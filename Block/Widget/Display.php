<?php

namespace CustomerParadigm\AmazonPersonalize\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;

class Display  extends \Magento\Catalog\Block\Product\AbstractProduct implements BlockInterface{

    protected $_template = "widget/recommendations.phtml";
    protected $customerSession;
    protected $pHelper;
    protected $awsEvents;
    protected $pConfig;
    protected $data;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \CustomerParadigm\AmazonPersonalize\ViewModel\Product $prodViewModel,
        \CustomerParadigm\AmazonPersonalize\Model\ResultFactory $awsResultFactory,
        \CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper,
        \CustomerParadigm\AmazonPersonalize\Model\Events $awsEvents,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->prodViewModel = $prodViewModel;
        $this->awsResultFactory = $awsResultFactory;
        $this->pHelper = $pHelper;
        $this->awsEvents = $awsEvents;
        $this->pConfig = $pConfig;
    }

    public function getUserId() {
        $customer = $this->customerSession->create();
        $id = $customer->getId();
        $id = empty($id) ? $customer->getSessionId() : $id;
        return $id;
    }

    public function isControlUser() {
	$user_type = $this->awsEvents->personalizeAbType();
        return $user_type === 'control';
    }

    public function getUserType() {
        return $this->awsEvents->personalizeAbType();
    }

    public function isEnabled() {
        return $this->pHelper->canDisplay();
    }
    
    public function canDisplay() {
        $display = true;
        if( ! $this->isEnabled() ) { 
            $display = false; 
        } elseif($this->pConfig->getGaAbEnabled() && $this->isControlUser() ) { 
            $display = false; 
        }
        return $display;
    }

    public function getRecommendationHtml() {
        $user_id = $this->getUserId();
	$recommend_result = $this->awsResultFactory->create()->getRecommendation($user_id);
        $productCollection = $this->prodViewModel->getViewableProducts($recommend_result, $this->getCount());
        $resultPage = $this->resultPageFactory->create();

        $block = $resultPage->getLayout()
            ->createBlock("\CustomerParadigm\AmazonPersonalize\Block\Product\ListProduct")
            ->setTemplate("CustomerParadigm_AmazonPersonalize::catalog/product/list.phtml");
        $block->setProductCollection($productCollection);

        return $block->toHtml();     
    }
}
