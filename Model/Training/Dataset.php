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
//	protected $infoLogger;
//	protected $errorLogger;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
	{
		parent::__construct($nameConfig);
		$this->datasetGroupName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/datasetGroupName');
		$this->datasetGroupArn = $this->nameConfig->buildArn('dataset-group',$this->datasetGroupName);

		$this->usersDatasetName = $this->nameConfig->buildName('users-dataset');
		$this->itemsDatasetName = $this->nameConfig->buildName('items-dataset');
		$this->interactionsDatasetName = $this->nameConfig->buildName('interactions-dataset');
                $this->usersDatasetArn = $this->nameConfig->buildArn('dataset', $this->datasetGroupName ) . "/USERS";
                $this->itemsDatasetArn = $this->nameConfig->buildArn('dataset', $this->datasetGroupName) . "/ITEMS";
		$this->interactionsDatasetArn = $this->nameConfig->buildArn('dataset',$this->datasetGroupName) . "/INTERACTIONS";

		$this->usersSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/usersSchemaName');
		$this->itemsSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/itemsSchemaName');
		$this->interactionsSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/interactionsSchemaName');
		$this->usersSchemaArn = $this->nameConfig->buildArn('schema',$this->usersSchemaName);
		$this->itemsSchemaArn = $this->nameConfig->buildArn('schema',$this->itemsSchemaName);
		$this->interactionsSchemaArn = $this->nameConfig->buildArn('schema',$this->interactionsSchemaName);
	}

	public function getStatus() {
		$checklist = array();
                
		if($rtn = $this->datasetExists($this->usersDatasetName)) {
                        $checklist[] = $rtn;
                }
                if($rtn = $this->datasetExists($this->itemsDatasetName)) {
                        $checklist[] = $rtn;
                }
                if($rtn = $this->datasetExists($this->interactionsDatasetName)) {
                        $checklist[] = $rtn;
                }

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
			if( ! $this->checkAssetCreatedAndSync('users','Dataset',$this->usersDatasetName,$this->usersDatasetArn) ) {
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
			$this->errorLogger->error( "\ncreate_users_dataset error: " . $e->getMessage());
		}

		try {
			if( ! $this->checkAssetCreatedAndSync('items','Dataset',$this->itemsDatasetName,$this->itemsDatasetArn) ) {
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
			$this->errorLogger->error( "\ncreate_items_dataset error: " . $e->getMessage());
		}

		try {
			if( ! $this->checkAssetCreatedAndSync('interactions','Dataset',$this->interactionsDatasetName,$this->interactionsDatasetArn) ) {
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
			$this->errorLogger->error( "\ncreate_interactions_dataset error: " . $e->getMessage());
		}
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
			$this->errorLogger->error( "\ndatasetExists() error. Message: " . print_r($e->getMessage(),true));
			exit;
		}
		return false;
	}
}
