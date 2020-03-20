<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class PersonalizeBase extends \Magento\Framework\Model\AbstractModel
{
	protected $nameConfig;
	protected $personalizeClient;
	protected $region;
	protected $varDir;
	protected $baseName;
	protected $apiCreate;
	protected $apiDescribe;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
    {
        $this->baseName = (new \ReflectionClass($this))->getShortName();
        $this->apiCreate = 'create' . $this->baseName . 'Async';
        //$this->apiDescribe = 'describe' . $this->baseName . 'Async';
        $this->apiDescribe = 'describe' . $this->baseName;
        $this->nameConfig = $nameConfig;
		$this->region = $this->nameConfig->getAwsRegion();
		$this->personalizeClient = new PersonalizeClient(
			[ 'profile' => 'default',
			'version' => 'latest',
			'region' => "$this->region" ]
		);
    }
}
