<?php

namespace CustomerParadigm\AmazonPersonalize\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Db;
use CustomerParadigm\AmazonPersonalize\Helper\Aws;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;
use \Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;

class Data extends AbstractHelper
{

    protected $optionFactory;
    protected $cacheTypeList;
    protected $cacheFrontendPool;
    protected $resource;
    protected $db;
    protected $awsHelper;
    protected $infoLogger;
    protected $errorLogger;
    protected $scope;
    protected $connection;
    protected $configWriter;
    protected $storeManager;
    protected $inlineTranslation;
    protected $transportBuilder;

    public function __construct(
        Context $context,
        OptionFactory $optionFactory,
        TypeListInterface $cacheTypeList,
        Pool $cacheFrontendPool,
        ResourceConnection $resource,
        Db $db,
        Aws $awsHelper,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        StateInterface $state,
        TransportBuilder $transportBuilder
    ) {
        parent::__construct($context);
        $this->optionFactory = $optionFactory;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection();
        $this->db = $db;
        $this->awsHelper = $awsHelper;
        $this->infoLogger = $infoLogger;
        $this->errorLogger = $errorLogger;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->transportBuilder = $transportBuilder;
        $this->scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    }

    public function flushAllCache()
    {
        $_types = [
            'config',
            'layout',
            'block_html',
            'collections',
            'reflection',
            'db_ddl',
            'eav',
            'config_integration',
            'config_integration_api',
            'full_page',
            'translate',
            'config_webservice'
        ];

        foreach ($_types as $type) {
            $this->flushCacheType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

    public function flushCacheType($type)
    {
        $this->cacheTypeList->cleanType($type);
    }

    public function getIdArrayFromItemList($itemList)
    {
        $rtn = [];
        foreach ($itemList as $item) {
            $rtn[] = $item['itemId'];
        }

        return $rtn;
    }

    public function canDisplay()
    {
        return
            $this->scopeConfig->isSetFlag('awsp_settings/awsp_general/enable', $this->scope)
            && $this->scopeConfig->isSetFlag('awsp_settings/awsp_general/campaign_exists', $this->scope)
            && $this->db->enabled();
    }
    
    public function canDisplayAdmin()
    {
        $rtn = false;
        $mod_enabled = $this->scopeConfig->isSetFlag('awsp_settings/awsp_general/enable', $this->scope);
        $creds_saved = $this->scopeConfig->isSetFlag('awsp_settings/awsp_general/access_key', $this->scope);
        $is_ec2 = $this->awsHelper->isEc2Install();
        $db_enabled = $this->db->enabled();
        if ($is_ec2) {
            if ($mod_enabled && $db_enabled) {
                $rtn = true;
            }
        } else {
            if ($mod_enabled && $db_enabled && $creds_saved) {
                $rtn = true;
            }
        }

        return $rtn;
    }

    public function getProductOptionsPriceRange($product)
    {
        // set max to zero and min to high value. Min replaced by first iteration.
        $rtn = ['min'=>100000000,'max'=>0];
        $required = [];
        $simple = [];
        $customOptions = $this->optionFactory->create()->getProductOptionCollection($product);
        if (empty($customOptions)) {
            return $rtn;
        }

        foreach ($customOptions as $customOption) {
            $isRequired = $customOption->getIsRequire();
            $values = $customOption->getValues();
            $localmax = 0;
            $localmin = 100000000;
            if (empty($values)) {
                continue;
            }
            foreach ($values as $value) {
                $valueData = $value->getData();
                if (array_key_exists('price', $valueData)) {
                    $price = $valueData['price'];
                    // skip $0 price options
                    if ($price == 0) {
                        continue;
                    }
                    if ($isRequired) {
                        $localmin = min($price, $localmin);
                    } else {
                        $simple[] = $price;
                    }

                    $localmax = max($price, $localmax);
                }
            }
            if ($localmin < 100000000) {
                $required[] = $localmin;
            }
            $rtn['max'] += $localmax;
        }

        if (empty($required)) {
            $rtn['min'] = empty($simple) ? 0 : min($simple);
        } else {
            $rtn['min'] = array_sum($required);
        }

        // unset high min value if it hasn't changed.
        if ($rtn['min'] == "100000000") {
            $rtn['min'] = "0";
        }
        return $rtn;
    }

    public function getConfigValue($config_path)
    {
        $rtn = $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $rtn;
    }
    
    public function setConfigValue($config_path, $value)
    {
         $this->configWriter->save($config_path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
    }

    public function setStepError($step, $message)
    {
        $this->saveStepData($step, 'error', $message);
    }

    public function saveStepData($step_name, $set_column, $value)
    {
        $sql = "update aws_wizard_steps set $set_column = '$value' where step_name = '$step_name'";
        $this->connection->exec($sql);
    }
   /* 
    public function writeSetupVar($filename,$contents) {
	file_put_contents($filename,$contents);
}
    */

    public function sendEmail()
    {
        // this is an example and you can change template id,fromEmail,toEmail,etc as per your need.
        $templateId = 'sales_email_order_template'; // template id
        $fromEmail = 'owner@domain.com';  // sender Email id
        $fromName = 'Admin';             // sender Name
        $toEmail = 'scott.renick@customerparadigm.com'; // receiver email id
 
        try {
            // template variables pass here
            $templateVars = [
                'msg' => 'test',
                'msg1' => 'test1'
            ];
            $storeId = $this->storeManager->getStore()->getId();
 
            $from = ['email' => $fromEmail, 'name' => $fromName];
            $this->inlineTranslation->suspend();
 
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $transport = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($toEmail)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
        }
    }
}
