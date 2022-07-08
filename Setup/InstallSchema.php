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
use CustomerParadigm\AmazonPersonalize\Helper\Aws;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{

	protected $awsHelper;

    public function __construct(
        Aws $awsHelper
    ) {
        $this->awsHelper = $awsHelper;
    }


    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
	$this->awsHelper->populateEc2CheckVal();
    }
}
