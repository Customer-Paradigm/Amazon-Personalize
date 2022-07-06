<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;
use \Magento\Framework\App\Config\Storage\WriterInterface;
//use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;
use \Magento\Framework\App\Filesystem\DirectoryList;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Helper\Aws;
use CustomerParadigm\AmazonPersonalize\Model\InteractionCheck;
use CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient;

class NameConfig extends PersonalizeConfig
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $configWriter;
    protected $scopeConfig;
    protected $pRuntimeClient;
    protected $errorLogger;
    protected $infoLogger;
    protected $sdkClient;
    protected $storeManager;
    protected $store;
    protected $directoryList;
    protected $helper;

    public function __construct(
        WriterInterface $configWriter,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        Data $helper,
        Aws $awsHelper,
        InteractionCheck $interactionCheck,
        AwsSdkClient $sdkClient
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $this->getScopeConfig();
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->helper = $helper;
        $this->awsHelper = $awsHelper;
        $this->store = $this->storeManager->getStore();
        parent::__construct($configWriter, $infoLogger, $errorLogger, $storeManager, $directoryList, $helper, $awsHelper, $interactionCheck, $sdkClient);
    }

    public function buildName($type)
    {
	    $storeName = $this->getStoreName();
	    if($type === 'personalize-s3bucket') {
		    // force default scope number for s3 bucket name	
		    $storeName = preg_replace('/-1/', '-0', $storeName);
	    }
	    else { // replace period with underscore for all but S3 bucket name
		    $storeName = preg_replace('/\./', '_', $storeName);
	    }
	    return 'cprdgm-' . $storeName . '-' . $type;
    }
    
    public function buildArn($type, $name, $suffix = null)
    {
        $prefix = "arn:aws:personalize:";
        $region = $this->getAwsRegion();
        $acct = $this->getAwsAccount();
        $rtn = $prefix . "$region:" . "$acct:" . "$type/" . $name;
        if ($suffix) {
            $rtn .= "/$suffix";
        }
        return $rtn;
    }

    public function saveName($type_name, $value)
    {
        $this->configWriter->save("awsp_wizard/data_type_name/$type_name", $value);
    }
    
    public function saveArn($arn_name, $value)
    {
        $this->configWriter->save("awsp_wizard/data_type_arn/$arn_name", $value);
    }
    
    public function getConfigVal($config_path)
    {
        return $this->scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function getArn($arn_name)
    {
        $config_path = "awsp_wizard/data_type_arn/$arn_name";
        return $this->scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    
    public function getName($name)
    {
        $config_path = "awsp_wizard/data_type_name/$name";
        return $this->scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    public function getVarDir()
    {
        return $this->directoryList->getPath('var');
    }
}
