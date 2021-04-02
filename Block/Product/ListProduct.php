<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace CustomerParadigm\AmazonPersonalize\Block\Product;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\Result\PageFactory;
use CustomerParadigm\AmazonPersonalize\Helper\Data as PersonalizeHelper;
use CustomerParadigm\AmazonPersonalize\Price\Render;

/**
 * Product list
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class ListProduct extends \Magento\Catalog\Block\Product\ListProduct implements IdentityInterface
{
	/**
	 * Default toolbar block name
	 *
	 * @var string
	 */
	protected $_defaultToolbarBlock = Toolbar::class;

	/**
	 * Product Collection
	 *
	 * @var AbstractCollection
	 */
	protected $_productCollection;

	/**
	 * Catalog layer
	 *
	 * @var Layer
	 */
	protected $_catalogLayer;

	/**
	 * @var PostHelper
	 */
	protected $_postDataHelper;

	/**
	 * @var Data
	 */
	protected $urlHelper;

	/**
	 * @var CategoryRepositoryInterface
	 */
	protected $categoryRepository;

	/**
	 * @var PersonalizeHelper
	 */
	protected $personalizeHelper;

	/**
	 * @var PageFactory
	 */
	protected $pageFactory;


	/**
	 * @param Context $context
	 * @param PostHelper $postDataHelper
	 * @param Resolver $layerResolver
	 * @param CategoryRepositoryInterface $categoryRepository
	 * @param Data $urlHelper
	 * @param PersonalizeHelper $personalizeHelper
	 * @param PageFactory $pageFactory
	 * @param array $data
	 */
	public function __construct(
		Context $context,
		PostHelper $postDataHelper,
		Resolver $layerResolver,
		CategoryRepositoryInterface $categoryRepository,
		Data $urlHelper,
		PersonalizeHelper $personalizeHelper,
		PageFactory $pageFactory,
		array $data = []
	) {
		$this->_catalogLayer = $layerResolver->get();
		$this->_postDataHelper = $postDataHelper;
		$this->categoryRepository = $categoryRepository;
		$this->urlHelper = $urlHelper;
		$this->personalizeHelper = $personalizeHelper;
		$this->pageFactory = $pageFactory;
		parent::__construct(
			$context,
			$postDataHelper,
			$layerResolver,
			$categoryRepository,
			$urlHelper,
			$data
		);
	}

	public function setProductCollection($collection)
	{
		$this->_productCollection = $collection;
	}

	/**
	 * @param Product $product
	 * @return string
	 */
	public function getProductPrice(\Magento\Catalog\Model\Product $product)
	{
		$price = $product->getFinalPrice();
		if(floatval($price) <= 0) {
			$price_range = $this->personalizeHelper->getProductOptionsPriceRange($product);
			$price = "From $" . number_format($price_range['min'],2);
		} else {
			$price = "$" . number_format($price,2);
		}
		$ctnr = 
"<div class='price-box price-final_price' data-role='priceBox' data-product-id='14' data-price-box='product-id-14'>
	<span class='price-container price-final_price tax weee'>
        <span id='old-price-14-widget-product-grid' data-price-amount='45' data-price-type='finalPrice' class='price-wrapper '><span class='price'>$price</span></span>
        </span>
</div>";
		return $ctnr;
	}

	protected function _beforeToHtml()
	{   
		// left this blank to get rid of product name list that core
		// function prints out.
	}


}
