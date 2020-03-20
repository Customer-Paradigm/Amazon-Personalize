<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use Aws\Iam\IamClient; 
use Aws\Exception\AwsException;

class Iam
{
    protected $nameConfig;
    protected $region;
    protected $varDir;
    protected $IamClient;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
    )
    {
        $this->nameConfig = $nameConfig;
        $this->region = $this->nameConfig->getAwsRegion();
		$this->varDir = $this->nameConfig->getVarDir();
        $this->IamClient =   new IamClient(
            [ 'profile' => 'default',
            'version' => 'latest',
            'region' => "$this->region" ]
		);
    }

    public function listUsers() {
		try {
			$result = $this->IamClient->listUsers();
			var_dump($result);
		} catch (AwsException $e) {
			echo($e->getMessage());
		}
    }
}
