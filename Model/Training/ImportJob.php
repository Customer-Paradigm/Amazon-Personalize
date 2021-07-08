<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

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
                \CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper
	)
	{
		parent::__construct($nameConfig);
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
		$this->usersDatasetArn = $this->nameConfig->buildArn('dataset',$this->datasetGroupName, "USERS");
		$this->itemsDatasetArn = $this->nameConfig->buildArn('dataset',$this->datasetGroupName, "ITEMS");
		$this->interactionsDatasetArn = $this->nameConfig->buildArn('dataset',$this->datasetGroupName, "INTERACTIONS");
	}

	public function getStatus() {
		$checkArray = array($this->usersImportJobName,$this->itemsImportJobName,$this->interactionsImportJobName);
		$checklist = array();
		$rtn = $this->personalizeClient->listDatasetImportJobs();
		$result = 'none found';
		try {
			foreach($rtn['datasetImportJobs'] as $idx=>$item) {
				if(in_array($item['jobName'],$checkArray)) {
					$checklist[] = $rtn['datasetImportJobs'][$idx];
				}
			}
			if(count($checklist) == 0) {
				$result = 'not started';
/*
				$result = 'error';
                        	$this->pHelper->setStepError('create_import_jobs',"No import jobs have been started");
				$this->nameConfig->getLogger()->error( "\ncheck datasetImportJobs status error: no jobs found for names " . print_r($checkArray,true));
*/
			} else if(count($checklist) < 3) {
				$result = 'in progress';
			} else {
				foreach($checklist as $idx=>$item) {
					switch ($item['status']) {
					case 'ACTIVE':
						$result = 'complete';
						break;
					case 'CREATE PENDING':
					case 'CREATE IN_PROGRESS':
						$result = 'in progress';
						break;
					case 'CREATE FAILED':
						$result = 'error';
                        			$this->pHelper->setStepError('create_import_jobs',$item['failureReason']);
						//return $item['failureReason'];
						break;
					}
				}
			}
		} catch(\Exception $e) {
			$this->nameConfig->getLogger()->error( "\ncheck datasetImportJobs status error: " . $e->getMessage());
			return $e->getMessage();
		}
		$this->infoLogger->info('listDatasetImportJobs status result: ' . print_r($result,true));
		return $result;
	}

	public function createImportJobs() {
			$result = $this->personalizeClient->createDatasetImportJobAsync([
				'jobName' => $this->usersImportJobName,
				'datasetArn' => $this->usersDatasetArn,
				'dataSource' => array('dataLocation' => "s3://$this->s3BucketName/users.csv"),
				'roleArn' => $this->roleArn ]
			)->wait();
			$this->nameConfig->saveName('usersImportJobName', $this->usersImportJobName);
			$this->nameConfig->saveArn('usersImportJobArn', $result['datasetImportJobArn']);
			
			$result = $this->personalizeClient->createDatasetImportJobAsync([
				'jobName' => $this->itemsImportJobName,
				'datasetArn' => $this->itemsDatasetArn,
				'dataSource' => array('dataLocation' => "s3://$this->s3BucketName/items.csv"),
				'roleArn' => $this->roleArn ]
			)->wait();
			$this->nameConfig->saveName('itemsImportJobName', $this->itemsImportJobName);
			$this->nameConfig->saveArn('itemsImportJobArn', $result['datasetImportJobArn']);

			$result = $this->personalizeClient->createDatasetImportJobAsync([
				'jobName' => $this->interactionsImportJobName,
				'datasetArn' => $this->interactionsDatasetArn,
				'dataSource' => array('dataLocation' => "s3://$this->s3BucketName/interactions.csv"),
				'roleArn' => $this->roleArn ]
			)->wait();
			$this->nameConfig->saveName('interactionsImportJobName', $this->interactionsImportJobName);
			$this->nameConfig->saveArn('interactionsImportJobArn', $result['datasetImportJobArn']);
	}
}
