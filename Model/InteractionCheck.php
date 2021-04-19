<?php

namespace CustomerParadigm\AmazonPersonalize\Model;

class InteractionCheck extends \Magento\Framework\Model\AbstractModel 
{
	const CACHE_TAG = 'customerparadigm_amazonpersonalize_interactioncheck';
	protected $_cacheTag = 'customerparadigm_amazonpersonalize_interactioncheck';
	protected $_eventPrefix = 'customerparadigm_amazonpersonalize_interactioncheck';
	protected $pConfig;

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []
	)
	{
		parent::__construct($context, $registry, $resource, $resourceCollection, $data);
		$this->pConfig = $pConfig;
	}

	protected function _construct() { 
		$this->_init('CustomerParadigm\AmazonPersonalize\Model\ResourceModel\InteractionCheck');
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

	public function saveEvent($event){
		$user_id = $event["userId"];
		$props = json_decode($event["eventList"][0]["properties"]);
		$item_id = $props->itemId;
		$event_type = $event["eventList"][0]["eventType"];
		$timestamp = $event["eventList"][0]["sentAt"];
		$this->setUserId($user_id);
		$this->setItemId($item_id);
		$this->setEventType($event_type);
		$this->setTimestamp($timestamp);
		$rslt = $this->save();
		return $rslt;
	}	

	public function clearData() {
		$connection = $this->getResource()->getConnection();
		$tableName = $this->getResource()->getMainTable();
		$connection->truncateTable($tableName);
	}
}
