<?php                   
namespace CustomerParadigm\AmazonPersonalize\Api;
/*
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;
Use Aws\Personalize\PersonalizeClient;
Use Aws\Iam\IamClient;
use Aws\S3\S3Client;
 */
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class AwsSdkClient implements AwsSdkClientInterface {

	protected $scopeConfig;
	protected $storeId;
	protected $region;

	public function __construct(
		ScopeConfigInterface $scopeConfig,
		StoreManagerInterface $storeManager
	) {
		$this->scopeConfig = $scopeConfig;
		$this->storeManager = $storeManager;
		$this->storeId = $this->storeManager->getStore()->getId();
		$this->region = $this->scopeConfig->getValue('awsp_settings/awsp_general/aws_region',
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
	
		// Set default region for setup:upgrade, when admin settings are not yet populated
		if(empty($this->region)) {
			$this->region = "us-east-1";
		}

	}

	public function getClient($type) {
		$classname = $this->getClientClass($type);
		$client = new $classname(
			[ 
			//'profile' => 'default',
			'version' => 'latest',
			'region' => "$this->region" ]
		);
		return $client;
	}

	public function getAwsRegion() {
		return $this->region;
	}
	
	public function getScopeConfig() {
		return $this->scopeConfig;
	}

	protected function getClientClass($typename) {
		$rtn = '';
		switch(strtolower($typename)) {
			case 'personalizeruntime': $rtn = 'Aws\PersonalizeRuntime\PersonalizeRuntimeClient'; break;
			case 'personalize': $rtn = 'Aws\Personalize\PersonalizeClient'; break;
			case 'iam': $rtn = 'Aws\Iam\IamClient'; break;
			case 'sts': $rtn = 'Aws\Sts\StsClient'; break;
			case 's3': $rtn = 'Aws\S3\S3Client'; break;
		}
		return $rtn;
	}
		
}
