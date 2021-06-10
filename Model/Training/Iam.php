<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use Aws\Iam\IamClient; 
Use Aws\Sts\StsClient;
use Aws\Exception\AwsException;

class Iam extends PersonalizeBase
{
	protected $nameConfig;
	protected $region;
	protected $varDir;
	protected $IamClient;
	protected $stsClient;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
	{
		parent::__construct($nameConfig);
		$this->region = $this->nameConfig->getAwsRegion();
		$this->varDir = $this->nameConfig->getVarDir();
		$this->IamClient =   new IamClient(
			[ 'profile' => 'default',
			'version' => 'latest',
			'region' => "$this->region" ]
		);
		$this->stsClient = new StsClient(
			[ 'profile' => 'default',
			'version' => 'latest',
			'region' => "$this->region" ]
		);

	}

	public function createPersonalizeS3Role() {
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
			)->wait();
			$this->infoLogger->info( $result);
			$this->errorLogger->error( $result);
			$this->nameConfig->saveArn('personalizeS3RoleArn', $result[0]['Arn']);
		} catch (AwsException $e) {
			$this->errorLogger->error( $e->getMessage());
		}

	}

    public function getStatus() {
        try {
            $arn = $this->nameConfig->getArn('userPolicyArn');
        } catch (\Exception $e) {
                $this->wizardTracking->setStepError('create_personalize_s3_role',$e->getMessage());
        }
        if(empty($arn)) {
            return 'not started';
        } else {
            return 'complete';
	}
    }

}
