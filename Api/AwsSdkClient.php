<?php                   
namespace CustomerParadigm\AmazonPersonalize\Api;
                        
Use Aws\Personalize\PersonalizeClient;
Use Aws\Iam\IamClient;
use Aws\S3\S3Client;


class AwsSdkClient implements AwsSdkClientInterface {

	protected $nameConfig;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	) {
		$this->nameConfig = $nameConfig;
		$this->region = $this->nameConfig->getAwsRegion();
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

	protected function getClientClass($typename) {
		$rtn = '';
		switch($typename) {
			case 'Personalize': $rtn = 'Aws\Personalize\PersonalizeClient'; break;
			case 'Iam': $rtn = 'Aws\Iam\IamClient'; break;
			case 'S3': $rtn = 'Aws\S3\S3Client'; break;
		}
		return $rtn;
	}
		
}
