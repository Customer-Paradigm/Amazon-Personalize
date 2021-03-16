<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Config;

use \Magento\Framework\App\Config\Storage\WriterInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;
use \Magento\Framework\App\Filesystem\DirectoryList;
use CustomerParadigm\AmazonPersonalize\Helper\Data;

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
    protected $storeManager;
    protected $storeId;
    protected $directoryList;
    protected $webdir;
    protected $helper;

    /**
     * AfterSaveConfig constructor.
     *
     */
    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        Data $helper
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->infoLogger = $infoLogger;
        $this->errorLogger= $errorLogger;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->helper = $helper;
        $this->webdir = $this->directoryList->getRoot();
        $this->storeId = $this->storeManager->getStore()->getId();
        $this->homedir = $this->scopeConfig->getValue('awsp_settings/awsp_general/home_dir', 
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,$this->storeId);
        $this->region = $this->scopeConfig->getValue('awsp_settings/awsp_general/aws_region', 
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
	
	// Set default region for setup:upgrade, when admin settings are not yet populated
	if(empty($this->region)) {
		$this->region = "us-east-1";
	}
	
        putenv("HOME=$this->homedir");

        $this->pRuntimeClient = new PersonalizeRuntimeClient (
            [
                'profile' => 'default',
                'version' => 'latest',
                'region' => "$this->region" ]
            );
    }
    
    public function saveConfigSetting($path,$value) {
        $this->configWriter->save($path, $value);
    }

    public function setCron($name,$onoff,$schedule = "* * * * *"){
        if( $onoff == 'off' ) {
            $schedule = '';
        }
        $this->configWriter->save("awsp_settings/crontab/$name", $schedule);
        $this->helper->flushAllCache();
    }

    public function setLastAbPercent($num) {
        $this->configWriter->save('awsp_settings/awsp_percent/last_val', $num);
    }

    public function getLastAbPercent() {
        $val = $this->scopeConfig->getValue('awsp_settings/awsp_percent/last_val', 
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        $result = empty($val) ? false : $val;
        return $result;
    }

    public function saveKeys($access,$secret) {
        $this->configWriter->save('awsp_settings/awsp_general/access_key', $access);
        $this->configWriter->save('awsp_settings/awsp_general/secret_key', $secret);
    }

    public function getAccessKey() {
        return $this->scopeConfig->getValue('awsp_settings/awsp_general/access_key', 
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getSecretKey() {
        return $this->scopeConfig->getValue('awsp_settings/awsp_general/secret_key', 
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getClientAccessKey() {
        $key = $this->clientAccessKey;
        if(empty($key)) {
            $val = $this->checkConfig();
            $key = empty($val) ? false : $val['client_key'];
        }
        return $key;
    }

    public function getClientSecretKey() {
        $key = $this->clientSecretKey;
        if(empty($key)) {
            $val = $this->checkConfig();
            $key = empty($val) ? false : $val['client_secret'];
        }
        return $key;
    }

    public function getStoreName() {

        $name = $this->scopeConfig->getValue('general/store_information/name',
		\Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
	if( empty($name) ) {
	    $url = $this->storeManager->getStore()->getBaseUrl();
	    $name = parse_url($url, PHP_URL_HOST);
	}
	return $name;
    }

    public function isEnabled() {
        return $this->helper->canDisplay();
    }

    public function getUserHomeDir() {
        return $this->scopeConfig->getValue('awsp_settings/awsp_general/home_dir', 
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function setUserHome($dir) {
        $this->configWriter->save('awsp_settings/awsp_general/home_dir', $dir);
    }

    public function getAwsAccount() {
        return $this->scopeConfig->getValue('awsp_settings/awsp_general/aws_acct', 
               \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getAwsRegion() {
        return $this->region;
    }

    public function setAwsRegion($region) {
        $this->configWriter->save('awsp_settings/awsp_general/aws_region', $region);
    }

    public function checkConfig() {
        $client = $this->pRuntimeClient;
        $config_valid = false;
        try {
            $cred_class = $client->getCredentials();
            $status = $cred_class->getState();

            if($status === 'rejected' ) {
                $this->errorLogger->error("Aws Credentials failed. Looks like home/.aws file is missing or can't be read");
            }

            if($status === 'fulfilled' ) {
                $response = $cred_class->wait(true);
                $client_key = $response->getAccessKeyId();
                $client_secret = $response->getSecretKey();
                $saved_key = $this->getAccessKey();
                $saved_secret = $this->getSecretKey();
                if( !empty($client_key) &&
                    ($client_key != $saved_key) &&
                    !empty($client_secret) &&
                    ($client_secret != $saved_secret)
                ) {
                    $config_valid = array('client_key'=>$client_key, 'client_secret'=>$client_secret);
                } else {
                    $this->errorLogger->error('Aws Credentials failed. Looks like home/.aws creds were overwritten');
                }
            }

        } catch( Exception $e ) {
            $this->errorLogger->error('Error checking Aws Creds: ', ['exception' => $e]);
            return false;
        }
        return $config_valid;
    }

    public function getGaAbEnabled() {
        return $this->isEnabled() &&
            $this->scopeConfig->getValue('awsp_settings/awsp_abtesting/abtest_enable', 
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getGaAbPercent() {
        return $this->scopeConfig->getValue('awsp_settings/awsp_abtesting/percentage', 
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function getMagentoGaAccountNum() {
        return $this->scopeConfig->getValue('google/analytics/account', 
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
    }

    public function checkHeaderSiteTag() {
        $includes = $this->scopeConfig->getValue('design/head/includes', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        $acctnum = $this->scopeConfig->getValue('google/analytics/account', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        $hasgatag = strpos($includes, "www.googletagmanager.com/gtag/js?") !== false;
        $acct_matches = strpos($includes, $acctnum) !== false;
        return $hasgatag && $acct_matches;
    }

	public function encrypt_decrypt($action, $string) {
		$output = false;

		$encrypt_method = "AES-256-CBC";
		$secret_key = $this->homedir;
		$secret_iv = $this->webdir;

		// hash
		$key = hash('sha256', $secret_key);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);

		if ( $action == 'encrypt' ) {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
		} else if( $action == 'decrypt' ) {
			$output = openssl_decrypt($string, $encrypt_method, $key, 0, $iv);
		}

		return $output;
    }
    
    public function getCalcInstalled() {
        $coupon = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_coupon', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        $rule = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_key', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        $table = $this->scopeConfig->getValue('catalogrule_product_history', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $this->storeId);
        return $coupon && $rule && $table;
    }

    public function getLogger($type='error') {
        $rtn = $this->errorLogger;
        if($type == 'info') {
            $rtn = $this->infoLogger;

        }
        return $rtn;
    }

}
