<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use Aws\Iam\IamClient; 
use Aws\Exception\AwsException;

class Iam
{
    private $errorLogger;
    protected $nameConfig;
    protected $region;
    protected $varDir;
    protected $IamClient;

    public function __construct(
	    \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
	    \CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger $errorLogger
    )
    {
        $this->errorLogger = $errorLogger;
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
		} catch (AwsException $e) {
			$this->errorLogger->error('Aws List Users Error:', ['exception' => $e]);
		}
    }
}
