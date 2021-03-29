<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

Use Aws\Personalize\PersonalizeClient;

class Test extends \Magento\Framework\App\Action\Action {

    protected $pRuntimeClient;
    protected $nameConfig;
    protected $personalizeBase;
    protected $personalizeClient;

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
        $this->nameConfig = $nameConfig;
        $homedir = $this->pConfig->getUserHomeDir();
        putenv("HOME=$homedir");

	parent::__construct($context);
        $this->region = $this->nameConfig->getAwsRegion();
	$this->personalizeClient = new PersonalizeClient(
		[ 'profile' => 'default',
		'version' => 'latest',
		'region' => "$this->region" ]
	);
    }

    public function execute()
    {
/* Comment out this redirect to homepage to use the test controller */
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('');
            return $resultRedirect;


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


