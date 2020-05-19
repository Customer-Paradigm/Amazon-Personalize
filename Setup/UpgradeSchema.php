<?php
namespace CustomerParadigm\AmazonPersonalize\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements  UpgradeSchemaInterface
{

	public function upgrade(SchemaSetupInterface $setup,
		ModuleContextInterface $context){
		$setup->startSetup();
		if (version_compare($context->getVersion(), '1.0.1') < 0) {

			// Get module table
			$tableName = $setup->getTable('aws_predicted_items');

			// Check if the table already exists
			if ($setup->getConnection()->isTableExists($tableName) == true) {

				$connection = $setup->getConnection();
				$connection->modifyColumn(
					$tableName,
					'user_id',
					[
						'type' => Table::TYPE_TEXT,
						255,
						['nullable' => false]
					]
				);

			}
		}

		/**
		 * Create table to track personalize/control type users for ab testing
		 */
		if (version_compare($context->getVersion(), '1.0.2') < 0) {
			$installer = $setup;
			$installer->startSetup();

			$table = $installer->getConnection()->newTable($installer->getTable('aws_ab_tracking')		
			)->addColumn(
				'ab_tracking_id',
				Table::TYPE_INTEGER,
				null,
				['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
				'Recommendation Id'
			)->addColumn(
				'customer_session_id',
				Table::TYPE_TEXT,
				255,
				['nullable' => false],
				'Customer Session Id'
			)->addColumn(
				'using_personalize',
				Table::TYPE_BOOLEAN,
				1,
				['nullable' => false],
				'Using Personalize'
			)->addColumn(
				'created_at',
				Table::TYPE_TIMESTAMP,
				null,
				['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
				'Created At'
			);
			$installer->getConnection()->createTable($table);
			$installer->endSetup();
		}	

		/**
		 * Create sales_order columns for ab test user type attribute
		 */
		if (version_compare($context->getVersion(), '1.0.4') < 0) {
			$installer = $setup;
            $installer->startSetup();
			$installer->getConnection()->addColumn(
				$installer->getTable('sales_order_grid'),
				'ab_customer_type',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'length' => 255,
					'comment' =>'A/B Test customer type'
				]
            );
            
            $installer->getConnection()->addColumn(
				$installer->getTable('sales_order'),
				'ab_customer_type',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'length' => 255,
					'comment' =>'A/B Test customer type'
				]
			);

			$installer->endSetup();
		}
        
        if (version_compare($context->getVersion(), '1.0.5') <= 0) {
            $installer = $setup;
            $installer->startSetup();

            $table = $installer->getConnection()->newTable($installer->getTable('aws_wizard_steps')
            )->addColumn(
                'wizard_step_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Step Id'
            )->addColumn(
                'step_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Step Name'
            )->addColumn(
                'in_progress',
                Table::TYPE_BOOLEAN,
                1,
                ['nullable' => true, 'default' => NULL],
                'Step in progress'
            )->addColumn(
                'is_completed',
                Table::TYPE_BOOLEAN,
                1,
                ['nullable' => true, 'default' => NULL],
                'Step Complete'
            )->addColumn(
                'error',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => NULL],
                'Error Message'
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

        /**
         * Create table to track errors
         */
        if (version_compare($context->getVersion(), '1.0.6') < 0) {
            $installer = $setup;
            $installer->startSetup();

            $table = $installer->getConnection()->newTable($installer->getTable('aws_errors')
            )->addColumn(
                'error_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Error table index id'
            )->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Aws operation name'
            )->addColumn(
                'type',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Aws operation type'
            )->addColumn(
                'aws_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Aws operation id'
            )->addColumn(
                'arn',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Aws arn'
            )->addColumn(
                'role_arn',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Aws role arn'
            )->addColumn(
                'aws_message',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Aws error message'
            )->addColumn(
                'magento_class_info',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Magento class/function call'
            )->addColumn(
                'magento_message',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Magento error message'
            )->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            );
            $installer->getConnection()->createTable($table);
            $installer->endSetup();
        }

        /**
         * Create sales_order columns for ab test user type attribute
         */
        if (version_compare($context->getVersion(), '1.0.7') < 0) {
            $installer = $setup;
            $installer->startSetup();
            $installer->getConnection()->addColumn(
                $installer->getTable('aws_wizard_steps'),
                'attempt_number',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'length' => 255,
                    'comment' =>'Number of attempts for this step'
                ]
            );

            $installer->endSetup();
        }


	}
}
