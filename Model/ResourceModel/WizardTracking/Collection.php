<?php
namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\WizardTracking;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'wizard_tracking_id';
	protected $_eventPrefix = 'awspersonalize_wizard_collection';
	protected $_eventObject = 'wizardtracking_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking', 'CustomerParadigm\AmazonPersonalize\Model\ResourceModel\WizardTracking');
	}

}
