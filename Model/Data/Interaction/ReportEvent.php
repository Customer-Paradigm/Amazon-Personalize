<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Data\Interaction;

class ReportEvent extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'customerparadigm_amazonpersonalize_interaction_reportevent';

    protected $_cacheTag = 'customerparadigm_amazonpersonalize_interaction_reportevent';

    protected $_eventPrefix = 'customerparadigm_amazonpersonalize_interaction_reportevent';

    protected function _construct()
    {
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\ReportEvent');
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
