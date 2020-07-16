<?php

namespace CustomerParadigm\AmazonPersonalize\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
use Magento\Framework\App\Config\ScopeConfigInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Db;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;



class Data extends AbstractHelper {

    protected $optionFactory;
    protected $cacheTypeList;
    protected $cacheFrontendPool;
    protected $db;
    protected $infoLogger;
    protected $errorLogger;
    protected $scope;

    public function __construct( 
        Context $context,
        OptionFactory $optionFactory,
		TypeListInterface $cacheTypeList, 
        Pool $cacheFrontendPool,
        Db $db,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger
    ) {
        parent::__construct($context);
        $this->optionFactory = $optionFactory;
		$this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->db = $db;
        $this->infoLogger = $infoLogger;
        $this->errorLogger = $errorLogger;
        $this->scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
    }

    public function flushAllCache() {
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
    
	public function flushCacheType($type) {
		$this->cacheTypeList->cleanType($type);
    }

    public function getIdArrayFromItemList($itemList) {
        $rtn = array();
        foreach( $itemList as $item ) {
            $rtn[] = $item['itemId'];
        }

        return $rtn;
    }

    public function canDisplay() {
        return 
            $this->scopeConfig->isSetFlag( 'awsp_settings/awsp_general/enable', $this->scope )
            && $this->scopeConfig->isSetFlag( 'awsp_settings/awsp_general/campaign_exists', $this->scope )
            //&& $this->db->enabled('inst');
            && $this->db->enabled();
    }

    public function getProductOptionsPriceRange($product) {
        // set max to zero and min to high value. Min replaced by first iteration.
        $rtn = array('min'=>100000000,'max'=>0);
        $required = array();
        $simple = array();
        $customOptions = $this->optionFactory->create()->getProductOptionCollection($product);

        foreach ($customOptions as $customOption) {
            $isRequired = $customOption->getIsRequire();
            $values = $customOption->getValues();
            $localmax = 0;
            $localmin = 100000000;
            foreach ($values as $value) {
                $valueData = $value->getData();
                if(array_key_exists('price', $valueData)) {
                    $price = $valueData['price'];
                    // skip $0 price options
                    if($price == 0) {
                        continue;
                    }
                    if( $isRequired ) {
                        $localmin = min($price,$localmin);
                    } else {
                        $simple[] = $price;
                    }

                    $localmax = max($price,$localmax);
                }
            }
            if($localmin < 100000000) {
                $required[] = $localmin;
            } 
            $rtn['max'] += $localmax;
        }

        if( empty($required) ) {
            $rtn['min'] = min($simple);
        } else {
            $rtn['min'] = array_sum($required);
        }

        // unset high min value if it hasn't changed.
        if($rtn['min'] == "100000000") {
            $rtn['min'] = "0";
        }
        return $rtn;
    }

    public function getConfigValue($config_path){
		$rtn = $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
       ); 
       return $rtn;
    }

}
