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

class PersonalizeBase extends \Magento\Framework\Model\AbstractModel
{
    protected $nameConfig;
    protected $personalizeClient;
    protected $region;
    protected $varDir;
    protected $baseName;
    protected $sdkClient;
    protected $apiCreate;
    protected $apiUpdate;
    protected $apiDescribe;
    protected $infoLogger;
    protected $errorLogger;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
        \CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient
    ) {
        $this->baseName = (new \ReflectionClass($this))->getShortName();
        $this->apiCreate = 'create' . $this->baseName . 'Async';
        $this->apiUpdate = 'update' . $this->baseName . 'Async';
        $this->apiDescribe = 'describe' . $this->baseName;
        $this->nameConfig = $nameConfig;
        $this->region = $this->nameConfig->getAwsRegion();
        $this->infoLogger = $nameConfig->getLogger('info');
        $this->errorLogger = $nameConfig->getLogger('error');
        $this->sdkClient = $sdkClient;
        $this->personalizeClient = $this->sdkClient->getClient('Personalize');
    }

    public function checkAssetCreatedAndSync($type_name, $step_name, $name_value, $arn_value)
    {
        $this->infoLogger->info("\n--------checkAssetCreatedAndSync() function called---");
        $rtn = false;
        $step_plural = $step_name . "s";
        if ($this->assetExists($step_plural, $name_value, $arn_value)) {
            $name = $type_name.$step_name;
            $this->infoLogger->info("\n--------checkAssetCreatedAndSync() function name:\n" . $name);
            $rtn = true;
            if (empty($this->nameConfig->getConfigVal($name."Name"))) {
                $this->nameConfig->saveName($name."Name", $name_value);
            }
            if (empty($this->nameConfig->getConfigVal($name."Arn"))) {
                $this->nameConfig->saveArn($name."Arn", $arn_value);
            }
        }
        $this->infoLogger->info("\n--------checkAssetCreatedAndSync() function rtn:\n" . $rtn);
        return $rtn;
    }

    public function assetExists($type, $name, $arn = null)
    {
        try {
            $type = ucfirst($type);
            $func_name = "list" . $type;
            $args = ['maxResults'=>100];
            if (!empty($arn)) {
                $args = ['datasetArn'=>$arn];
            }
            $assets = $this->personalizeClient->$func_name($args);
            if (empty($assets)) {
                return false;
            }
            $type_key = null;
            foreach ($assets as $key => $unused) {
                $type_key = $key;
                break;
            }
            if (empty($type_key)) {
                return false;
            }
            foreach ($assets[$type_key] as $idx => $item) {
                if ($item['name'] === $name) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->errorLogger->error("\nassetExists() error. Message:\n" . print_r($e->getMessage(), true));
            return false;
        }
        return false;
    }

    public function getAssetArn($type, $name)
    {
        try {
            $type = ucfirst($type);
            $func_name = "list" . $type;

            $assets = $this->personalizeClient->$func_name(['maxResults'=>100]);
            if (empty($assets)) {
                return false;
            }
            $type_key = array_key_first($assets->toArray());
            foreach ($assets[$type_key] as $idx => $item) {
                if ($item['name'] === $name) {
                    return $item['eventTrackerArn'];
                }
            }
        } catch (Exception $e) {
            $this->errorLogger->error("\nassetExists() error. Message:\n" . print_r($e->getMessage(), true));
            return false;
        }
            return false;
    }
}
