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
use \Magento\Framework\HTTP\Client\Curl;
use \Magento\Framework\App\Config\Storage\WriterInterface;
use \CustomerParadigm\AmazonPersonalize\Helper\Aws;

class AwsSdkClient implements AwsSdkClientInterface
{

    protected $scopeConfig;
    protected $configWriter;
    protected $storeId;
    protected $region;
    protected $curl;
    protected $awsHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        Curl $curl,
        Aws $awsHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->awsHelper = $awsHelper;
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->region = $this->scopeConfig->getValue(
            'awsp_settings/awsp_general/aws_region',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    
        // Set default region for setup:upgrade, when admin settings are not yet populated
        if (empty($this->region)) {
            $this->region = "us-east-1";
        }
    }

    public function getClient($type)
    {
        $classname = $this->getClientClass($type);
        $params = [
                        'version' => 'latest',
            'region' => "$this->region" ];
        
        // if this module's website is not running on ec2 instance
        // check for local credentials
        if (!$this->awsHelper->isEc2Install()) {
            $params['profile'] = 'default';
        }
        $client = new $classname($params);
        return $client;
    }

    public function getAwsRegion()
    {
        return $this->region;
    }
    
    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }

    protected function getClientClass($typename)
    {
        $rtn = '';
        switch (strtolower($typename)) {
            case 'personalizeruntime':
                $rtn = 'Aws\PersonalizeRuntime\PersonalizeRuntimeClient';
                break;
            case 'personalize':
                $rtn = 'Aws\Personalize\PersonalizeClient';
                break;
            case 'iam':
                $rtn = 'Aws\Iam\IamClient';
                break;
            case 'sts':
                $rtn = 'Aws\Sts\StsClient';
                break;
            case 's3':
                $rtn = 'Aws\S3\S3Client';
                break;
        }
        return $rtn;
    }
}
