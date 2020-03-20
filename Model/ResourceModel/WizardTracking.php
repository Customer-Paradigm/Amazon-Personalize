<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel;

class WizardTracking extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	const CACHE_TAG = 'aws_ab_tracking';
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('aws_wizard_steps', 'wizard_step_id');
	}

}
