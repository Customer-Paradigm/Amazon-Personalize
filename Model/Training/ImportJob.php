<?php
/**
 * CustomerParadigm_AmazonPersonalize
 *
 * @category   CustomerParadigm
 * @package    CustomerParadigm_AmazonPersonalize
 * @copyright  Copyright (c) 2023 Customer Paradigm (https://customerparadigm.com/)
 * @license    https://github.com/Customer-Paradigm/Amazon-Personalize/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use Aws\Personalize\PersonalizeClient;

class ImportJob extends PersonalizeBase
{
    protected $usersImportJobName;
    protected $itemsImportJobName;
    protected $interactionsImportJobName;
    protected $datasetGroupName;
    protected $usersDatasetName;
    protected $itemsDatasetName;
    protected $interactionsDatasetName;
    protected $usersDatasetArn;
    protected $itemsDatasetArn;
    protected $interactionsDatasetArn;
    protected $infoLogger;
    protected $pHelper;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
        \CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper,
        \CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient
    ) {
        parent::__construct($nameConfig, $sdkClient);
        $acct_num = $this->nameConfig->getAwsAccount();
        $this->pHelper = $pHelper;

        $this->infoLogger = $this->nameConfig->getLogger('info');
        $this->roleArn = "arn:aws:iam::$acct_num:role/personalize_full_access";
        $this->s3BucketName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/s3BucketName');
        $this->usersImportJobName = $this->nameConfig->buildName('users-import');
        $this->itemsImportJobName = $this->nameConfig->buildName('items-import');
        $this->interactionsImportJobName = $this->nameConfig->buildName('interactions-import');
        $this->datasetGroupName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/datasetGroupName');
        $this->usersDatasetName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/usersDatasetName');
        $this->itemsDatasetName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/itemsDatasetName');
        $this->interactionsDatasetName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/interactionsDatasetName');
        $this->usersDatasetArn = $this->nameConfig->buildArn('dataset', $this->datasetGroupName, "USERS");
        $this->itemsDatasetArn = $this->nameConfig->buildArn('dataset', $this->datasetGroupName, "ITEMS");
        $this->interactionsDatasetArn = $this->nameConfig->buildArn('dataset', $this->datasetGroupName, "INTERACTIONS");
    }

    public function getStatus()
    {
        $arnArray = [$this->usersDatasetArn,$this->itemsDatasetArn,$this->interactionsDatasetArn];
        $checkArray = [$this->usersImportJobName,$this->itemsImportJobName,$this->interactionsImportJobName];
        $checklist = [];
        $result = 'none found';
        try {
            foreach ($arnArray as $item) {
                $rtn = $this->personalizeClient->listDatasetImportJobs(array('datasetArn'=>$item));
                if (in_array($rtn['datasetImportJobs'][0]['jobName'], $checkArray)) {
                    $checklist[] = $rtn['datasetImportJobs'][0];
                }
            }
            if (count($checklist) == 0) {
                $result = 'not started';
            } elseif (count($checklist) < 3) {
                $result = 'in progress';
            } else {
                foreach ($checklist as $idx => $item) {
                    switch ($item['status']) {
                        case 'ACTIVE':
                            $result = 'complete';
                            $this->pHelper->setStepError('create_import_jobs', "");
                            break;
                        case 'CREATE PENDING':
                        case 'CREATE IN_PROGRESS':
                            $result = 'in progress';
                            break;
                        case 'CREATE FAILED':
                            $result = 'error';
                            // If Import job already exists ( wasn't removed on previous reset )
                            if (strstr('ResourceAlreadyExistsException', $item['failureReason']) !== false) {
                                $result = 'complete';
                                $this->pHelper->setStepError('create_import_jobs', "");
                                break;
                            }
                            $this->pHelper->setStepError('create_import_jobs', $item['failureReason']);
                            break;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->nameConfig->getLogger()->error("\ncheck datasetImportJobs status error: " . $e->getMessage());
            return $result;
        }
            $this->infoLogger->info('listDatasetImportJobs status result: ' . print_r($result, true));
        return $result;
    }

    public function createImportJobs()
    {
        $result = $this->personalizeClient->createDatasetImportJobAsync([
            'jobName' => $this->usersImportJobName,
            'datasetArn' => $this->usersDatasetArn,
            'dataSource' => ['dataLocation' => "s3://$this->s3BucketName/users.csv"],
            'roleArn' => $this->roleArn ])->wait();
        $this->nameConfig->saveName('usersImportJobName', $this->usersImportJobName);
        $this->nameConfig->saveArn('usersImportJobArn', $result['datasetImportJobArn']);

        $result = $this->personalizeClient->createDatasetImportJobAsync([
            'jobName' => $this->itemsImportJobName,
            'datasetArn' => $this->itemsDatasetArn,
            'dataSource' => ['dataLocation' => "s3://$this->s3BucketName/items.csv"],
            'roleArn' => $this->roleArn ])->wait();
        $this->nameConfig->saveName('itemsImportJobName', $this->itemsImportJobName);
        $this->nameConfig->saveArn('itemsImportJobArn', $result['datasetImportJobArn']);

        $result = $this->personalizeClient->createDatasetImportJobAsync([
            'jobName' => $this->interactionsImportJobName,
            'datasetArn' => $this->interactionsDatasetArn,
            'dataSource' => ['dataLocation' => "s3://$this->s3BucketName/interactions.csv"],
            'roleArn' => $this->roleArn ])->wait();
        $this->nameConfig->saveName('interactionsImportJobName', $this->interactionsImportJobName);
        $this->nameConfig->saveArn('interactionsImportJobArn', $result['datasetImportJobArn']);
    }
}
