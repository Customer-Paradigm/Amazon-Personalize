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

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
	{
		parent::__construct($nameConfig);
		$this->usersDatasetName = $this->nameConfig->buildName('users-dataset');
		$this->itemsDatasetName = $this->nameConfig->buildName('items-dataset');
		$this->interactionsDatasetName = $this->nameConfig->buildName('interactions-dataset');
		$this->datasetGroupName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/datasetGroupName');
		$this->usersSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/usersSchemaName');
		$this->itemsSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/itemsSchemaName');
		$this->interactionsSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/interactionsSchemaName');
		$this->datasetGroupArn = $this->nameConfig->buildArn('dataset-group',$this->datasetGroupName);
		$this->usersSchemaArn = $this->nameConfig->buildArn('schema',$this->usersSchemaName);
		$this->itemsSchemaArn = $this->nameConfig->buildArn('schema',$this->itemsSchemaName);
		$this->interactionsSchemaArn = $this->nameConfig->buildArn('schema',$this->interactionsSchemaName);
	}

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
				return 'error';
			}

			if ($aws_schema['dataset']['datasetArn'] == $config_arn) {
				return true;
			}

			return false;
		}
	}

	public function getStatus() {
		$count = 0;
		$checklist[] = $this->awsSchemaIsCreated('awsp_wizard/data_type_arn/usersDatasetArn');
		$checklist[] = $this->awsSchemaIsCreated('awsp_wizard/data_type_arn/itemsDatasetArn');
		$checklist[] = $this->awsSchemaIsCreated('awsp_wizard/data_type_arn/interactionsDatasetArn');
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
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => $this->usersDatasetName,
				'schemaArn' => $this->usersSchemaArn,
				'datasetGroupArn' => $this->datasetGroupArn,
				'datasetType' => 'Users', ]
			)->wait();
			$this->nameConfig->saveName('usersDatasetName', $this->usersDatasetName);
			$this->nameConfig->saveArn('usersDatasetArn', $result['datasetArn']);
		} catch(\Exception $e) {
			$this->nameConfig->getLogger()->error( "\ncreate users dataset  error : \n" . $e->getMessage());
		}

		try {
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => $this->itemsDatasetName,
				'schemaArn' => $this->itemsSchemaArn,
				'datasetGroupArn' => $this->datasetGroupArn,
				'datasetType' => 'Items']
			)->wait();
			$this->nameConfig->saveName('itemsDatasetName', $this->itemsDatasetName);
			$this->nameConfig->saveArn('itemsDatasetArn', $result['datasetArn']);
		} catch(\Exception $e) {
			$this->nameConfig->getLogger()->error( "\ncreate items dataset error : \n" . $e->getMessage());
		}

		try {
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => $this->interactionsDatasetName,
				'schemaArn' => $this->interactionsSchemaArn,
				'datasetGroupArn' => $this->datasetGroupArn,
				'datasetType' => 'Interactions']
			)->wait();
			$this->nameConfig->saveName('interactionsDatasetName', $this->interactionsDatasetName);
			$this->nameConfig->saveArn('interactionsDatasetArn', $result['datasetArn']);
		} catch(\Exception $e) {
			$this->nameConfig->getLogger()->error( "\ncreate interactions dataset error : \n" . $e->getMessage());
		}
	}
}
