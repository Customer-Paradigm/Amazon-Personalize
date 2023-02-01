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

namespace CustomerParadigm\AmazonPersonalize\Model;

class Error extends \Magento\Framework\Model\AbstractModel
{
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
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

    public function clearData()
    {
        $connection = $this->getResource()->getConnection();
        $tableName = $this->getResource()->getMainTable();
        $connection->truncateTable($tableName);
    }

    public function writeError($e, $type = null)
    {
        $mssg = $e['formatted'];
        $type = empty($type) ? $e['level_name'] : $type;
        $this->addData([
            'error_type' => $type,
            'error_message' => $mssg
        ]);
        $this->save();
    }

    public function getAllErrors()
    {
        $rtn = [];
        $coll = $this->getCollection();
        foreach ($coll as $item) {
            $data = $item->getData();
            $rtn[] = ['date' => $data['created_at'], 'error_message' => $data['error_message'] ];
        }
        return $rtn;
    }
}
