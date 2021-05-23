<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

Use Aws\Personalize\PersonalizeClient;
Use Aws\Iam\IamClient;
Use Aws\Sts\StsClient;

class Test extends \Magento\Framework\App\Action\Action {

    protected $pRuntimeClient;
    protected $nameConfig;
    protected $personalizeBase;
    protected $personalizeClient;
    protected $iamClient;
    protected $stsClient;

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
	\CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper

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
        putenv("HOME=$this->homedir");

	parent::__construct($context);
        $this->region = $this->nameConfig->getAwsRegion();
	$this->stsClient = new StsClient(
		[ 'profile' => 'default',
		'version' => 'latest',
		'region' => "$this->region" ]
	);
	$this->personalizeClient = new PersonalizeClient(
		[ 'profile' => 'default',
		'version' => 'latest',
		'region' => "$this->region" ]
	);
/*
	$this->iamClient = new IamClient(
		[ 'profile' => 'default',
		'version' => 'latest',
		'region' => "$this->region"
	]);
*/
    }

    public function execute()
    {
	    /* Comment out this redirect to homepage to use the test controller 
            $resultRedirect = $this->resultRedirectFactory->create();
           $resultRedirect->setPath('');
	    return $resultRedirect;
	* */

// test IAM user s3 access
$this->createRole();
//$this->createRole();
		die('-----------------------');

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

    public function createRole() {
	try {
	$result = $this->stsClient->getSessionToken();
	$credentials = $this->stsClient->createCredentials($result);
	$this->iamClient = IamClient::factory(array(
	    'profile' => 'default',
	    'version' => 'latest',
	    'region' => "$this->region",
	    'credentials' => $credentials
    ));
	$result = $this->iamClient->createRole([
		'AssumeRolePolicyDocument' => '{"Version":"2012-10-17",
		"Statement":[{
			"Effect":"Allow",
			"Principal":{"Service":["personalize.amazonaws.com"]},
			"Action":["sts:AssumeRole"]}]}',
		'RoleName' => 'PersonalizeS3AcessRole'
	]
);
	/*
	    $result = $this->iamClient->createGroup(array(
		// UserName is required
		'GroupName' => 'PersonalizeGroup',
	));
	 */
	    var_dump($result);
	} catch (AwsException $e) {
	    // output error message if fails
	    error_log($e->getMessage());
	}
 
}
/*
    public function createRole() {

	    $roleName = 'PersonalizeAccessRole';

	    $description = 'An Instance role that has permission for Amazon s3 buckets.';

	    $PersonalizeAccessPolicy = '{
	    "Version": "2012-10-17",
		    "Statement": [
	    {
		    "Effect": "Allow",
			    "Action": [
				    "s3:Get*",
				    "s3:List*"
			    ],
			    "Resource": "*"
    }
    ]
    }';

	    $rolePolicy = '{
	    "Version": "2012-10-17",
		    "Statement": [
	    {
		    "Effect": "Allow",
			    "Principal": {
			    "Service": "s3.amazonaws.com"
    },
	    "Action": "sts:AssumeRole"
    }
  ]
    }';


	    try {
		    $iamPolicy = $this->iamClient->createPolicy([
			    'PolicyName' => $roleName . 'policy',
			    'PolicyDocument' => $PersonalizeAccessPolicy
		    ]);
		    if ($iamPolicy['@metadata']['statusCode'] == 200) {
			    $policyArn = $iamPolicy['Policy']['Arn'];
			    echo('<p> Your IAM Policy has been created. Arn -  ');
			    echo($policyArn);
			    echo('<p>');
			    $role = $this->iamClient->createRole([
				    'RoleName' => $roleName,
				    'Description' => $description,
				    'AssumeRolePolicyDocument' => $rolePolicy,
			    ]);
			    echo('<p> Your IAM User Role has been created. Arn: ');
			    echo($role['Role']['Arn']);
			    echo('<p>');
			    if ($role['@metadata']['statusCode'] == 200) {
				    $result = $this->iamClient->attachRolePolicy([
					    'PolicyArn' => $policyArn,
					    'RoleName' => $roleName,
				    ]);
				    var_dump($result);
			    } else {
				    echo('<p> There was an error creating your IAM User Role </p>');
				    var_dump($role);
			    }
		    } else {
			    echo('<p> There was an error creating your IAM Policy </p>');
			    var_dump($iamPolicy);

		    }
	    } catch (AwsException $e) {
		    // output error message if fails
		    echo $e;
		    error_log($e->getMessage());

	    }
    }
   */ 

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


