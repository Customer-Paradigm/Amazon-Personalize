<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

class WizardTracking extends \Magento\Framework\Model\AbstractModel 
{
    const CACHE_TAG = 'customerparadigm_amazonpersonalize_wizardtracking';
    protected $_cacheTag = 'customerparadigm_amazonpersonalize_wizardtracking';
    protected $_eventPrefix = 'customerparadigm_amazonpersonalize_wizardtracking';
    protected $connection;
    protected $trackingCollection;
    protected $steps;
    protected $pHelper;
    protected $logger;
    protected $nameConfig;
    protected $pConfig;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Psr\Log\LoggerInterface $logger,
        \CustomerParadigm\AmazonPersonalize\Model\ResourceModel\WizardTracking\Collection $trackingCollection,
        \CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
            $this->connection = $this->getResource()->getConnection();
            $this->trackingCollection = $trackingCollection;
            $this->pHelper = $pHelper;
            $this->logger = $logger;
            $this->pConfig = $pConfig;

        $this->steps = array(
            // 'create_personalize_user',
            'create_csv_files',
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
        );
    }

    protected function _construct() { 
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

    public function saveStepData($step_name, $set_column, $value) {
        $sql = "update aws_wizard_steps set $set_column = '$value' where step_name = '$step_name'";
        $this->connection->exec($sql);
    }
    
    public function stepsInitialized() {
        $tabledata = $this->getStepInfo();
        return count($tabledata) == count( $this->steps);
    }

	public function initStepsTable() {
       // $this->clearData();
        if( ! $this->stepsInitialized() ){
            foreach( $this->steps as $step) {
                $sql = "insert into aws_wizard_steps(step_name) values('$step') ";
                $this->connection->exec($sql);
            }
        } 
	}
    
    public function runSteps($wizard) {
        $rtn = array();
        $process = $this->getProcessStepState();
        $step = $process['step'];
        $rtn['steps'] = $this->displayProgress();
        $rtn['mssg'] = $process['mssg'];
        $rtn['state'] = $process['state'];
        switch($process['state']) {
			case 'error':
				return $rtn;
			case 'step ready':
			case 'not started':
				$fname = $this->stepToFuncName($step);
				$this->setStepInprogress($step);
				try {
					$rtn = $wizard->{$fname}();
					$check = $wizard->checkStatus($step);
					if($check == 'complete') {
						$this->setStepComplete($step);
        				$rtn['steps'] = $this->displayProgress();
						$rtn['mssg'] = 'success';
						$rtn['state'] = 'complete';
					}
				} catch(\Exception $e) {
					$this->setStepError($step, $e->getMessage());
					$this->logger->critical($e);
					$rtn['mssg'] = 'run step error';
					$rtn['is_success'] = false;
				}
				break;
			case 'in progress':
				$rtn['steps'] = $this->displayProgress();
				$rtn['mssg'] = 'success';
				$rtn['state'] = 'complete';
				$check = $wizard->checkStatus($step);
				if($check == 'complete') {
					$this->setStepComplete($step);
				}
			}
        $this->pHelper->flushAllCache();
		return $rtn;
    }

    public function validateSteps() {
        $stepinfo = $this->getStepInfo();
    }
    
    public function getStepInfo() {
		$sql = "select * from aws_wizard_steps";
        return $this->connection->query($sql)->fetchAll();
    }
    
    public function getAssets() {
		$sql = "select path, value from core_config_data where path like '%data_type_%'";
        return $this->connection->query($sql)->fetchAll();
    }

    public function getStepStateSummary() {
        $stepinfo = $this->getStepInfo();
        $rtn = array();
        foreach($stepinfo as $step) {
            $mssg = '';
            $state = '';
            $name = $step['step_name'];
            $has_error = false;
            if( $step['error'] ) {
                $has_error = true;
                $state = 'error';
                $mssg = $step['error'];
            } 
            
            if( $step['in_progress'] ) {
                $state = 'in progress';
            } elseif ( $step['is_completed'] ){
                $state = 'complete';
            } else {
                $state = 'not started';
            }
            
            $rtn[] = array('step_name'=>$name,'state'=>$state,'error'=>$has_error,'mssg'=>$mssg);
        }
        return $rtn;
    }

    /* Tracked in aws_wizard_steps db table 
     *
     * Returns overall process Status
     * @return array
     */
    public function getProcessStatus() {
        $rtn = array();
        $steps = $this->getStepInfo();
        if(empty($steps)) {
            $rtn = array('step_name'=>'create_s3_bucket', 'status'=>'notStarted');
        }
        $count = count($steps);
        foreach($steps as $idx=>$step) {
            if( $step['error'] ) {
                return array('step_name'=>$step['step_name'], 'status'=>'hasError');
            }
            if( $step['in_progress'] && !$step['is_completed'] ) {
                return array('step_name'=>$step['step_name'], 'status'=>'inProgress');
            }
            if( $step['is_completed'] && $count <= $step['wizard_step_id']) {
                $this->pConfig->saveConfigSetting('awsp_settings/awsp_general/campaign_exists',1);
                return array('step_name'=>'finished', 'status'=>'finished');
            }
            if( !$step['in_progress'] && !$step['is_completed'] && $step['wizard_step_id'] == 1 ) {
                return array('step_name'=>$step['step_name'], 'status'=>'notStarted');
            }
            if( !$step['in_progress'] && !$step['is_completed'] ) {
                return array('step_name'=>$step['step_name'], 'status'=>'ready');
            }
            // Catch holes in logic 
            $rtn = array('step_name'=>$step['step_name'], 'status'=>'unknown');
        }
        return $rtn;
    }

    /* Tracked in aws_wizard_steps db table 
     *
     * Returns state of current process step
     * @return array
     */
    public function getProcessStepState() {
        $steps = $this->getStepStateSummary();
        foreach($steps as $idx=>$step) {
            if($idx === 0 && $step['state'] == 'not started') {
                return array('step'=>$step['step_name'],'state'=>'not started', 'mssg'=>'');
            }
            if( $step['error']) {
                return array('step'=>$step['step_name'],'state'=>'error','mssg'=>$step['mssg']);
            }
            if($step['state'] == 'in progress') {
                return array('step'=>$step['step_name'],'state'=>$step['state'],'mssg'=>'');
            }
            if( $step['state'] == 'not started' ) {
                return array('step'=>$step['step_name'],'state'=>'step ready','mssg'=>'');
            }
        }
        return array('step'=>'','state'=>'finished', 'mssg'=>'');
    }

    public function displayProgress() {
        $summary = $this->getStepStateSummary();
        foreach($summary as $idx=>$info) {
            $summary[$idx]['step_name'] = $this->stepToDisplayName($info['step_name']);
        }
        return $summary;
    }

    public function getNextStep() {
		$sql = "select * from aws_wizard_steps where is_completed is NULL or is_completed = 0  order by wizard_step_id limit 1";
        return $this->connection->query($sql)->fetch();
    }
    
    public function validateNextStep($step) {
        $rtn = array();
        
        $rtn['step_name'] = $step['step_name'];
        $rtn['display_name'] = $this->stepToDisplayName($step['step_name']);
        $rtn['state'] = 'ready';
        $rtn['error_mssg'] = $step['error'];
        if( $step['in_progress'] ) {
            $rtn['state'] = 'in_progress';
        }
        if( $step['error'] ) {
            $rtn['state'] = 'error';
        }
        return $rtn;
    }

	public function clearData() {
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
        if (substr($string, -1) == 's')
        {
            $string = substr($string, 0, -1);
        }
        $string = str_replace('create_','', $string);
        $tmp = explode('_', $string);
        $rtn .= lcfirst($tmp[0]);
        if(count($tmp) > 1 ) {
            $rtn .= ucfirst($tmp[1]);
        }
        return $rtn;
    }

	protected function stepToFuncName($string) 
	{	$rtn = '';
		$tmp = explode('_', $string);
		foreach($tmp as $str) {
			$rtn .= ucfirst($str);
		}
		$rtn = lcfirst($rtn);
		return $rtn;
	}
    
    protected function stepToDisplayName($string) 
	{	$rtn = array();
		$tmp = explode('_', $string);
		foreach($tmp as $str) {
			$rtn[] = ucfirst($str);
        }
        $rtn = implode(" ", $rtn);
        if(strpos($rtn, 'Import') !== false ){
            $rtn .= ' (long process)';
        }
        if(strpos ($rtn, 'Version') !== false) {
            $rtn .= ' (long process)';
        }
		return $rtn;
	}

    protected function setStepComplete($step) {
        $this->saveStepData($step,'is_completed', true);
        $this->saveStepData($step,'in_progress', false);
    }

    protected function setStepInprogress($step) {
        $this->saveStepData($step,'in_progress', true);
    }

    protected function setStepError($step,$message) {
        $this->saveStepData($step,'error',$message);
    }
}
