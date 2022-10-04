<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Asset;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'config_id';
    protected $_eventPrefix = 'aws_asset';
    protected $_eventObject = 'aws_asset__collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\Asset', 'CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Asset');
    }
}
