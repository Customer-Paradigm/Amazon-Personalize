<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

class WizardTracking extends \Magento\Framework\Model\AbstractModel
{
    public const CACHE_TAG = 'customerparadigm_amazonpersonalize_wizardtracking';
    protected $_cacheTag = 'customerparadigm_amazonpersonalize_wizardtracking';
    protected $_eventPrefix = 'customerparadigm_amazonpersonalize_wizardtracking';
    protected $connection;
    protected $steps;
    protected $pHelper;
    protected $awsHelper;
    protected $infoLogger;
    protected $errorLogger;
    protected $nameConfig;
    protected $eventManager;
    protected $attempts;
    protected $maxAttempts;
    public $pConfig;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \CustomerParadigm\AmazonPersonalize\Logger\InfoLogger $infoLogger,
        \CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger $errorLogger,
        \CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper,
        \CustomerParadigm\AmazonPersonalize\Helper\Aws $awsHelper,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->connection = $this->getResource()->getConnection();
        $this->pHelper = $pHelper;
        $this->awsHelper = $awsHelper;
        $this->infoLogger = $infoLogger;
        $this->errorLogger = $errorLogger;
        $this->pConfig = $pConfig;
        $this->eventManager = $eventManager;
        $this->attempts = 0;
        $this->maxAttempts = 3;
        $this->steps = [
            'create_csv_files',
//        'create_personalize_s3_role',
            'create_s3_bucket',
        'upload_csv_files',
            'create_schemas',
            'create_dataset_group',
        'create_datasets',
            'create_import_jobs',
            'create_solution',
            'create_solution_version',
            'create_campaign',
        'create_event_tracker'
        ];
    }

    protected function _construct()
    {
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\ResourceModel\WizardTracking');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }

    public function saveStepData($step_name, $set_column, $value)
    {
        $sql = "update aws_wizard_steps set $set_column = '$value' where step_name = '$step_name'";
        $this->connection->exec($sql);
    }

    public function getStepData($step_name, $column)
    {
        $sql = "select $column value from aws_wizard_steps where step_name = '$step_name'";
        return $this->connection->query($sql)->fetch();
    }

    public function stepsInitialized()
    {
        $tabledata = $this->getStepInfo();
        return count($tabledata) == count($this->steps);
    }

    public function initStepsTable()
    {
        // $this->clearData();
        if (! $this->stepsInitialized()) {
            foreach ($this->steps as $step) {
                $sql = "insert into aws_wizard_steps(step_name) values('$step') ";
                $this->connection->exec($sql);
            }
        }
    }

    public function runSteps($wizard)
    {
        $this->attempts = 1;
        $rtn = [];
        $this->eventManager->dispatch('awsp_wizard_runsteps_before', ['obj' => $this]);
        try {
            $process = $this->getProcessStepState();
            $this->infoLogger->info("WizardTracking runSteps() getProcessStepState rtn: " . print_r($process, true));
            $step = $process['step'];
            $rtn['steps'] = $this->displayProgress();
            $rtn['mssg'] = $process['mssg'];
            $rtn['state'] = $process['state'];
            switch ($process['state']) {
                case 'error':
                    $this->errorLogger->error("WizardTracking runSteps() non-exeption error at step:  " . $step);
                    $this->errorLogger->error("WizardTracking runSteps() error message:  " . $rtn['mssg']);
                    $this->setStepError($step, $rtn['mssg']);
                    // $this->infoLogger->info("WizardTracking runSteps() error status -- try $step again");
                    // return $this->tryAgain($step);
                    // no break
                case 'step ready':
                case 'not started':
                    $fname = $this->stepToFuncName($step);
                    $this->infoLogger->info("WizardTracking runSteps() starting step " . $fname);
                    $this->setStepInprogress($step);
                    $this->pHelper->saveStepData($step, 'attempt_number', $this->attempts);
                    try {
                        $rtn = $wizard->{$fname}();
                        if(empty($rtn) || $rtn == false) {
                            $rtn = [];
                        }

                        $check = $wizard->checkStatus($step);
                        if ($check == 'complete') {
                            $this->setStepComplete($step);
                            $rtn['steps'] = $this->displayProgress();
                            $rtn['mssg'] = 'success';
                            $rtn['state'] = 'complete';
                        }
                    } catch (\Exception $e) {
                        $this->setStepError($step, $e->getMessage());
                        $this->errorLogger->error("WizardTracking runSteps() error at step:  " . $step);
                        $this->errorLogger->error("WizardTracking runSteps() error message:  " . $e);
                        $rtn['mssg'] = 'run step error';
                        $rtn['is_success'] = false;
                    }
                    break;
                case 'in progress':
                    $check = $wizard->checkStatus($step);
                    if ($check == 'complete') {
                        $this->setStepComplete($step);
                    }
                    /*
                             if($check == 'not started') {
                                 $this->setStepReady($step);
                     }
                     */
                    $rtn['steps'] = $this->displayProgress();
                    $rtn['mssg'] = 'success';
                    $rtn['state'] = 'complete';
            }
            $this->pHelper->flushAllCache();
        } catch (Exception $e) {
            $this->errorLogger->error("WizardTracking runSteps() Exception at step:  " . $step . "------------------");
            $this->errorLogger->error($e->getMessage());
            $this->eventManager->dispatch('awsp_wizard_runsteps_error', ['obj' => $e]);
        }
        $this->eventManager->dispatch('awsp_wizard_runsteps_after', ['obj' => $this]);
        return $rtn;
    }

    public function tryAgain($step)
    {
        $rtn = [];
        $this->attempts +=1;
        $this->setStepRetry($step);
        $process = $this->getProcessStepState();
        $step = $process['step'];
        $rtn['steps'] = $this->displayProgress();
        $rtn['mssg'] = $process['mssg'];
        $rtn['state'] = $process['state'];
        return $rtn;
    }

    public function validateSteps()
    {
        $stepinfo = $this->getStepInfo();
    }

    public function getStepInfo()
    {
        $sql = "select * from aws_wizard_steps";
        return $this->connection->query($sql)->fetchAll();
    }

    public function getAssets()
    {
        $sql = "select path, value from core_config_data where path like '%data_type_%'";
        return $this->connection->query($sql)->fetchAll();
    }

    public function getStepStateSummary()
    {
        $stepinfo = $this->getStepInfo();
        $rtn = [];
        foreach ($stepinfo as $step) {
            $mssg = '';
            $state = '';
            $name = $step['step_name'];
            $has_error = false;
            if ($step['error']) {
                $has_error = true;
                $state = 'error';
                $mssg = $step['error'];
            }

            if ($step['in_progress']) {
                $state = 'in progress';
            } elseif ($step['is_completed']) {
                $state = 'complete';
            } elseif ($step['step_name'] == 'create_csv_files'
                && $has_error
                && strpos($step['error'], 'you need at least 1000') !== false) {
                $state = 'paused';
            } else {
                $state = 'not started';
            }

            $rtn[] = ['step_name'=>$name,'state'=>$state,'error'=>$has_error,'mssg'=>$mssg];
        }
        return $rtn;
    }

    /* Tracked in aws_wizard_steps db table
     *
     * Returns overall process Status
     * @return array
     */
    public function getProcessStatus()
    {
        $rtn = [];
        $steps = $this->getStepInfo();
        if (empty($steps)) {
            $rtn = ['step_name'=>'create_s3_bucket', 'status'=>'notStarted'];
        }
        $count = count($steps);
        foreach ($steps as $idx => $step) {
            if ($step['error'] && $step['step_name'] == 'create_csv_files') {
                return ['step_name'=>$step['step_name'], 'status'=>'paused'];
            }
            if ($step['error']) {
                return ['step_name'=>$step['step_name'], 'status'=>'hasError'];
            }
            if ($step['in_progress'] && !$step['is_completed']) {
                return ['step_name'=>$step['step_name'], 'status'=>'inProgress'];
            }
            if ($step['is_completed'] && $count <= $step['wizard_step_id']) {
                $this->pConfig->saveConfigSetting('awsp_settings/awsp_general/campaign_exists', 1);
                return ['step_name'=>'finished', 'status'=>'finished'];
            }
            if (!$step['in_progress'] && !$step['is_completed'] && $step['wizard_step_id'] == 1) {
                return ['step_name'=>$step['step_name'], 'status'=>'notStarted'];
            }
            if (!$step['in_progress'] && !$step['is_completed']) {
                return ['step_name'=>$step['step_name'], 'status'=>'ready'];
            }
            // Catch holes in logic
            $rtn = ['step_name'=>$step['step_name'], 'status'=>'unknown'];
        }
        return $rtn;
    }

    /* Tracked in aws_wizard_steps db table
     *
     * Returns state of current process step
     * @return array
     */
    public function getProcessStepState()
    {
        $steps = $this->getStepStateSummary();
        foreach ($steps as $idx => $step) {
            if ($idx === 0 && $step['state'] == 'not started') {
                return ['step'=>$step['step_name'],'state'=>'not started', 'mssg'=>''];
            }
            if ($step['error']) {
                return ['step'=>$step['step_name'],'state'=>'error','mssg'=>$step['mssg']];
            }
            if ($step['step_name'] == 'create_csv_files' && strpos($step['error'], 'you need at least 1000') !== false) {
                return ['step'=>$step['step_name'],'state'=>'paused','mssg'=>$step['mssg']];
            }
            if ($step['step_name'] == 'create_solution_version' && strpos($step['error'], 'you need at least 1000') !== false) {
                return ['step'=>$step['step_name'],'state'=>'error','mssg'=>$step['mssg']];
            }
            if ($step['state'] == 'in progress') {
                return ['step'=>$step['step_name'],'state'=>$step['state'],'mssg'=>''];
            }
            if ($step['state'] == 'not started') {
                return ['step'=>$step['step_name'],'state'=>'step ready','mssg'=>''];
            }
        }
        return ['step'=>'','state'=>'finished', 'mssg'=>''];
    }

    public function displayProgress()
    {
        if (! $this->pHelper->canDisplayAdmin()) {
            return ['steps'=>[], 'license'=>false, 'stat'=>'error','mssg'=>'Please enter and save your AWS credentials to enable Campaign Setup Wizard'];
        }

        $summary = $this->getStepStateSummary();

        if(empty($summary)) {
                return [];
        }

        foreach ($summary as $idx => $info) {
            $summary[$idx]['step_name'] = $this->stepToDisplayName($info['step_name']);
        }
        return $summary;
    }

    public function getNextStep()
    {
        $sql = "select * from aws_wizard_steps where is_completed is NULL or is_completed = 0  order by wizard_step_id limit 1";
        return $this->connection->query($sql)->fetch();
    }

    public function validateNextStep($step)
    {
        $rtn = [];

        $rtn['step_name'] = $step['step_name'];
        $rtn['display_name'] = $this->stepToDisplayName($step['step_name']);
        $rtn['state'] = 'ready';
        $rtn['error_mssg'] = $step['error'];
        if ($step['in_progress']) {
            $rtn['state'] = 'in_progress';
        }
        if ($step['error']) {
            $rtn['state'] = 'error';
        }
        if ($step['step_name'] == 'create_csv_files' && strpos($step['error'], 'you need at least 1000') !== false) {
            $rtn['state'] = 'paused';
        }
        if ($step['step_name'] == 'create_solution_version' && strpos($step['error'], 'you need at least 1000') !== false) {
            $rtn['state'] = 'error';
        }
        return $rtn;
    }

    public function needsInteractions()
    {
        return $this->pConfig->needsInteractions();
    }

    public function clearData()
    {
        $tableName = $this->getResource()->getMainTable();
        $this->connection->truncateTable($tableName);
        $sql = "delete from core_config_data where path like 'awsp_wizard/data_type_name/%'";
        $this->connection->exec($sql);
        $sql = "delete from core_config_data where path like 'awsp_wizard/data_type_arn/%'";
        $this->connection->exec($sql);
    }

    public function stepToAssetName($string)
    {
        $rtn = '';
        // de-pluralize
        if (substr($string, -1) == 's') {
            $string = substr($string, 0, -1);
        }
        $string = str_replace('create_', '', $string);
        $tmp = explode('_', $string);
        $rtn .= lcfirst($tmp[0]);
        if (count($tmp) > 1) {
            $rtn .= ucfirst($tmp[1]);
        }
        return $rtn;
    }

    protected function stepToFuncName($string)
    {
        $rtn = '';
        $tmp = explode('_', $string);
        foreach ($tmp as $str) {
            $rtn .= ucfirst($str);
        }
        $rtn = lcfirst($rtn);
        return $rtn;
    }

    protected function stepToDisplayName($string)
    {
        $rtn = [];
        $tmp = explode('_', $string);
        foreach ($tmp as $str) {
            $rtn[] = ucfirst($str);
        }
        $rtn = implode(" ", $rtn);
        if (strpos($rtn, 'Import') !== false) {
            $rtn .= ' (long process)';
        }
        if (strpos($rtn, 'Version') !== false) {
            $rtn .= ' (long process)';
        }
        return $rtn;
    }

    public function setStepComplete($step)
    {
        $this->pHelper->saveStepData($step, 'is_completed', true);
        $this->pHelper->saveStepData($step, 'in_progress', false);
    }

    public function setStepReady($step)
    {
        $this->pHelper->saveStepData($step, 'is_completed', null);
        $this->pHelper->saveStepData($step, 'in_progress', null);
    }

    public function setStepInprogress($step)
    {
        $this->pHelper->saveStepData($step, 'is_completed', false);
        $this->pHelper->saveStepData($step, 'in_progress', true);
    }

    public function setStepError($step, $message)
    {
        $this->pHelper->setStepError($step, $message);
    }

    public function resetStep($step)
    {
        $this->pHelper->saveStepData($step, 'error', null);
        $this->pHelper->saveStepData($step, 'in_progress', null);
        $this->pHelper->saveStepData($step, 'is_completed', null);
    }

    protected function setStepRetry($step)
    {
        $attempt = $this->getAttemptNum($step);
        if (is_array($attempt)) {
            $attempt = json_encode($attempt);
        }
        $this->infoLogger->info("WizardTracking setStepRetry() -- attempt #: " . $attempt);
        if ($attempt >= $this->maxAttempts) {
            $this->pHelper->saveStepData($step, 'in_progress', true);
            $this->pHelper->saveStepData($step, 'is_completed', false);
            $this->pHelper->saveStepData($step, 'error', "unknown error after $attempt tries");
            return [];
        } else {
            $this->pHelper->saveStepData($step, 'attempt_number', $attempt);
            $this->pHelper->saveStepData($step, 'is_completed', null);
            $this->pHelper->saveStepData($step, 'error', null);
            return $step;
        }
    }

    protected function getAttemptNum($step)
    {
        return $this->getStepData($step, 'attempt_number');
    }
}
