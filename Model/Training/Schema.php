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
    protected $wizardTracking;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
        \CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking $wizardTracking,
        \CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient
    ) {
        parent::__construct($nameConfig, $sdkClient);
        $this->wizardTracking = $wizardTracking;
        $this->infoLogger = $nameConfig->getLogger('info');
        $this->errorLogger = $nameConfig->getLogger('error');
        $this->usersSchemaName = $this->nameConfig->buildName('users-schema');
        $this->itemsSchemaName = $this->nameConfig->buildName('items-schema');
        $this->interactionsSchemaName = $this->nameConfig->buildName('interactions-schema');
        $this->usersSchemaArn = $this->nameConfig->buildArn('schema', $this->usersSchemaName);
        $this->itemsSchemaArn = $this->nameConfig->buildArn('schema', $this->itemsSchemaName);
        $this->interactionsSchemaArn = $this->nameConfig->buildArn('schema', $this->interactionsSchemaName);
        $this->usersConfigName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/usersSchemaName');
        $this->itemsConfigName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/itemsSchemaName');
        $this->interactionsConfigName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_name/interactionsSchemaName');
    }

    public function getStatus()
    {
        $checklist = [];
        if ($rtn = $this->schemaExists($this->usersSchemaName)) {
            $checklist[] = $rtn;
        }
        if ($rtn = $this->schemaExists($this->itemsSchemaName)) {
            $checklist[] = $rtn;
        }
        if ($rtn = $this->schemaExists($this->interactionsSchemaName)) {
            $checklist[] = $rtn;
        }

        switch (true) {
            case (count($checklist) == 3):
                return 'complete';
                break;
            case (count($checklist) == 0):
                return 'not started';
                break;
            case (count($checklist) > 0 && count($checklist) < 3):
                return 'in progress';
                break;
            default:
                return  'not defined';
        }
    }

    public function createSchemas()
    {
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
            if (! $this->checkAssetCreatedAndSync('users', 'Schema', $this->usersSchemaName, $this->usersSchemaArn)) {
                $result = $this->personalizeClient->{$this->apiCreate}([
                    'name' => "$this->usersSchemaName",
                    'schema' => $schUser,
                ])->wait();
                $this->usersSchemaArn = $result['schemaArn'];
                $this->nameConfig->saveName('usersSchemaName', $this->usersSchemaName);
                $this->nameConfig->saveArn('usersSchemaArn', $this->usersSchemaArn);
            }
        } catch (\Exception $e) {
            $this->errorLogger->error("\ncreate users schema error: " . print_r($e->getMessage(), true));
        }

        try {
            if (! $this->checkAssetCreatedAndSync('items', 'Schema', $this->itemsSchemaName, $this->itemsSchemaArn)) {
                $result = $this->personalizeClient->{$this->apiCreate}([
                        'name' => "$this->itemsSchemaName",
                        'schema' => $schItem,
                ])->wait();
                $this->itemsSchemaArn = $result['schemaArn'];
                $this->nameConfig->saveName('itemsSchemaName', $this->itemsSchemaName);
                $this->nameConfig->saveArn('itemsSchemaArn', $this->itemsSchemaArn);
            }
        } catch (\Exception $e) {
            $this->errorLogger->error("\ncreate items schema error: " . print_r($e->getMessage(), true));
        }

        try {
            if (! $this->checkAssetCreatedAndSync('interactions', 'Schema', $this->interactionsSchemaName, $this->interactionsSchemaArn)) {
                $result = $this->personalizeClient->{$this->apiCreate}([
                        'name' => "$this->interactionsSchemaName",
                        'schema' => $schInt,
                ])->wait();
                $this->interactionsSchemaArn = $result['schemaArn'];
                $this->nameConfig->saveName('interactionsSchemaName', $this->interactionsSchemaName);
                $this->nameConfig->saveArn('interactionsSchemaArn', $this->interactionsSchemaArn);
            }
        } catch (\Exception $e) {
            $this->errorLogger->error("\ncreate interactions schema error: " . print_r($e->getMessage(), true));
        }
    }

    public function schemaExists($schemaName)
    {
        return $this->assetExists('Schemas', $schemaName);
    }
}
