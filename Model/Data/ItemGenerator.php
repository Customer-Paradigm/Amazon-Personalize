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

namespace CustomerParadigm\AmazonPersonalize\Model\Data;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\WriteFactory;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;

class ItemGenerator extends \CustomerParadigm\AmazonPersonalize\Model\Data\AbstractGenerator
{
    /*
     * Array containing csv header keys
     */
    protected $csvHeaders = [
        "ITEM_ID",
        "PRICE",
        "WEIGHT",
        "CATEGORIES"
    ];

    protected $filename = "items";
    protected $infoLogger;
    protected $errorLogger;

    private $productCollectionFactory;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        WriteFactory $writeFactory,
        DirectoryList $directoryList,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger,
        File $file
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($writeFactory, $directoryList, $file);
        $this->infoLogger = $infoLogger;
        $this->errorLogger = $errorLogger;
    }

    private function getItemDataFromProduct($product)
    {
        $data = [];
        $data[] = $this->parseNullData($product->getId());
        $data[] = $this->parseNullData((float)$product->getFinalPrice());
        $data[] = $this->parseNullData($product->getWeight());
        $data[] = $this->parseNullData($this->getCategoryDataFromProduct($product));

        return $data;
    }

    private function getCategoryDataFromProduct($product)
    {
        $categories = $product->getCategoryCollection()->addAttributeToSelect('*');

        $categoryData = "";
        foreach ($categories as $category) {
            $categoryData .= $category->getName() . '|';
        }
        $categoryData = rtrim($categoryData, '|');
        if (empty($categoryData)) {
            $categoryData = 'none';
        }

        return $categoryData;
    }

    private function writeProductsToCsv($products)
    {
        foreach ($products as $product) {
            $this->writer->writeCsv($this->getItemDataFromProduct($product));
        }

        return $this;
    }

    public function generateCsv()
    {
        try {
            $products = $this->productCollectionFactory->create()->addAttributeToSelect('*');

            $count = count($products);

            $this->createWriter()
                ->writeHeadersToCsv()
                ->writeProductsToCsv($products)
                ->closeWriter();
        } catch (Exception $e) {
            $mssg = $e->getMessage();
            $this->errorLogger->error("Items Generator Processing error: $mssg");
        }

        return $this;
    }
}
