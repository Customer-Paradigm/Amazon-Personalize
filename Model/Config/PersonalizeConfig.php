<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Config;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;
use Magento\Framework\App\Filesystem\DirectoryList;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Helper\Aws;
use CustomerParadigm\AmazonPersonalize\Model\InteractionCheck;
use CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient;

class PersonalizeConfig
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $configWriter;
    protected $scopeConfig;
    protected $pRuntimeClient;
    protected $region;
    protected $homedir;
    protected $errorLogger;
    protected $infoLogger;
    protected $clientAccessKey;
    protected $clientSecretKey;
    protected $sdkClient;
    protected $storeManager;
    protected $storeId;
    protected $directoryList;
    protected $webdir;
    protected $helper;
    protected $awsHelper;
    protected $interactionCheck;
    protected $stsClient;
    protected $cryp_key;
    protected $cryp_iv;
    protected $cryp_cypher;

    /**
     * AfterSaveConfig constructor.
     *
     */
    public function __construct(
        WriterInterface $configWriter,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        Data $helper,
        Aws $awsHelper,
        InteractionCheck $interactionCheck,
        AwsSdkClient $sdkClient
    ) {
        $this->configWriter = $configWriter;
        $this->infoLogger = $infoLogger;
        $this->errorLogger= $errorLogger;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->helper = $helper;
        $this->awsHelper = $awsHelper;
        $this->interactionCheck = $interactionCheck;
        $this->sdkClient = $sdkClient;
        $this->webdir = $this->directoryList->getRoot();
        $this->storeId = $this->storeManager->getStore()->getId();
        // if this is default store view id (1), make it the same as admin (0)
        $this->storeId = $this->storeId == 1 ? 0 : $this->storeId;
        $this->scopeConfig = $sdkClient->getScopeConfig();
        $this->homedir = $this->scopeConfig->getValue(
            'awsp_settings/awsp_general/home_dir',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
        if (is_null($this->homedir) || !is_writable($this->homedir)) {
            $this->homedir =  $this->webdir . "/pub/media";
            $this->configWriter->save('awsp_settings/awsp_general/home_dir', $this->homedir);
        }

        putenv("HOME=$this->homedir");

        $this->region = $this->sdkClient->getAwsRegion('PersonalizeRuntime');
        $this->pRuntimeClient = $this->sdkClient->getClient('PersonalizeRuntime');
	$this->stsClient = $this->sdkClient->getClient('sts');
	$this->cryp_key = "qV3UPAWUXtjK";
	$this->cryp_iv = "CTzVhG67lxV8xyxy";
	$this->cryp_cypher = "AES-256-CBC";
    }

    public function saveConfigSetting($path, $value)
    {
        $this->configWriter->save($path, $value);
    }

    public function deleteConfigSetting($path)
    {
        $this->configWriter->delete($path);
    }

    public function setCron($name, $onoff, $schedule = "* * * * *")
    {
        if ($onoff == 'off') {
            $schedule = '';
        }
        $this->configWriter->save("awsp_settings/crontab/$name", $schedule);
        $this->helper->flushAllCache();
    }

    public function setLastAbPercent($num)
    {
        $this->configWriter->save('awsp_settings/awsp_percent/last_val', $num);
    }

    public function getLastAbPercent()
    {
        $val = $this->scopeConfig->getValue(
            'awsp_settings/awsp_percent/last_val',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
        $result = empty($val) ? false : $val;
        return $result;
    }

    public function saveKeys($access, $secret)
    {
	    $access_crypt = $this->encrypt($access);
	    $secret_crypt = $this->encrypt($secret);
	    $this->configWriter->save('awsp_settings/awsp_general/access_key', $access_crypt);
	    $this->configWriter->save('awsp_settings/awsp_general/secret_key', $secret_crypt);
    }

    public function getAccessKey($is_encrypted = false)
    {
	    $rtn = $this->scopeConfig->getValue(
		    'awsp_settings/awsp_general/access_key',
		    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
		    $this->storeId
	    );
	    if($is_encrypted) {
		    return $this->decrypt($rtn);
	    }
	    return $rtn;
    }

    public function getSecretKey($is_encrypted = false)
    {
	    $rtn = $this->scopeConfig->getValue(
		    'awsp_settings/awsp_general/secret_key',
		    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
		    $this->storeId
	    );
	    if($is_encrypted) {
		    return $this->decrypt($key_crypt);
	    }
	    return $rtn;
    }

    public function getClientAccessKey()
    {
        $key = $this->clientAccessKey;
        if (empty($key)) {
            $val = $this->checkConfig();
            $key = empty($val) ? false : $val['client_key'];
        }
        return $key;
    }

    public function getClientSecretKey()
    {
        $key = $this->clientSecretKey;
        if (empty($key)) {
            $val = $this->checkConfig();
            $key = empty($val) ? false : $val['client_secret'];
        }
        return $key;
    }

    public function getStoreName()
    {
        $configname = $this->scopeConfig->getValue(
            'general/store_information/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
        if (! empty($configname)) {
            $configname = $this->awsCleanName($configname);
        }
        $s3name = $this->scopeConfig->getValue(
            'awsp_wizard/data_type_name/s3BucketName',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
        if (!empty($configname) && !empty($s3name) && strpos($s3name, $configname) !== false) { // storename from config is already in use, continue with that
            $name = $configname;
        } else {
            $url = $this->storeManager->getStore()->getBaseUrl();
            $name = parse_url($url, PHP_URL_HOST);
            $name = $this->awsCleanName($name) . '-' . $this->storeId;
        }
        return $name;
    }

    public function awsCleanName($storeName)
    {
        $storeName = trim($storeName); // clean leading/trailing spaces
        $storeName = preg_replace('/[^A-Za-z0-9\-\.\s]/', '', $storeName); // Removes special chars
        $storeName = explode(' ', $storeName);
        $storeName = array_slice($storeName, 0, 3);
        $storeName = implode('-', $storeName);
        $storeName = strtolower($storeName);

        return $storeName;
    }

    public function isEnabled()
    {
        return $this->helper->moduleEnabled();
    }

    public function getInteractionsCount()
    {
        $rtn = 0;
        $process_started = $this->scopeConfig->getValue('awsp_settings/awsp_general/file-interactions-count');
        if ($process_started === null) {
            return false;
        }
        $ordercount = $this->getOrderInteractionsCount();
        $eventcount = $this->getEventInteractionsCount();
        return $ordercount + $eventcount;
    }

    public function getEventInteractionsCount()
    {
        return $this->interactionCheck->getTotal();
    }

    public function getOrderInteractionsCount()
    {
        return $this->scopeConfig->getValue('awsp_settings/awsp_general/order-interactions-count');
    }

    public function getFileInteractionsCount()
    {
        $filecount = $this->scopeConfig->getValue(
            'awsp_settings/awsp_general/file-interactions-count',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
        $filecount = empty($filecount) ? 0 : $filecount;
        return $filecount;
    }

    public function needsInteractions()
    {
        if ($count = $this->getInteractionsCount() === false) {
            $rtn = false;
        } else {
            $rtn = $count < 1000;
        }
        return $rtn;
    }

    public function getUserHomeDir()
    {
        return $this->scopeConfig->getValue(
            'awsp_settings/awsp_general/home_dir',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function setUserHome($dir)
    {
        $this->configWriter->save('awsp_settings/awsp_general/home_dir', $dir);
    }

    public function getAwsAccount()
    {
        return $this->scopeConfig->getValue(
            'awsp_settings/awsp_general/aws_acct',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getAwsRegion()
    {
        return $this->region;
    }

    public function setAwsRegion($region)
    {
        $this->configWriter->save('awsp_settings/awsp_general/aws_region', $region);
    }

    public function checkConfig()
    {
        $client = $this->pRuntimeClient;
        $config_valid = false;
        try {
            $cred_class = $client->getCredentials();
            $status = $cred_class->getState();
            if ($status === 'rejected') {
                $this->errorLogger->error("Aws Credentials failed. Looks like .aws file is missing or can't be read");
            }

            if ($status === 'fulfilled' || $status === 'pending') {
                $response = $cred_class->wait(true);

                $client_key = $response->getAccessKeyId();
                $client_secret = $response->getSecretKey();
                $config_valid = ['client_key'=>$client_key, 'client_secret'=>$client_secret];
            }
        } catch (Exception $e) {
            $this->errorLogger->error('Error checking Aws Creds: ', ['exception' => $e]);
            return false;
        }
        return $config_valid;
    }

    public function getGaAbEnabled()
    {
        return $this->isEnabled() &&
            $this->scopeConfig->getValue(
                'awsp_settings/awsp_abtesting/abtest_enable',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeId
            );
    }

    public function getGaAbPercent()
    {
        return $this->getGaAbEnabled() && $this->scopeConfig->getValue(
            'awsp_settings/awsp_abtesting/percentage',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function getMagentoGaAccountNum()
    {
        return $this->getGaAbEnabled() && $this->scopeConfig->getValue(
            'google/analytics/account',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
    }

    public function checkHeaderSiteTag()
    {
        $includes = $this->scopeConfig->getValue(
            'design/head/includes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeId
        );
	$acctnum = $this->getMagentoGaAccountNum();
        $hasgatag = strpos($includes, "www.googletagmanager.com/gtag/js?") !== false;
        $acct_matches = strpos($includes, chr($acctnum)) !== false;
        return $hasgatag && $acct_matches;
    }
    
    public function encrypt($simple_string) {
	$ciphering = "AES-256-CBC";

	// Use OpenSSl Encryption method
	$iv_length = openssl_cipher_iv_length($ciphering);
	$options = 0;

	// Use openssl_encrypt() function to encrypt the data
	$encryption = openssl_encrypt($simple_string, $this->cryp_cypher,
		    $this->cryp_key, $options, $this->cryp_iv);

	return $encryption;
    }


    public function decrypt($encryption) {
	$ciphering = "AES-256-CBC";

	// Use OpenSSl Encryption method
	$iv_length = openssl_cipher_iv_length($ciphering);
	$options = 0;

	// Use openssl_decrypt() function to decrypt the data
	$decryption=openssl_decrypt ($encryption, $this->cryp_cypher,
		$this->cryp_key, $options, $this->cryp_iv);

	return $decryption;
    }

    public function getLogger($type = 'error')
    {
        $rtn = $this->errorLogger;
        if ($type == 'info') {
            $rtn = $this->infoLogger;
        }
        return $rtn;
    }

    public function getScopeConfig()
    {
        return $this->scopeConfig;
    }
}
