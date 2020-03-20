<?php namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class Schema extends PersonalizeBase
{
	protected $usersSchemaName;
	protected $itemsSchemaName;
	protected $interactionsSchemaName;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
    {
        parent::__construct($nameConfig);
        $this->usersSchemaName = $this->nameConfig->buildName('users-schema');
        $this->itemsSchemaName = $this->nameConfig->buildName('items-schema');
        $this->interactionsSchemaName = $this->nameConfig->buildName('interactions-schema');
    }

    public function getStatus() {
        $checkArray = array($this->usersSchemaName,$this->itemsSchemaName,$this->interactionsSchemaName);
        $checklist = array();
        $rtn = $this->personalizeClient->listSchemas();
        try {
            foreach($rtn['schemas'] as $item) {
                if(in_array($item['name'],$checkArray)) {
                    $checklist[] = 'check';
                }
            }
            if(count($checklist) == 3) {
                return 'complete';
            }
            if(count($checklist) == 0) {
                return 'not started';
            }
            if(count($checklist) > 0 && count($checklist) < 3) {
                return 'in progress';
            }
        } catch(\Exception $e) {
            return 'error';
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
		$result = $this->personalizeClient->{$this->apiCreate}([
			'name' => "$this->usersSchemaName",
			'schema' => $schUser,
		])->wait();
		$this->nameConfig->saveName('usersSchemaName', $this->usersSchemaName);
		$this->nameConfig->saveArn('usersSchemaArn', $result['schemaArn']);
	} catch(\Exception $e) {
		$this->nameConfig->getLogger()->error( "\ncreate users schema error : \n" . $e->getMessage());
	}

    try {
		$result = $this->personalizeClient->{$this->apiCreate}([
			'name' => "$this->itemsSchemaName",
			'schema' => $schItem,
		])->wait();
		$this->nameConfig->saveName('itemsSchemaName', $this->itemsSchemaName);
		$this->nameConfig->saveArn('itemsSchemaArn', $result['schemaArn']);
	} catch(\Exception $e) {
		$this->nameConfig->getLogger()->error( "\ncreate items schema error : \n" . $e->getMessage());
	}

    try {
		$result = $this->personalizeClient->{$this->apiCreate}([
			'name' => "$this->interactionsSchemaName",
			'schema' => $schInt,
		])->wait();
		$this->nameConfig->saveName('interactionsSchemaName', $this->interactionsSchemaName);
		$this->nameConfig->saveArn('interactionsSchemaArn', $result['schemaArn']);
	} catch(\Exception $e) {
		$this->nameConfig->getLogger()->error( "\ncreate interactions schema error : \n" . $e->getMessage());
	}
  }
}
