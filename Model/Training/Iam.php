<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use Aws\Iam\IamClient; 
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;

class Iam
{
    private $logger;
    protected $nameConfig;
    protected $region;
    protected $varDir;
    protected $IamClient;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
    )
    {
        $this->logger = $logger;
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
			$this->logger->error('Aws List Users Error:', ['exception' => $e]);
		}
    }
}
