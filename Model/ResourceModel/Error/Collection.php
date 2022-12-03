<?php

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Error;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'error_id';
    protected $_eventPrefix = 'aws_error';
    protected $_eventObject = 'aws_error__collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\Error', 'CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Error');
    }
}
