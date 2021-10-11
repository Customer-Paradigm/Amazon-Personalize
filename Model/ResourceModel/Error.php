<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel;

class Error extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const CACHE_TAG = 'aws_errors';
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }
    
    protected function _construct()
    {
        $this->_init('aws_errors', 'error_id');
    }
}
