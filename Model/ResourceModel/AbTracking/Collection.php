<?php
namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\AbTracking;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'ab_tracking_id';
	protected $_eventPrefix = 'awspersonalize_ab_collection';
	protected $_eventObject = 'abtracking_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('CustomerParadigm\AmazonPersonalize\Model\AbTracking', 'CustomerParadigm\AmazonPersonalize\Model\ResourceModel\AbTracking');
	}

}
