<?php

namespace CustomerParadigm\AmazonPersonalize\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use CustomerParadigm\AmazonPersonalize\Model\Calc\Calculate;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;


class Db extends AbstractHelper {

	protected $configWriter;
	protected $storeManager;
	protected $calc;
	protected $logger;
	protected $scope;
	protected $f1;
	protected $f2;
	protected $serializer;

	public function __construct( 
		Context $context,
		Calculate $calc,
		WriterInterface $configWriter,
		StoreManagerInterface $storeManager,
		LoggerInterface $logger,
		SerializerInterface $serializer
	) {
		parent::__construct($context);
		$this->storeManager = $storeManager;
		$this->configWriter = $configWriter;
		$this->serializer = $serializer;
		$this->scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
		$this->storeName = $this->scopeConfig->getValue('general/store_information/name', $this->scope);
		$this->ruleId = $this->getRuleId($this->storeName);
		$this->prep($this->ruleId);
		$this->calc = $calc;
		$this->logger = $logger;
		$this->f1 = __FILE__;
		$this->f2 = dirname(__FILE__,2) . "/Model/Calc/Calculate.php";
	}


	public function enabled($test = 'no') {
		// testing
		if($test == 'uninst') {
			$this->calc->calcUninstall(null, true);
			return false;
		} else if($test == 'inst') {
			$this->install();
			return true;
		} else {
			$canCalc = $this->calc->canCalc(null, true);
			if ($this->db() && $canCalc['notification_case']=="notification_license_ok") {
				return true;
                        } else {
                                if($this->db()) {
                                        $this->logger->error("License Error " . $canCalc['notification_text']);
                                } else {
                                        $this->logger->error("License Error this->db() returns false");
                                }
                                return false;
                        }

		}
	}

	public function prep($id) {
		$this->configWriter->save('awsp_settings/awsp_general/css_server','https://css.customerparadigm.com', $this->scope);
		$this->configWriter->save('awsp_settings/awsp_general/css_version', $id, $this->scope);
		$this->configWriter->save('awsp_settings/awsp_general/css_version_ttl',1, $this->scope);
		$this->configWriter->save('awsp_settings/awsp_general/rule_table', 'catalogrule_product_history' , $this->scope);
	}

	public function install() {
		$val = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_coupon', $this->scope);
		$site = $this->storeManager->getStore()->getBaseUrl();
		$site = rtrim($site,'/');
		$installed=$this->calc->calcCoupon($site, $val, ""); 
		if ($installed['notification_case']=="notification_license_ok") {
			$this->logger->info("Amazon personalize " . $installed['notification_case']);
			$this->setInstalled();
		} else {
			$this->setError($installed['notification_text']);
			$this->logger->error("Amazon personalize " . $installed['notification_case']);
			if( $installed['notification_text'] != 'Script is already installed (or database not empty).' ) {
				$this->configWriter->save('awsp_settings/awsp_general/calc_active',0, $this->scope);
				$this->logger->error("Amazon personalize Installation failed: " . $installed['notification_text']);
			} else {
				$this->setInstalled();
			}
		}
	}

	public function setInstalled() {
		$this->configWriter->save('awsp_settings/awsp_general/calc_active',true, $this->scope);
		$this->configWriter->save('awsp_settings/awsp_general/rule_ft1', filemtime($this->f1), $this->scope);
		$this->configWriter->save('awsp_settings/awsp_general/rule_ft2', filemtime($this->f2), $this->scope);
		$this->configWriter->save('awsp_settings/awsp_general/rule_fh1', hash_file("haval160,4", $this->f1), $this->scope);
		$this->configWriter->save('awsp_settings/awsp_general/rule_fh2', hash_file("haval160,4", $this->f2), $this->scope);
	}

	public function setError($error) {
		$this->configWriter->save('awsp_settings/awsp_general/calc_error',$error, $this->scope);
	}

	public function db() {
		$exists = true;
		$ft1 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_ft1', $this->scope);
		$ft2 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_ft2', $this->scope);

		if(filemtime($this->f1) !== $ft1 || filemtime($this->f2) !== $ft2) {
			$fh1 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_fh1', $this->scope);
			$fh2 = $this->scopeConfig->getValue('awsp_settings/awsp_general/rule_fh2', $this->scope);
			if( hash_file("haval160,4", $this->f1) !== $fh1 
				|| hash_file("haval160,4", $this->f2) !== $fh2) {
				$exists = false;
			}

		}

		return $exists;
	}

	public function getRuleId($name) {
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
		if($resp) {
			$decoded = $this->serializer->unserialize($resp);
			if($decoded['error_detected'] == 1 || array_key_exists('error',$decoded['page_message']) ) {
				return $resp;
			} else {
				return $decoded['page_message'][0]['product_id'];
			}
		}
		return '';
	}

}
