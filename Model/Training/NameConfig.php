<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;
use Psr\Log\LoggerInterface;
use \Magento\Framework\App\Filesystem\DirectoryList;
use CustomerParadigm\AmazonPersonalize\Helper\Data;

class NameConfig extends PersonalizeConfig
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $configWriter;
    protected $scopeConfig;
    protected $pRuntimeClient;
    protected $logger;
    protected $storeManager;
    protected $store;
    protected $directoryList;
    protected $helper;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        Data $helper
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->helper = $helper;
        $this->store = $this->storeManager->getStore();
        parent::__construct($configWriter, $scopeConfig, $logger, $storeManager, $directoryList, $helper);
    }

    public function buildName($type) {
        $storeName = $this->scopeConfig->getValue( 'general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->store->getId() );
        $storeName = trim($storeName); // clean leading/trailing spaces
        $storeName = preg_replace('/[^A-Za-z0-9\-\s]/', '', $storeName); // Removes special chars.
        $storeName = explode(' ', $storeName);
        $storeName = array_slice($storeName, 0, 3);
        $storeName = implode('-', $storeName);
        $storeName = strtolower($storeName);
        
        return $storeName . '-' . $type;
    }
    
    public function buildArn($type,$name,$suffix = null) {
        // identify this a cparadigm arn
        $name  'cparadigm-' . $name;
        $prefix = "arn:aws:personalize:";
        $region = $this->getAwsRegion();
        $acct = $this->decryptAwsAccount();
        $rtn = $prefix . "$region:" . "$acct:" . "$type/" . $name;
        if( $suffix ) {
            $rtn .= "/$suffix";
        }
        return $rtn;
    }

    public function saveName($type_name, $value) {
        $this->configWriter->save("awsp_wizard/data_type_name/$type_name", $value);
    }
    
    public function saveArn($arn_name, $value) {
        $this->configWriter->save("awsp_wizard/data_type_arn/$arn_name", $value);
    }
    
    public function getConfigVal($config_path) {
        return $this->scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    
    public function getArn($arn_name) {
        $config_path = "awsp_wizard/data_type_arn/$arn_name";
        return $this->scopeConfig->getValue( $config_path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }
    
    public function getName($name) {
        $config_path = "awsp_wizard/data_type_name/$name";
        return $this->scopeConfig->getValue( $config_path, \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    public function getVarDir() {
        return $this->directoryList->getPath('var');
    }
}
