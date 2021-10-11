<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

class TestPolicy extends \Magento\Framework\App\Action\Action
{

    protected $pRuntimeClient;
    protected $nameConfig;
    protected $personalizeBase;
    protected $personalizeClient;
    protected $s3;
    protected $importJob;

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
        \CustomerParadigm\AmazonPersonalize\Model\Training\ImportJob $importJob,
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
        $this->importJob = $importJob;
        $this->sdkClient = $sdkClient;
        putenv("HOME=$this->homedir");

        parent::__construct($context);
        $this->region = $this->nameConfig->getAwsRegion();
        $this->personalizeClient = $this->sdkClient->getClient('Personalize');
        $this->iamClient = $this->sdkClient->getClient('Iam');
        $this->s3Client = $this->sdkClient->getClient('S3');
    }

    public function execute()
    {
/* Comment out this redirect to homepage to use the test controller
*/
            $resultRedirect = $this->resultRedirectFactory->create();
           $resultRedirect->setPath('');
        return $resultRedirect;
        try {
        //    foreach($buckets['Buckets'] as $bucket) {
            $policy = $this->s3Client->getBucketPolicy([
                'Bucket' => 'calibrated-power-solutions-personalize-s3bucket'
            ]);
            $location = $this->s3Client->getBucketLocation([
                'Bucket' => 'calibrated-power-solutions-personalize-s3bucket'
            ]);
            echo "Bucket policy:\n";
            echo (string) $policy->get('Policy');
            echo "Location:\n";
            var_dump($location->get('LocationConstraint'));
            echo "\n";

        } catch (AwsException $e) {
            // output error message if fails
            var_dump($e->getMessage());
        }
        die("\n---------End");

/*
    // test non existant config value
        $itemsSchemaName = $this->nameConfig->getConfigVal('awsp_wizard/data_type_arn/itemsDatasetArn');
    // test assetExists function
        var_dump($this->assetExists('Datasets', 'cprdgm-mage240-test-users-dataset'));
        var_dump($this->assetExists('Datasets', 'cprdgm-mage240-test-items-dataset'));
        $schemas = $this->personalizeClient->listSchemas(array('maxResults'=>100));
        echo('<pre>');
        var_dump($schemas);
        echo('</pre>');
        die("<br>----hit test");
 */
    }

    protected function assetExists($type, $name)
    {
        try {
               // $assets = $this->personalizeClient->listSchemas(array('maxResults'=>100));
            $func_name = "list" . $type;
                $assets = $this->personalizeClient->$func_name(['maxResults'=>100]);
            if (empty($assets)) {
                return false;
            }
            $type_key = array_key_first($assets->toArray());
            foreach ($assets[$type_key] as $idx => $item) {
                if ($item['name'] === $name) {
                        return true;
                }
            }
        } catch (Exception $e) {
                $this->errorLogger->error("\nassetExists() error. Message:\n" . print_r($e->getMessage(), true));
                exit;
        }
        return false;
    }
}
