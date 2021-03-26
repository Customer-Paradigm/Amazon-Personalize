<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class Dataset extends PersonalizeBase
{
	protected $userDatasetName;
	protected $itemDatasetName;
	protected $interactionDatasetName;
	protected $datasetGroupName;
	protected $usersSchemaName;
	protected $itemsSchemaName;
	protected $interactionsSchemaName;
	protected $datasetGroupArn;
	protected $usersSchemaArn;
	protected $itemsSchemaArn;
	protected $interactionsSchemaArn;
	protected $infoLogger;
	protected $errorLogger;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
	{
		parent::__construct($nameConfig);
		$this->infoLogger = $this->nameConfig->getLogger('info');
                $this->errorLogger = $this->nameConfig->getLogger('error');
		$this->usersDatasetName = $this->nameConfig->buildName('users-dataset');
		$this->itemsDatasetName = $this->nameConfig->buildName('items-dataset');
		$this->interactionsDatasetName = $this->nameConfig->buildName('interactions-dataset');
		$this->usersDatasetArn = $this->nameConfig->buildArn('users-dataset');
		$this->itemsDatasetArn = $this->nameConfig->buildArn('items-dataset');
		$this->interactionsDatasetArn = $this->nameConfig->buildArn('interactions-dataset');
		$this->datasetGroupName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/datasetGroupName');
		$this->usersSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/usersSchemaName');
		$this->itemsSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/itemsSchemaName');
		$this->interactionsSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/interactionsSchemaName');
		$this->datasetGroupArn = $this->nameConfig->buildArn('dataset-group',$this->datasetGroupName);
		$this->infoLogger->info( "\ndatasetGroupArn ---------: \n" . $this->datasetGroupArn);
		$this->usersSchemaArn = $this->nameConfig->buildArn('schema',$this->usersSchemaName);
		$this->itemsSchemaArn = $this->nameConfig->buildArn('schema',$this->itemsSchemaName);
		$this->interactionsSchemaArn = $this->nameConfig->buildArn('schema',$this->interactionsSchemaName);
	}
/*
	public function awsSchemaIsCreated($config_path) {
		$config_arn = $this->nameConfig->getConfigVal($config_path);
		if ($config_arn == NULL) {
			return false;
		} else {
			try {
				$aws_schema = $this->personalizeClient->{$this->apiDescribe}([
					'datasetArn' => $config_arn,
				]);
			} catch(\Exception $e) {
				$this->errorLogger->error( "\nerror checking schema creation--$config_path : \n" . $e->getMessage());
				return 'error';
			}

			if ($aws_schema['dataset']['datasetArn'] == $config_arn) {
				$this->infoLogger->info( "\nschema created--arn: $config_arn\n");
				return true;
			}

			return false;
		}
	}
*/

	public function getStatus() {
		$count = 0;
		if($rtn = $this->datasetExists($this->usersDatasetName)) {
			$checklist[] = $rtn;
		}
		if($rtn = $this->datasetExists($this->itemsDatasetName)) {
			$checklist[] = $rtn;
		}
		if($rtn = $this->datasetExists($this->interactionsDatasetName)) {
			$checklist[] = $rtn;
		}
		//$this->infoLogger->info( "\ndataset getstatus--checklist:\n" . print_r($checklist,true));
		switch (true) {
			CASE (count($checklist) == 3):
				return 'complete';
			break;
			CASE (count($checklist) == 0):
				return 'not started';
			break;
			CASE (count($checklist) > 0 && count($checklist) < 3):
				return 'in progress';
			break;
			DEFAULT:
			return  'not defined';
		}
	}

	public function createDatasets() {
		try {
			if( ! alreadyCreated('users',$this->usersDatasetName,$this->usersDatasetArn) ) {
				$result = $this->personalizeClient->{$this->apiCreate}([
					'name' => $this->usersDatasetName,
					'schemaArn' => $this->usersSchemaArn,
					'datasetGroupArn' => $this->datasetGroupArn,
					'datasetType' => 'Users', ]
				)->wait();
				$this->nameConfig->saveName('usersDatasetName', $this->usersDatasetName);
				$this->nameConfig->saveArn('usersDatasetArn', $result['datasetArn']);
			}
		} catch(\Exception $e) {
			$this->errorLogger->error( "\ncreate users dataset  error : \n" . $e->getMessage());
		}

		try {
			if( ! alreadyCreated('items',$this->itemsDatasetName,$this->itemsDatasetArn) ) {
				$result = $this->personalizeClient->{$this->apiCreate}([
					'name' => $this->itemsDatasetName,
					'schemaArn' => $this->itemsSchemaArn,
					'datasetGroupArn' => $this->datasetGroupArn,
					'datasetType' => 'Items']
				)->wait();
				$this->nameConfig->saveName('itemsDatasetName', $this->itemsDatasetName);
				$this->nameConfig->saveArn('itemsDatasetArn', $result['datasetArn']);
			}
		} catch(\Exception $e) {
			$this->errorLogger->error( "\ncreate items dataset error : \n" . $e->getMessage());
		}

		try {
			if( ! alreadyCreated('interactions',$this->interactionsDatasetName,$this->interactionsDatasetArn) ) {
				$result = $this->personalizeClient->{$this->apiCreate}([
					'name' => $this->interactionsDatasetName,
					'schemaArn' => $this->interactionsSchemaArn,
					'datasetGroupArn' => $this->datasetGroupArn,
					'datasetType' => 'Interactions']
				)->wait();
				$this->nameConfig->saveName('interactionsDatasetName', $this->interactionsDatasetName);
				$this->nameConfig->saveArn('interactionsDatasetArn', $result['datasetArn']);
			}
		} catch(\Exception $e) {
			$this->errorLogger->error( "\ncreate interactions dataset error : \n" . $e->getMessage());
		}
	}

        public function alreadyCreated($name,$datasetName,$datasetArn) {
                        $rtn = false;
                        if( $this->datasetExists($datasetName) ) {
				$rtn = true;
			//	if(empty($this->nameConfig->getConfigVal($name."DatasetName", $datasetName))) {
                                        $this->nameConfig->saveName($name."DatasetName", $datasetName);
                         //       }
                          //      if(empty($this->nameConfig->getConfigVal($name."DatasetArn", $datasetArn))) {
                                        $this->nameConfig->saveArn($name."DatasetArn", $datasetArn);
                           //     }
                        }
                        return $rtn;
        }

    public function datasetExists($datasetName) {
        try {
                $datasets = $this->personalizeClient->listDatasets(array('datasetGroupArn'=>$this->datasetGroupArn,'maxResults'=>100));
                foreach($datasets['datasets'] as $idx=>$item) {
                        if($item['name'] === $datasetName) {
                                return true;
                        }
                }
        } catch(Exception $e) {
                $this->errorLogger->error( "\ndatasetExists() error. Message:\n" . print_r($e->getMessage(),true));
                exit;
        }
        return false;
    }

}
