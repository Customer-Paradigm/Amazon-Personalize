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
			if( ! $this->alreadyCreated('users',$this->usersSchemaName,$this->usersSchemaArn) ) {
				$this->infoLogger->info( "\ncreate users schema result : \n");
				$result = $this->personalizeClient->{$this->apiCreate}([
					'name' => "$this->usersSchemaName",
					'schema' => $schUser,
				])->wait();
				$this->usersSchemaArn = $result['schemaArn'];
				$this->nameConfig->saveName('usersSchemaName', $this->usersSchemaName);
				$this->nameConfig->saveArn('usersSchemaArn', $this->usersSchemaArn);
			//	$this->infoLogger->info($result);
			}


		} catch( \Exception $e ) {
			$this->errorLogger->error( "\n------------create users schema error : \n");
			$this->errorLogger->error( $e->getMessage());
		}

		try {
                        if( ! $this->alreadyCreated('items',$this->itemsSchemaName,$this->itemsSchemaArn) ) {
                                $this->infoLogger->info( "\ncreate items schema result : \n");
                                $result = $this->personalizeClient->{$this->apiCreate}([
                                        'name' => "$this->itemsSchemaName",
                                        'schema' => $schUser,
                                ])->wait();
                                $this->itemsSchemaArn = $result['schemaArn'];
				$this->nameConfig->saveName('itemsSchemaName', $this->itemsSchemaName);
				$this->nameConfig->saveArn('itemsSchemaArn', $this->itemsSchemaArn);
                        //      $this->infoLogger->info($result);
                        }


		} catch( \Exception $e ) {
			$this->errorLogger->error( "\ncreate items schema error : \n" . $e->getMessage());
		}

		try {
                        if( ! $this->alreadyCreated('interactions',$this->interactionsSchemaName,$this->interactionsSchemaArn) ) {
                                $this->infoLogger->info( "\ncreate interactions schema result : \n");
                                $result = $this->personalizeClient->{$this->apiCreate}([
                                        'name' => "$this->interactionsSchemaName",
                                        'schema' => $schUser,
                                ])->wait();
                                $this->interactionsSchemaArn = $result['schemaArn'];
				$this->nameConfig->saveName('interactionsSchemaName', $this->interactionsSchemaName);
				$this->nameConfig->saveArn('interactionsSchemaArn', $this->interactionsSchemaArn);
                        //      $this->infoLogger->info($result);
                        }

		} catch( \Exception $e ) {
			$this->errorLogger->error( "\ncreate interactions schema error : \n" . $e->getMessage());
		}
	}

	public function alreadyCreated($name,$schemaName,$schemaArn) {
			$this->infoLogger->info( "\n--Hit--alreadyCreated(): \n");
			$rtn = false;
			$check = $this->personalizeClient->{$this->apiDescribe}([
				'schemaArn' => $schemaArn,
			]);
			//$this->infoLogger->info( "\ncheck users schemaExists: \n" . print_r($check,true));
			if(!empty($check) ) {
				$rtn = true;
				$this->nameConfig->saveName($name."SchemaName", $schemaName);
				$this->nameConfig->saveArn($name."SchemaArn", $schemaArn);
			}
			return $rtn;
	}
}
