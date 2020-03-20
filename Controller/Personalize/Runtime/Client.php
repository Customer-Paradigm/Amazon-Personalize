<?php

namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize\Runtime;

//use \Aws\PersonalizeRuntime\PersonalizeRuntimeClient;

class Client extends \Magento\Framework\App\Action\Action {

    protected $pRuntimeClient;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \CustomerParadigm\AmazonPersonalize\Block\Product\ListProduct $listProduct,
        \CustomerParadigm\AmazonPersonalize\ViewModel\Product $prodViewModel,
        \CustomerParadigm\AmazonPersonalize\Api\Personalize\RuntimeClient $rtClient,
        \CustomerParadigm\AmazonPersonalize\Model\ResultFactory $awsResultFactory,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productFactory = $productFactory;
        $this->listProduct = $listProduct;
        $this->resultPageFactory = $resultPageFactory;
        $this->prodViewModel = $prodViewModel;
        $this->rtClient = $rtClient;
        $this->awsResultFactory = $awsResultFactory;
        $this->pConfig = $pConfig;
        $homedir = $this->pConfig->getUserHomeDir();
        putenv("HOME=$homedir");

        parent::__construct($context);
    }

    public function execute()
    {
        $post_params =   $this->getRequest()->getPost();
        $user_id = $post_params['userid'];
        // for testing
        // $user_id = '2';

        $recommend_result = $this->awsResultFactory->create()->getRecommendation($user_id);
  
        $productCollection = $this->prodViewModel->getViewableProducts($recommend_result,5);
        $response =  $this->resultJsonFactory->create();
        $resultPage = $this->resultPageFactory->create();

        $block = $resultPage->getLayout()
            ->createBlock("\CustomerParadigm\AmazonPersonalize\Block\Product\ListProduct")
            ->setTemplate("CustomerParadigm_AmazonPersonalize::catalog/product/list.phtml");
        $block->setProductCollection($productCollection);

        $response->setData($block->toHtml());
	return $response;
    }
}


