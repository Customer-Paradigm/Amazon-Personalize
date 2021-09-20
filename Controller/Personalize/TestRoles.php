<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

class TestRoles extends \Magento\Framework\App\Action\Action {

    protected $pRuntimeClient;
    protected $nameConfig;
    protected $personalizeBase;
    protected $personalizeClient;
    protected $iam;
    protected $wizardTracking;
    protected $sdkClient;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \CustomerParadigm\AmazonPersonalize\Block\Product\ListProduct $listProduct,
        \CustomerParadigm\AmazonPersonalize\ViewModel\Product $prodViewModel,
        \CustomerParadigm\AmazonPersonalize\Api\Personalize\RuntimeClient $rtClient,
        \CustomerParadigm\AmazonPersonalize\Model\ResultFactory $awsResultFactory,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        \CustomerParadigm\AmazonPersonalize\Block\Widget\Display $prodDisplay,
	\CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper,
	\CustomerParadigm\AmazonPersonalize\Model\Training\Iam $iam,
	\CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking $wizardTracking,
        \CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productFactory = $productFactory;
        $this->listProduct = $listProduct;
        $this->resultPageFactory = $resultPageFactory;
        $this->prodViewModel = $prodViewModel;
        $this->rtClient = $rtClient;
        $this->awsResultFactory = $awsResultFactory;
        $this->pConfig = $pConfig;
        $this->nameConfig = $nameConfig;
        $this->prodDisplay = $prodDisplay;
        $this->pHelper = $pHelper;
        $this->homedir = $this->pConfig->getUserHomeDir();
        $this->iam = $iam;
        $this->wizardTracking = $wizardTracking;
        $this->sdkClient = $sdkClient;
        putenv("HOME=$this->homedir");

        parent::__construct($context);
	$this->region = $this->nameConfig->getAwsRegion();
        $this->personalizeClient = $this->sdkClient->getClient('Personalize');
    }

    public function execute()
    {
/* Comment out this redirect to homepage to use the test controller 
*/
            $resultRedirect = $this->resultRedirectFactory->create();
           $resultRedirect->setPath('');
	    return $resultRedirect;

	//$this->testAssumeRole();
	$this->testListRoles();
        echo("\n--------end tests---------");
    }

    public function testAssumeRole() {
	echo("<pre><div>assume role</div>");
        var_dump($this->iam->assumeRole());
	echo("</pre>");
    }
    
    public function testListRoles() {
	echo("<pre><div>list roles</div>");
        $this->iam->assumeRole();
        var_dump($this->iam->listRoles());
	echo("</pre>");
    }
}
