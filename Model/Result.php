<?php

namespace CustomerParadigm\AmazonPersonalize\Model;
use Aws\Exception\AwsException;
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;

class Result extends \Magento\Framework\Model\AbstractModel 
{
    const CACHE_TAG = 'customerparadigm_amazonpersonalize_result';

    protected $rtClient;
    protected $_cacheTag = 'customerparadigm_amazonpersonalize_result';
    protected $_eventPrefix = 'customerparadigm_amazonpersonalize_result';
    protected $campaignArn;
    protected $nameConfig;
    protected $dateTime;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Api\Personalize\RuntimeClient $rtClient,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
	\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
	\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
	\Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        array $data = []
    )
    {
        $this->rtClient = $rtClient;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
	$this->nameConfig = $nameConfig;
	$this->dateTime = $dateTime;
	$this->campaignArn = $this->nameConfig->getArn('campaignArn');
    }

    protected function _construct() { 
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Result');
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

    public function getSaved($item_type,$user_id) {
        $collection = $this->getCollection()
            ->addFieldToFilter('item_type', $item_type)
            ->addFieldToFilter('user_id', $user_id);
        return $collection->getFirstItem();
    }

    public function getRecommendation($user_id) {
        $data = [];
	$saved = $this->getSaved('recommendation', $user_id);
        if(!empty($saved->getData()) ) {
            $data = $this->updateData($saved);
        } else {
            $data = $this->saveData('recommendation',$user_id);
        }
        return $data;
    }

    public function saveData($item_type,$user_id) {
        $rslt = $this->rtClient->getRecommendations($this->campaignArn,$user_id);
        if( empty($rslt) ) {
            return array();
        }
        $rslt = $rslt->toArray();
        $item_list = json_encode($rslt["itemList"]);
        $this->setUserId($user_id);
        $this->setItemType($item_type);
        $this->setItemList($item_list);
        $this->save();
        return $rslt['itemList'];
    }
    
    public function updateData($saved) {
	$user_id = $saved->getUserId();

	$last_updated = date_create($saved->getUpdatedAt());
	$target_time = $last_updated->modify('+5 minutes');
	$current_time = $this->dateTime->gmtDate('Y-m-d H:i:s');
	$current = date_create($current_time);
	
	if($current < $target_time) {
        	return json_decode($saved->getItemList(),true);
	}
	
	$rslt = $this->rtClient->getRecommendations($this->campaignArn,$user_id);
	if( empty($rslt) ) {
        	return json_decode($saved->getItemList(),true);
	}
       
	$rslt = $rslt->toArray();
	$item_list = json_encode($rslt['itemList']);
	$predicted = $this->load($saved->getRecommendationId());
        $predicted->setItemList($item_list);
        $predicted->save();
        return $rslt['itemList'];
    }
}
