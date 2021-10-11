<?php
namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction;

class ReportEvent extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_eavAttribute;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute
    ) {
        $this->_eavAttribute = $eavAttribute;
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('report_event', 'event_id');
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        $attributeIdName = $this->_eavAttribute->getIdByCode('catalog_product', 'name');
        $attributeIdPrice = $this->_eavAttribute->getIdByCode('catalog_product', 'price');

        $field = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
        $select = $this->getConnection()
            ->select()
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
            ->where($field . '=?', $value)
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
        return $select;
    }
}
