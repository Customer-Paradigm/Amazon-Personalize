<?php
namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Result;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'recommendation_id';
    protected $_eventPrefix = 'awspersonalize_result_collection';
    protected $_eventObject = 'result_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\Result', 'CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Result');
    }
}
