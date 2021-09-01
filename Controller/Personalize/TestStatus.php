<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

Use Aws\Personalize\PersonalizeClient;

class TestStatus extends \Magento\Framework\App\Action\Action {

    protected $pRuntimeClient;
    protected $nameConfig;
    protected $personalizeBase;
    protected $personalizeClient;
    protected $s3;
    protected $schema;
    protected $dataset;
    protected $importjob;
    protected $solution;
    protected $solutionVersion;
    protected $errorModel;
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
        \CustomerParadigm\AmazonPersonalize\Model\Training\s3 $s3,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Schema $schema,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Dataset $dataset,
        \CustomerParadigm\AmazonPersonalize\Model\Training\ImportJob $importjob,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Solution $solution,
        \CustomerParadigm\AmazonPersonalize\Model\Training\SolutionVersion $solutionVersion,
        \CustomerParadigm\AmazonPersonalize\Model\Error $errorModel,
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
        $this->s3 = $s3;
        $this->schema = $schema;
        $this->dataset = $dataset;
        $this->importjob = $importjob;
        $this->solution = $solution;
        $this->solutionVersion = $solutionVersion;
        $this->errorModel = $errorModel;
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
/*
            $resultRedirect = $this->resultRedirectFactory->create();
           $resultRedirect->setPath('');
            return $resultRedirect;
 */
        $this->s3Status();
        $this->uploadStatus();
        $this->schemaStatus();
        $this->datasetStatus();
        $this->importJobStatus();
        $this->solutionStatus();
        $this->solutionVersionStatus();
        echo("\n--------end tests---------");
    }

    public function s3Status() {
        echo("<pre><div>s3Status</div>");
        var_dump($this->s3->checkBucketExists());
        echo("</pre>");
    }

    public function uploadStatus() {
        echo("<pre><div>uploadStatus</div>");
        var_dump($this->s3->getUploadStatus());
        echo("</pre>");
    }

    public function schemaStatus() {
        echo("<pre><div>schemaStatus</div>");
        var_dump($this->schema->getStatus());
        echo("</pre>");
    }

    public function datasetStatus() {
        echo("<pre><div>datasetStatus</div>");
        var_dump($this->dataset->getStatus());
        echo("</pre>");
    }

    public function importJobStatus() {
        echo("<pre><div>importJobStatus</div>");
        var_dump($this->importjob->getStatus());
        echo("</pre>");
    }

    public function solutionStatus() {
        echo("<pre><div>importJobStatus</div>");
        var_dump($this->solution->getStatus());
        echo("</pre>");
    }

    public function solutionVersionStatus() {
        echo("<pre><div>importJobStatus</div>");
        var_dump($this->solutionVersion->getStatus());
        echo("</pre>");
    }
}
