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

namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\ReportEvent;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_eventPrefix = 'customerparadigm_amazonpersonalize_reportevent_collection';
    protected $_eventObject = 'amazonpersonalize_reportevent_collection';

    protected $_eavAttribute;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->_eavAttribute = $eavAttribute;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    protected function _construct()
    {
        $this->_init(
            'CustomerParadigm\AmazonPersonalize\Model\Data\Interaction\ReportEvent',
            'CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\ReportEvent'
        );
    }

    protected function _initSelect()
    {
        $attributeIdName = $this->_eavAttribute->getIdByCode('catalog_product', 'name');
        $attributeIdPrice = $this->_eavAttribute->getIdByCode('catalog_product', 'price');

        $this->getSelect()
            ->from(
                ['main_table' => $this->getMainTable()],
                [
                    'user_id' => new \Zend_Db_Expr(
                        'if(customer_visitor.customer_id is null, customer_visitor.session_id, customer_visitor.customer_id)'
                    ),
                    'item_id' => 'catalog_product_entity.entity_id',
                    'event_type' => 'report_event_types.event_name',
                    'timestamp' => 'UNIX_TIMESTAMP(main_table.logged_at)',
                ]
            )
            ->join(
                'customer_visitor',
                'main_table.subject_id = customer_visitor.visitor_id',
                []
            )
            ->join(
                'report_event_types',
                'main_table.event_type_id = report_event_types.event_type_id',
                []
            )
            ->joinLeft(
                'catalog_product_entity',
                'main_table.object_id = catalog_product_entity.entity_id',
                []
            )
            ->joinLeft(
                'catalog_product_entity_decimal',
                'catalog_product_entity.entity_id = catalog_product_entity_decimal.entity_id',
                []
            )
            ->joinLeft(
                'catalog_product_entity_varchar',
                'catalog_product_entity.entity_id = catalog_product_entity_varchar.entity_id',
                []
            )
            ->joinLeft(
                'customer_entity',
                'customer_visitor.customer_id = customer_entity.entity_id',
                []
            )
            ->where('catalog_product_entity_decimal.attribute_id =?', $attributeIdPrice)
            ->where('catalog_product_entity_varchar.attribute_id =?', $attributeIdName);
        return $this;
    }
}
