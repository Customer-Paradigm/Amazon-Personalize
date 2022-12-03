<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel;

class InteractionCheck extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const CACHE_TAG = 'aws_interaction_check';
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('aws_interaction_check', 'interaction_check_id');
    }
}
