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

namespace CustomerParadigm\AmazonPersonalize\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\Config\Storage\WriterInterface;

class Aws extends AbstractHelper
{
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

    public function populateEc2CheckVal()
    {
        $check = $this->scopeConfig->getValue(
            'awsp_settings/awsp_general/ec2_install',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            0
        );

        // If value already exists, bypass the curl check
        if ($check === "" || $check === null) {
            try {
                //$URL = 'http://169.254.169.254/latest/user-data';
                $URL = 'http://169.254.169/latest/user-data';
                $this->curl->setOption(CURLOPT_HEADER, 0);
                $this->curl->setOption(CURLOPT_TIMEOUT, 3);
                $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
                $this->curl->get($URL);
                $response = $this->curl->getBody();
                $findLightsail = strpos($response, 'Lightsail');
                if ($response === false || $findLightsail !== false) {
                    // We want store scope to always be default(0) on this config value
                    $this->configWriter->save('awsp_settings/awsp_general/ec2_install', 0, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                } else {
                    // We want store scope to always be default(0) on this config value
                    $this->configWriter->save('awsp_settings/awsp_general/ec2_install', 1, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
                }
            } catch(\Exception $e) {
                // Set value to 0 if curl call errors
                $this->configWriter->save('awsp_settings/awsp_general/ec2_install', 0, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            }
        }
    }

    public function isEc2Install()
    {
        // We want store scope to always be default(0) on this config value
        $installedVal = $this->scopeConfig->getValue(
            'wsp_settings/awsp_general/ec2_install',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            0
        );
        return $installedVal;
    }
}
