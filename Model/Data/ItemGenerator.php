<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Data;

use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\WriteFactory;

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

    private $productCollectionFactory;

    public function __construct(
        CollectionFactory $productCollectionFactory,
        WriteFactory $writeFactory,
        DirectoryList $directoryList,
        File $file
    ){
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($writeFactory, $directoryList, $file);
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
        $categoryData = rtrim($categoryData,'|');

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
        $products = $this->productCollectionFactory->create()->addAttributeToSelect('*');

        $count = count($products);
        file_put_contents('/home/demo/public_html/pargolf/var/log/test.log',"\n Product Count: $count", FILE_APPEND);

        $this->createWriter()
            ->writeHeadersToCsv()
            ->writeProductsToCsv($products)
            ->closeWriter();

        return $this;
    }
}
