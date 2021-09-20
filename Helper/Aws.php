<?php

namespace CustomerParadigm\AmazonPersonalize\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\HTTP\Client\Curl;
use \Magento\Framework\App\Config\Storage\WriterInterface;

class Aws extends AbstractHelper {

        protected $scopeConfig;
        protected $storeManager;
	protected $configWriter;
        protected $curl;
        protected $storeId;

	public function __construct( 
		Context $context,
		ScopeConfigInterface $scopeConfig,
		StoreManagerInterface $storeManager,
		Curl $curl,
		WriterInterface $configWriter
	) {
		parent::__construct($context);
		$this->scopeConfig = $scopeConfig;
		$this->storeManager = $storeManager;
		$this->storeId = $this->storeManager->getStore()->getId();
		$this->curl = $curl;
		$this->configWriter = $configWriter;
	}

        public function isEc2Install(){
          $check = $this->scopeConfig->getValue('awsp_settings/awsp_general/ec2_install',
                  \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
          if($check === "" || $check === NULL) {
                  $URL = 'http://169.254.169.254/latest/user-data';
                  $this->curl->setOption(CURLOPT_HEADER, 0);
                  $this->curl->setOption(CURLOPT_TIMEOUT, 3);
                  $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
                  $this->curl->get($URL);
                  $response = $this->curl->getBody();
                  $findLightsail = strpos($response,'Lightsail');
                  if($response === false || $findLightsail !== false) {
                          $this->configWriter->save('awsp_settings/awsp_general/ec2_install', 0, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,$this->storeId);
                          return false;
                  } else {
                          $this->configWriter->save('awsp_settings/awsp_general/ec2_install', 1, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT,$this->storeId);
                          return true;
                  }
          }
          return $check;
        }
}
