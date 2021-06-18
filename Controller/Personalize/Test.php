<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

Use Aws\Personalize\PersonalizeClient;
Use Aws\Iam\IamClient;
use Aws\S3\S3Client;

class Test extends \Magento\Framework\App\Action\Action {

    protected $pRuntimeClient;
    protected $nameConfig;
    protected $personalizeBase;
    protected $personalizeClient;
    protected $s3;
    protected $importJob;
    protected $stepsReset;
    protected $wizardTracking;

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
	\CustomerParadigm\AmazonPersonalize\Model\Training\StepsReset $stepsReset,
        \CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking $wizardTracking
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
        $this->stepsReset = $stepsReset;
        $this->wizardTracking = $wizardTracking;
        putenv("HOME=$this->homedir");

	parent::__construct($context);
        $this->region = $this->nameConfig->getAwsRegion();
	$this->personalizeClient = new PersonalizeClient(
		[ 'profile' => 'default',
		'version' => 'latest',
		'region' => "$this->region" ]
	);
        $this->iamClient = new IamClient(
                [ 'profile' => 'default',
                'version' => 'latest',
                'region' => "$this->region" ]
        );
        $this->s3Client =   new S3Client(
            [ 'profile' => 'default',
            'version' => 'latest',
            'region' => "$this->region" ]
        );
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
	$this->stepsReset->execute();
/*
	$this->listS3();
	$this->getBucketAcl('cprdgm-mage240-test-personalize-s3bucket');
	$this->listBucketContents('cprdgm-mage240-test-personalize-s3bucket');
	$this->listPers('Schemas');
	$this->listPers('DatasetGroups');
	$this->listPers('Datasets');
	$this->listPers('DatasetImportJobs');
	$this->listPers('Solutions');
	$this->listPers('SolutionVersions');
	$this->listPers('Campaigns');
//	$this->listPers('EventTrackers');
*/
	echo('done');
    }

    public function getBucketAcl($bucketName) {
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

    public function listBucketContents($bucketName) {
	    echo('<pre>');
	    var_dump($this->s3Client->listObjectsV2([
    		'Bucket' => "$bucketName", // REQUIRED
	    ]));
	    echo('</pre>');
    }
    
    public function deleteBucketContents($bucketName,$objectKey) {
	    echo('<pre>');
	    var_dump($this->s3Client->deleteObject([
    		'Bucket' => "$bucketName", // REQUIRED
    		'Key' => "$objectKey", // REQUIRED
	    ]));
	    echo('</pre>');
    }

    public function listS3() {
	    echo('<pre>');
	    var_dump($this->s3Client->listBuckets([]));
	    echo('</pre>');
	}
    
    public function listPers($type) {
	    echo('<pre>');
	    $func = 'list' . $type;
	    var_dump($this->personalizeClient->$func([]));
	    echo('</pre>');
	}

    protected function assetExists($type, $name) {
        try {
               // $assets = $this->personalizeClient->listSchemas(array('maxResults'=>100));
		$func_name = "list" . $type;
                $assets = $this->personalizeClient->$func_name(array('maxResults'=>100));
		if(empty($assets)) {
			return false;
		}
		$type_key = array_key_first($assets->toArray());
                foreach($assets[$type_key] as $idx=>$item) {
                        if($item['name'] === $name) {
                                return true;
                        }
                }
        } catch(Exception $e) {
                $this->errorLogger->error( "\nassetExists() error. Message:\n" . print_r($e->getMessage(),true));
                exit;
        }
        return false;
    }
}


