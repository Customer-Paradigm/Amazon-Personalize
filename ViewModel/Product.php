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

namespace CustomerParadigm\AmazonPersonalize\ViewModel;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as productCollectionFactory;

use CustomerParadigm\AmazonPersonalize\Helper\Data;
use Magento\Catalog\Block\Product\Context;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Product extends DataObject implements ArgumentInterface
{
    /**
     * @param productCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable $configurable
     * @param Data $dataHelper
     */
    public function __construct(
        private readonly ProductCollectionFactory $productCollectionFactory,
        protected readonly ProductRepositoryInterface $productRepository,
        protected readonly Configurable $configurable,
        private readonly Data $dataHelper
    ) {
        parent::__construct();
    }

    /**
     * @param $idList
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProducts($idList): array
    {
        $rtn = [];
        foreach ($idList as $item) {
            $prod = $this->productRepository->getById($item['itemId']);
            $rtnItem = ["<br>" . $prod->getSku(), $prod->getName()];
            $rtn[] = $rtnItem;
        }
        return $rtn;
    }

    /**
     * @param $idList
     * @return mixed
     */
    public function getAllProductsFromRecommendations($idList)
    {
        $idArray = $this->dataHelper->getIdArrayFromItemList($idList);
        return $this->getProductCollection($idArray);
    }

    /**
     * @param $idArray
     * @return mixed
     */
    public function getProductCollection($idArray)
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('entity_id', ['in' => $idArray]);
        return $collection;
    }

    /**
     * @param $idArray
     * @param $count
     * @return mixed
     */
    public function getProductCollectionRand($idArray, $count = null)
    {
        $collection = $this->getProductCollection($idArray);
        if (!empty($count)) {
            $collection->setPageSize($count);
            $collection->setCurPage(1);
        }
        $collection->getSelect()->orderRand();
        return $collection;
    }

    /**
     * @param $idList
     * @param $current
     * @param $count
     * @return mixed
     */
    public function getViewableProducts($idList, $current, $count = 2000)
    {
        return $this->decideViewable($idList, $current, $count);
    }

    /**
     * @param $idList
     * @param $current
     * @param $count
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function decideViewable($idList, $current, $count)
    {
        $count = intval($count);
        $current = intval($current);
        $used_parent_ids = [];
        $idArray = [];

        // Create array of ids. Use id for simple product if it is viewable on the frontend,
        // use the id for the parent configurable if not.
        foreach ($idList as $item) {
            $prodId = intval($item['itemId']);
            if ($prodId == $current) {
                $used_parent_ids[] = $current;
                continue; // Don't include current product in suggestions.
            }

            // skip if product id from aws suggestion is not available on magento
            try {
                $prod = $this->productRepository->getById($prodId);
            } catch (\Exception $e) {
                continue;
            }

            if (!$prod->isInStock()) {
                continue;
            }

            $visible_text = $prod->getAttributeText('visibility');
            $visible = $visible_text == 'Not Visible Individually' ? false : true;

            // get parent if this product is not set to Visible
            if (! $visible) {
                $parentConfigIds = $this->configurable->getParentIdsByChild($prodId);
                if ($parentConfigIds) {
                    $conf_id = $parentConfigIds[0];
                    if ($current == $conf_id) {
                        $used_parent_ids[] = $current;
                        continue; // Don't include current product's parent in suggestions.
                    }
                    // disallow duplicates
                    if (! in_array($conf_id, $used_parent_ids)) {
                        $parent = $this->productRepository->getById($conf_id);
                        // Only include in stock items
                        if (!$parent->isInStock()) {
                            continue;
                        }

                        $idArray[] = $parent->getId();
                        $used_parent_ids[] = $parent->getId();
                    }
                }
            } elseif (in_array($prod->getId(), $used_parent_ids)) {
                continue;
            } else {
                $idArray[] = $prod->getId();
            }
            // Stop looking if you've got enough
            if (count($idArray) >= $count) {
                break;
            }
        }
        return $this->getProductCollection($idArray, $count);
    }
}
