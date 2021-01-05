<?php namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class Schema extends PersonalizeBase
{
	protected $usersSchemaName;
	protected $itemsSchemaName;
	protected $interactionsSchemaName;
	protected $usersSchemaArn;
	protected $itemsSchemaArn;
	protected $interactionsSchemaArn;
	protected $nameConfig;
	protected $infoLogger;
	protected $errorLogger;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
	{
		parent::__construct($nameConfig);
		$this->infoLogger = $nameConfig->getLogger('info');
		$this->errorLogger = $nameConfig->getLogger('error');
		$this->usersSchemaName = $this->nameConfig->buildName('users-schema');
		$this->itemsSchemaName = $this->nameConfig->buildName('items-schema');
		$this->interactionsSchemaName = $this->nameConfig->buildName('interactions-schema');
	}

	public function awsSchemaIsCreated($config_path) {
		$config_arn = $this->nameConfig->getConfigVal($config_path);
		if ($config_arn == NULL) {
			return false;
		} else {
			try {
				$aws_schema = $this->personalizeClient->{$this->apiDescribe}([
					'schemaArn' => $config_arn,
				]);
			} catch(\Exception $e) {
				return 'error';
			}

			if ($aws_schema['schema']['schemaArn'] == $config_arn) {
				return true;
			}

			return false;
		}
	}

	public function getStatus() {
		$count = 0;
		$checklist[] = $this->awsSchemaIsCreated('awsp_wizard/data_type_arn/usersSchemaArn');
		$checklist[] = $this->awsSchemaIsCreated('awsp_wizard/data_type_arn/itemsSchemaArn');
		$checklist[] = $this->awsSchemaIsCreated('awsp_wizard/data_type_arn/interactionsSchemaArn');
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

	public function createSchemas() {

		$schUser = '{
		"type": "record",
			"name": "Users",
			"namespace": "com.amazonaws.personalize.schema",
			"fields": [
	{
		"name": "USER_ID",
			"type": "string"
	},
	{
		"name": "GROUP",
			"type": "string",
			"categorical": true
	},
	{
		"name": "COUNTRY",
			"type": "string"
	},
	{
		"name": "CITY",
			"type": "string"
	},
	{
		"name": "STATE",
			"type": "string"
	},
	{
		"name": "POSTCODE",
			"type": "string"
	}

],
	"version": "1.0"
	}';

		$schItem = '{
		"type": "record",
			"name": "Items",
			"namespace": "com.amazonaws.personalize.schema",
			"fields": [
	{
		"name": "ITEM_ID",
			"type": "string"
	},
	{
		"name": "PRICE",
			"type": "float"
	},
	{
		"name": "WEIGHT",
			"type": "string"
	},
	{
		"name": "CATEGORIES",
			"type": "string",
			"categorical": true
	}
],
	"version": "1.0"
	}';

		$schInt = '{
		"type": "record",
			"name": "Interactions",
			"namespace": "com.amazonaws.personalize.schema",
			"fields": [
	{
		"name": "USER_ID",
			"type": "string"
	},
	{
		"name": "ITEM_ID",
			"type": "string"
	},
	{
		"name": "EVENT_TYPE",
			"type": "string"
	},
	{
		"name": "TIMESTAMP",
			"type": "long"
	}
],
	"version": "1.0"
	}';

		try {
			$this->infoLogger->info( "\ncreate users schema result : \n");
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => "$this->usersSchemaName",
				'schema' => $schUser,
			])->wait();
			$this->infoLogger->info($result);
			$this->nameConfig->saveName('usersSchemaName', $this->usersSchemaName);
			$this->nameConfig->saveArn('usersSchemaArn', $result['schemaArn']);
			$this->usersSchemaArn = $result['schemaArn'];
			$check = $this->personalizeClient->{$this->apiDescribe}([
				'schemaArn' => $this->usersSchemaArn,
			]);
			$this->infoLogger->info( "\ncheck users schema: \n" . $check);
		} catch( \Exception $e ) {
			$this->errorLogger->error( "\n------------create users schema error : \n");
			$this->errorLogger->error( $e->getMessage());
		}

		try {
			$this->infoLogger->info( "\ncreate items schema result : \n");
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => "$this->itemsSchemaName",
				'schema' => $schItem,
			])->wait();
			$this->infoLogger->info($result);
			$this->nameConfig->saveName('itemsSchemaName', $this->itemsSchemaName);
			$this->nameConfig->saveArn('itemsSchemaArn', $result['schemaArn']);
			$this->itemsSchemaArn = $result['schemaArn'];
			$check = $this->personalizeClient->{$this->apiDescribe}([
				'schemaArn' => $this->itemsSchemaArn,
			]);
			$this->infoLogger->info( "\ncheck items schema: \n" . $check);
		} catch( \Exception $e ) {
			$this->errorLogger->error( "\ncreate items schema error : \n" . $e->getMessage());
		}

		try {
			$this->infoLogger->info( "\ncreate interactions schema result : \n");
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => "$this->interactionsSchemaName",
				'schema' => $schInt,
			])->wait();
			$this->infoLogger->info($result);
			$this->nameConfig->saveName('interactionsSchemaName', $this->interactionsSchemaName);
			$this->nameConfig->saveArn('interactionsSchemaArn', $result['schemaArn']);
			$this->interactionsSchemaArn = $result['schemaArn'];
			$check = $this->personalizeClient->{$this->apiDescribe}([
				'schemaArn' => $this->interactionsSchemaArn,
			]);
			$this->infoLogger->info( "\ncheck interaction schema: \n" . $check);
		} catch( \Exception $e ) {
			$this->errorLogger->error( "\ncreate interactions schema error : \n" . $e->getMessage());
		}
	}
}
