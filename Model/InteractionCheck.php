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

namespace CustomerParadigm\AmazonPersonalize\Model;

class InteractionCheck extends \Magento\Framework\Model\AbstractModel
{
    public const CACHE_TAG = 'customerparadigm_amazonpersonalize_interactioncheck';
    protected $_cacheTag = 'customerparadigm_amazonpersonalize_interactioncheck';
    protected $_eventPrefix = 'customerparadigm_amazonpersonalize_interactioncheck';

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
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

    public function saveEvent($event)
    {
        $user_id = $event["userId"];
        $props = $event["eventList"][0]["properties"];
        $decode = json_decode($props);
        if (!empty($decode)) {
            $item_id = $decode->itemId;
        } else { // json_decode choked on a string format, try another way
            $arr = explode(',', $props);
            $arr2 = explode(':', $arr[1]);
            $item_id = $arr2[1];
        }
        $event_type = $event["eventList"][0]["eventType"];
        $timestamp = $event["eventList"][0]["sentAt"];
        $this->setUserId($user_id);
        $this->setItemId($item_id);
        $this->setEventType($event_type);
        $this->setTimestamp($timestamp);
        $rslt = $this->save();
        return $rslt;
    }

    public function clearData()
    {
        $connection = $this->getResource()->getConnection();
        $tableName = $this->getResource()->getMainTable();
        $connection->truncateTable($tableName);
    }

    public function getTotal()
    {
        return $this->getCollection()->getSize();
    }
}
