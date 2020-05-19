<?php

namespace CustomerParadigm\AmazonPersonalize\Model;

class Error extends \Magento\Framework\Model\AbstractModel 
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->pConfig = $pConfig;
    }

    protected function _construct() { 
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Error');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
}
