<?php
/**
 * CustomerParadigm_AmazonPersonalize
 *
 * @category   CustomerParadigm
 * @package    CustomerParadigm_AmazonPersonalize
 * @copyright  Copyright (c) 2023 Customer Paradigm (https://customerparadigm.com/)
 * @license    https://github.com/Customer-Paradigm/Amazon-Personalize/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\InteractionCheck;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'interaction_check_id';
    protected $_eventPrefix = 'aws_interaction_check';
    protected $_eventObject = 'interaction_check_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\InteractionCheck', 'CustomerParadigm\AmazonPersonalize\Model\ResourceModel\InteractionCheck');
    }
}
