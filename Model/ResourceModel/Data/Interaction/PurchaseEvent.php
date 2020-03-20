<?php
namespace CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction;


class PurchaseEvent extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('sales_order_item', 'unused_id');
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        $field = $this->getConnection()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
        $select = $this->getConnection()
            ->select()
            ->from(['main_table' => $this->getMainTable()],
                [
                    'user_id' => 'sales_order.customer_id',
                    'item_id' => 'main_table.product_id',
                    'event_type' => new \Zend_Db_Expr("'checkout_purchase_product'"),
                    'timestamp' => 'UNIX_TIMESTAMP(main_table.updated_at)'
                ]
            )
            ->where($field . '=?', $value)
            ->join('sales_order',
                'main_table.order_id = sales_order.entity_id', []);

        return $select;
    }
}
