<?php
/**
 * @author Customer Paradigm Team
 * @copyright Copyright (c) 2018 Customer Paradigm (https://www.customerparadigm.com)
 * @package CustomerParadigm_Schematics
 */


namespace CustomerParadigm\AmazonPersonalize\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig
     */
    protected $pConfig;

    public function __construct(
        PersonalizeConfig $pConfig
    ) {
        $this->pConfig = $pConfig;
		// initialize aws cred directory cron on install
		$this->pConfig->setCron('aws_set_cli','on');
        $storename = $this->pConfig->getStoreName();
        $id = $this->pconfig->getRuleId($storename);
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer->getConnection()->newTable($installer->getTable('aws_predicted_items')
        )->addColumn(
            'recommendation_id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Recommendation Id'
        )->addColumn(
            'user_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'User Id'
        )->addColumn(
            'item_type',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false], 
            'Item Type'
        )->addColumn(
            'item_list',
            Table::TYPE_TEXT,
            '2M',
            ['nullable' => false],
            'Item List'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Updated At'
        );
        $installer->getConnection()->createTable($table);
         $installer->endSetup();

    }
}
