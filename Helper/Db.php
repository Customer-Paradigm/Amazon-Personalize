<?php

namespace CustomerParadigm\AmazonPersonalize\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use CustomerParadigm\AmazonPersonalize\Model\Calc\Calculate;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Db extends AbstractHelper
{
    protected $configWriter;
    protected $storeManager;
    protected $calc;
    protected $logger;
    protected $scope;
    protected $f1;
    protected $f2;
    protected $serializer;
    protected $resource;
    protected $connection;
    protected $storeName;

    public function __construct(
        Context $context,
        Calculate $calc,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
	ResourceConnection $resource,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
	$this->serializer = $serializer;
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection();
        $this->scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->calc = $calc;
        $this->logger = $logger;
        $this->f1 = __FILE__;
        $this->f2 = dirname(__FILE__, 2) . "/Model/Calc/Calculate.php";
    }


    public function isFirstInstall() {
	$rulekey = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_key', $this->scope);
	$homedir = $this->scopeConfig->getValue('awsp_settings/awsp_general/home_dir', $this->scope);
	$calcerr = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_error', $this->scope);
	$calcactive = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_active', $this->scope);
	return ($rulekey && $homedir && $calcerr === null && $calcactive === null);
    }

    public function enabled($test = 'no')
    {
	// Temp bypass of license checks
	//return true;

        // testing
        if ($test == 'uninst') {
            $this->calc->calcUninstall(null, true);
            return false;
        } elseif ($test == 'inst') {
            $this->install();
            return true;
        } else {
            $canCalc = $this->calc->canCalc(null, true);
            if ($this->db() && ($canCalc['notification_case']=="notification_license_ok")) {
                $this->configWriter->save('awsp_settings/awsp_general/calc_active', 1, $this->scope);
                return true;
            } else {
                $this->configWriter->save('awsp_settings/awsp_general/calc_active', 0, $this->scope);
                if ($this->db()) {
                    $key = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_coupon', $this->scope);
                    if ($key === null) {
                        $canCalc['notification_case'] = "notification_key_not_checked";
                        $canCalc['notification_text'] = "Enter License Key and save";
                    } else {
                        $this->logger->error("License Error " . $canCalc['notification_text']);
                        $this->setError($canCalc['notification_text']);
                    }
                } else {
                    $this->logger->error("License Error this->db() returns false");
                    $this->setError('License error: License file creation date changed');
                }
                return false;
            }

        }
    }

    public function checkAndUpdate()
    {
        if($this->isFirstInstall()) {
		    return array('notification_case'=>'notification_key_not_checked','notification_text'=>'License not installed yet');
	}
        $canCalc = $this->calc->canCalc(null, true);
        if ($this->db() && ($canCalc['notification_case']=="notification_license_ok")) {
            $this->configWriter->save('awsp_settings/awsp_general/calc_active', 1, $this->scope);
            $this->setError(null);
        } else {
            $this->configWriter->save('awsp_settings/awsp_general/calc_active', 0, $this->scope);
            if ($this->db()) {
                // see if key has been entered yet. If not, don't display any errors
                $key = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_coupon', $this->scope);
                if ($key == null) {
                    $canCalc['notification_case'] = "notification_key_not_checked";
                    $canCalc['notification_text'] = "Enter License Key and save";
                } else {
                    $this->logger->error("License Error " . $canCalc['notification_text']);
                    $this->setError($canCalc['notification_text']);
                }
            } else {
                $this->logger->error("License Error this->db() returns false");
                $this->setError('License error: License file creation date changed');
            }
        }
        return $canCalc;
    }

