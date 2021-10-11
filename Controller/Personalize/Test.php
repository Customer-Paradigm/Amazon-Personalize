<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

class Test extends \Magento\Framework\App\Action\Action
{

    protected $pRuntimeClient;
    protected $nameConfig;
    protected $personalizeBase;
    protected $personalizeClient;
    protected $s3;
    protected $iam;
    protected $importJob;
    protected $stepsReset;
    protected $errorModel;
    protected $assetModel;
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
        \CustomerParadigm\AmazonPersonalize\Model\Training\Iam $iam,
        \CustomerParadigm\AmazonPersonalize\Model\Training\ImportJob $importJob,
        \CustomerParadigm\AmazonPersonalize\Model\Training\StepsReset $stepsReset,
        \CustomerParadigm\AmazonPersonalize\Model\Error $errorModel,
        \CustomerParadigm\AmazonPersonalize\Model\Asset $assetModel,
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
        $this->iam = $iam;
        $this->importJob = $importJob;
        $this->stepsReset = $stepsReset;
        $this->errorModel = $errorModel;
        $this->assetModel = $assetModel;
        $this->wizardTracking = $wizardTracking;
        $this->sdkClient = $sdkClient;
        putenv("HOME=$this->homedir");

        parent::__construct($context);
        $this->region = $this->nameConfig->getAwsRegion();
        $this->personalizeClient = $this->sdkClient->getClient('Personalize');
        $this->iamClient = $this->sdkClient->getClient('Iam');
        $this->s3Client = $this->sdkClient->getClient('S3');
        $this->stsClient = $this->sdkClient->getClient('sts');
    }

    public function execute()
    {
        var_dump($this->nameConfig->getStoreName());
/* Comment out this redirect to homepage to use the test controller
*/
            $resultRedirect = $this->resultRedirectFactory->create();
           $resultRedirect->setPath('');
        return $resultRedirect;
//    $this->testAssetModel();
    //$this->testEmail();
//    $this->errorModel->getAllErrors();
//    $this->stepsReset->execute();
    //$this->testIam();
        $this->testGetAccount();
    //$this->listS3();
    //$this->getS3Status();
    //$this->listBucketContents('cprdgm-mage240-test-personalize-s3bucket');
    /*
var_dump($this->getBucketAcl('cprdgm-mage240-test-personalize-s3bucket'));
$this->listPers('Schemas');
     $this->listPers('DatasetGroups');
    $this->listPers('Datasets');
    $this->listPers('DatasetImportJobs');
    $this->listPers('Solutions');
    $this->listPers('SolutionVersions');
     */
//    $this->listPers('Campaigns');
        /*
    $this->listPers('EventTrackers');
*/
        echo('done');
    }

    public function testGetAccount()
    {
        echo('<pre>');
        var_dump($this->stsClient->GetCallerIdentity()['Account']);
        echo('</pre>');
    }

    public function testIam()
    {
        echo('<pre>');
        var_dump($this->iamClient->getUser(['personalize']));
        echo('</pre>');
    }

    public function testS3Upload()
    {
        try {
            $this->s3->uploadCsvFiles();
        } catch (\Exception $e) {
            // output error message if fails
            var_dump($e->getMessage());
            echo "\n";
        }
    }

    public function getBucketAcl($bucketName)
    {
        try {
            $resp = $this->s3Client->getBucketAcl([
            'Bucket' => $bucketName
            ]);
            echo "Succeed in retrieving bucket ACL as follows: \n";
            var_dump($resp);
        } catch (AwsException $e) {
            // output error message if fails
            echo $e->getMessage();
            echo "\n";
        }
    }

    public function listBucketContents($bucketName)
    {
        echo('<pre>');
        var_dump($this->s3Client->listObjectsV2([
            'Bucket' => "$bucketName", // REQUIRED
        ]));
        echo('</pre>');
    }
    
    public function deleteBucketContents($bucketName, $objectKey)
    {
        echo('<pre>');
        var_dump($this->s3Client->deleteObject([
            'Bucket' => "$bucketName", // REQUIRED
            'Key' => "$objectKey", // REQUIRED
        ]));
        echo('</pre>');
    }

    public function getS3Status()
    {
        echo('<pre>');
        var_dump($this->s3->getUploadStatus());
        echo('</pre>');
    }

    public function listS3()
    {
        echo('<pre>');
        var_dump($this->s3Client->listBuckets([]));
    //    var_dump($this->s3->listS3Buckets([]));
        echo('</pre>');
    }
    
    public function listPers($type)
    {
        echo('<pre>');
        $func = 'list' . $type;
        var_dump($this->personalizeClient->$func([]));
        echo('</pre>');
    }

    public function testEmail()
    {
        $this->pHelper->sendEmail();
    }

    public function testAssetModel()
    {
        $rtn = $this->assetModel->getPublicAwsAssetsDisplayData();
        echo('<pre>');
        print_r($rtn);
        echo('</pre>');
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
