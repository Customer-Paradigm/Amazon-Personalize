<?php

namespace CustomerParadigm\AmazonPersonalize\Model;

class Asset extends \Magento\Framework\Model\AbstractModel 
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
	\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
	\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct() { 
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Asset');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }

    public function getAllConfigDataArray()
    {
        $rtn = array();
        $coll = $this->getCollection();
        foreach($coll as $item) {
                $data = $item->getData();
                $rtn[] = array( 'config_id' => $data['config_id'], 
				'scope' => $data['scope'] ,
				'scope_id' => $data['scope_id'],
				'path' => $data['path'],
				'value' => $data['value'],
				'updated_at' => $data['updated_at']
			);
            }
        return $rtn;
    }
    
    public function getAllAwsSettings() {
    	$coll = $this->getCollection();

	$coll->addFieldToFilter('path', ['like' => '%awsp_%']);
	return $coll;
    }
    
   public function getAwsAssets() {
	$coll = $this->getCollection();

	$coll->addFieldToFilter('path', ['like' => '%awsp_wizard%data_type_%']);
	return $coll;
    }
    
    public function getPublicAwsSettings() {
        $coll = $this->getCollection();
	$coll->addFieldToFilter('path',['like' => '%awsp_settings%']);
	$coll->addFieldToFilter('path', ['nlike' => '%_key']);
	$coll->addFieldToFilter('path', ['nlike' => '%css_%']);
	$coll->addFieldToFilter('path', ['nlike' => '%rule_%']);
	$coll->addFieldToFilter('path', ['nlike' => '%crontab%']);
	$coll->addFieldToFilter('path', ['nlike' => '%calc_error']);
	$coll->addFieldToFilter('path', ['nlike' => '%calc_coupon']);
	$coll->addFieldToFilter('path', ['nlike' => '%home_dir']);
//	$coll->addFieldToFilter('path', ['nlike' => '%aws_acct']);
	$coll->addFieldToFilter('path', ['nlike' => '%log_display']);
	$coll->addFieldToFilter('path', ['nlike' => '%last_val']);
	return $coll;
    }
    
    public function getAwsDisplayData() {
	    $assets = $this->getAssetDisplayData($this->getAwsAssets());
	    $settings = $this->getSettingDisplayData($this->getPublicAwsSettings());
	    return array( 'assets'=>$assets, 'settings'=>$settings );
    }

    protected function getSettingDisplayData($collection) {
	    $rtn = array();
	    foreach($collection as $item) {
		$data = $item->getData();
		$updated_at = '';
		// for Magento 2.3.x backward compatibility
		if(array_key_exists('upated_at',$data)) {
			$updated_at = $data['updated_at'];
		}
		
		$rtn[] = array( 
				'name' => $this->mapSettingDisplayName($data['path']),
				'path' => $data['path'],
				'value' => $data['value'],
				'updated_at' => $updated_at
			);
            }
	return $rtn;
    }
    
    protected function getAssetDisplayData($collection) {
	    $rtn = array();
	    $rtn[] = ['name'=>"Name",'path'=>"Config Path",'value'=>"Value",'updated_at'=>"Last Updated"];
	    foreach($collection as $item) {
                $data = $item->getData();
                $rtn[] = array( 
				'name' => $this->mapAssetDisplayName($data['path']),
				'path' => $data['path'],
				'value' => $data['value'],
				'updated_at' => $data['updated_at']
			);
            }
	return $rtn;
    }
    
    protected function mapSettingDisplayName($path) {
	    $rtn = '';
	    $origName = $this->getNamePathPart($path);
	    switch($origName) {
	    case "calc_active": 
		$rtn = "License Active";
	    	break;
	    case "enable": 
		$rtn = "Module Enabled";
	    	break;
	    case "aws_region": 
		$rtn = "Aws Region";
	    	break;
	    case "data_range": 
		$rtn = "Data Range";
	    	break;
	    case "abtest_enable": 
		$rtn = "A/B Testing Enabled";
	    	break;
	    case "percentage": 
		$rtn = "A/B Testing Percentage";
	    	break;
	    case "order-interactions-count": 
		$rtn = "Order Interactions Count";
	    	break;
	    case "campaign_exists": 
		$rtn = "Campaign Is Created";
	    	break;
	    case "file-interactions-count": 
		$rtn = "File Interactions Count";
		break;
	    default:
		$rtn = $origName;
	    }
	    return $rtn;
    }

    protected function mapAssetDisplayName($path) {
	    $arr = preg_split('/(?=[A-Z])/',$this->getNamePathPart($path));
	    $words = ucfirst(implode(' ',$arr));
	    return $words;
    }

    protected function getNamePathPart($path) {
	    $parts = explode('/',$path);
	    return $parts[2];
    }
}