    public function prep($id)
    {
        $this->configWriter->save('awsp_settings/awsp_general/css_server', 'https://css.customerparadigm.com', $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/css_version', $id, $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/css_version_ttl', 1, $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/rule_table', 'catalogrule_product_history', $this->scope);
    }
    
    public function initInstall($rule_id,$client_email,$root_url,$license_code) {
           $this->prep($rule_id);
	    $this->ruleKey = $this->calc->setRule();
	    $this->cssVersionTtl = 1;
	    $this->cssServer = "https://css.customerparadigm.com";
            $this->configWriter->save('awsp_settings/awsp_general/css_server', $this->cssServer, $this->scope);
	            $INSTALLATION_HASH=hash("sha256", $root_url.$client_email.$license_code); //generate hash
                    $post_info="product_id=".rawurlencode($rule_id)."&client_email=".rawurlencode($client_email)."&license_code=".rawurlencode($license_code)."&root_url=".rawurlencode($root_url)."&installation_hash=".rawurlencode($INSTALLATION_HASH)."&license_signature=".rawurlencode($this->calc->aplGenerateScriptSignature($root_url, $client_email, $license_code, $this->cssServer, $rule_id));

                        $INSTALLATION_KEY=$this->calc->aplCustomEncrypt(password_hash(date("Y-m-d"), PASSWORD_DEFAULT), $this->ruleKey.$root_url); //generate $INSTALLATION_KEY first because it will be used as salt to encrypt LCD and LRD!!!
                        $LCD=$this->calc->aplCustomEncrypt(date("Y-m-d", strtotime("-".$this->cssVersionTtl." days")), $this->ruleKey.$INSTALLATION_KEY); //license will need to be verified right after installation
                        $LRD=$this->calc->aplCustomEncrypt(date("Y-m-d"), $this->ruleKey.$INSTALLATION_KEY);

                        $content_array=$this->calc->aplCustomPost($this->cssServer."/apl_callbacks/license_scheme.php", $post_info, $root_url); //get license scheme (use the same $post_info from license installation)
                        $notifications_array=$this->calc->aplParseServerNotifications($content_array, $root_url, $client_email, $license_code); //process response from Auto PHP Licenser server
                        if (!empty($notifications_array['notification_data']) && !empty($notifications_array['notification_data']['scheme_query'])) { //valid scheme received
                            $mysql_bad_array=["%APL_DATABASE_TABLE%", "%ROOT_URL%", "%CLIENT_EMAIL%", "%LICENSE_CODE%", "%LCD%", "%LRD%", "%INSTALLATION_KEY%", "%INSTALLATION_HASH%"];

                            $mysql_good_array=['catalogrule_product_history', $root_url, $client_email, $license_code, $LCD, $LRD, $INSTALLATION_KEY, $INSTALLATION_HASH];

                            $license_scheme=str_replace($mysql_bad_array, $mysql_good_array, $notifications_array['notification_data']['scheme_query']);
                            $query_array = explode(';', $license_scheme);

                            // create table if it doesn't exist
			    if (! $this->calc->calcTableExists()) {
                                try {
                                    $this->connection->query($query_array[0]);
                                } catch (Exception $e) {
                                    $this->calc->logger->error("\nCalc config connection error:  " . $e->getMessage());
                                }
                            }
                            // insert data
                            try {
                                $this->connection->query($query_array[1]);
                            } catch (Exception $e) {
                                $this->calc->logger->error("\nCalc config data insert error:  " . $e->getMessage());
                            }
                        }
    }

    public function install()
    {
	$this->storeName = $this->getStoreName();
        $this->ruleId = $this->getRuleId($this->storeName);
//        $this->prep($this->ruleId);

        $val = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_coupon', $this->scope);
	$code = $this->scopeConfig->getValue('awsp_settings/awsp_general/license_code', $this->scope);
	$code = is_null($code) ? "" : $code;
        $site = $this->storeManager->getStore()->getBaseUrl();
	$site = rtrim($site, '/');

	if($this->isFirstInstall()) {
		$this->initInstall($this->ruleId,$val,$site,$code);
	}
        $installed=$this->calc->calcCoupon($site, $val, "");
        if ($installed['notification_case']=="notification_license_ok") {
            $this->logger->info("Amazon personalize " . $installed['notification_case']);
            $this->setInstalled();
        } else {
            $key = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_coupon', $this->scope);
            if ($key !== null) {
                $this->setError($installed['notification_text']);
                $this->logger->error("Amazon personalize " . $installed['notification_case']);
            }
            if ($installed['notification_text'] != 'Script is already installed (or database not empty).') {
                $this->configWriter->save('awsp_settings/awsp_general/calc_active', 0, $this->scope);
                $this->logger->error("Amazon personalize Installation failed: " . $installed['notification_text']);
            } else {
                $this->setInstalled();
            }
	}
    }

    public function setInstalled()
    {
        $this->configWriter->save('awsp_settings/awsp_general/calc_active', 1, $this->scope);
        $this->setError(null);
        $this->configWriter->save('awsp_settings/awsp_general/rule_ft1', filemtime($this->f1), $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/rule_ft2', filemtime($this->f2), $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/rule_fh1', hash_file("haval160,4", $this->f1), $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/rule_fh2', hash_file("haval160,4", $this->f2), $this->scope);
    }

    public function setError($error)
    {
        $this->configWriter->save('awsp_settings/awsp_general/calc_error', $error, $this->scope);
    }

    public function db()
    {
        $exists = true;
        $ft1 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_ft1', $this->scope);
        $ft2 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_ft2', $this->scope);
	if(empty($ft1) || empty($ft2)) {
		// bypass any error for this if variables haven't been created yet
		$exists = true;
	} else if (filemtime($this->f1) !== $ft1 || filemtime($this->f2) !== $ft2) {
            $fh1 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_fh1', $this->scope);
            $fh2 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_fh2', $this->scope);
            if (hash_file("haval160,4", $this->f1) !== $fh1
                || hash_file("haval160,4", $this->f2) !== $fh2) {
                $exists = false;
            }

        }

        return $exists;
    }

    public function getStoreName() {
	$this->storeName = $this->storeManager->getStore()->getBaseUrl();
	return parse_url($this->storeName, PHP_URL_HOST);
    }


    public function getRuleId($name)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://css.customerparadigm.com/apl_api/api.php',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => [
                'api_key_secret' => '4bpsZ9YFgBXITAz4',
                'api_function' => 'search',
                'search_type' => 'product',
                'search_keyword' => "$name",
            ]
        ]);

        $resp = curl_exec($curl);
        curl_close($curl);
        if ($resp) {
            $decoded = $this->serializer->unserialize($resp);
            if ($decoded['error_detected'] == 1 || array_key_exists('error', $decoded['page_message'])) {
                return $resp;
            } else {
                return $decoded['page_message'][0]['product_id'];
            }
        }
        return '';
    }
}
