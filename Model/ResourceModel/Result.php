<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel;

class Result extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const CACHE_TAG = 'aws_predicted_items';
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('aws_predicted_items', 'recommendation_id');
    }
}
