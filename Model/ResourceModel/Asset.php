<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel;

class Asset extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public const CACHE_TAG = 'aws_asset';
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('core_config_data', 'config_id');
    }
}
