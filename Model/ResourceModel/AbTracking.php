<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel;

class AbTracking extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const CACHE_TAG = 'aws_ab_tracking';
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }
    
    protected function _construct()
    {
        $this->_init('aws_ab_tracking', 'ab_tracking_id');
    }
}
