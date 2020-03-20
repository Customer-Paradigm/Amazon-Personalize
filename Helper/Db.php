<?php

namespace CustomerParadigm\AmazonPersonalize\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
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

    public function __construct( 
        Context $context,
        Calculate $calc,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->configWriter = $configWriter;
		$this->calc = $calc;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->f1 = __FILE__;
        $this->f2 = dirname(__FILE__,2) . "/Model/Calc/Calculate.php";
    }


    public function enabled($test = 'no') {
        // testing
        if($test == 'uninst') {
            die('1');
            $this->calc->calcUninstall(null, true);
            return false;
        } else if($test == 'inst') {
            $this->install();
            return true;
        } else {
            die('3');
            $canCalc = $this->calc->canCalc(null, true);
            if ($this->db() && $canCalc['notification_case']=="notification_license_ok") {
                return true;
            } else {
                return false;
            }
        }
    }
    
    public function install() {
        $val = $this->scopeConfig->getValue('awsp_settings/awsp_general/calc_coupon', $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/css_server','https://css.customerparadigm.com', $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/css_version',3, $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/css_version_ttl',1, $this->scope);
        $this->configWriter->save('awsp_settings/awsp_general/rule_table', 'catalogrule_product_history' , $this->scope);
        $site = $this->storeManager->getStore()->getBaseUrl();
        $site = rtrim($site,'/');
        $installed=$this->calc->calcCoupon($site, $val, ""); 
        var_dump($installed);
        if ($installed['notification_case']=="notification_license_ok") {
            $this->logger->info("Amazon personalize " . $installed['notification_case']);
            $this->setInstalled();
        } else {
            $this->logger->error("Amazon personalize " . $installed['notification_case']);
            if( ! $installed['notification_text'] == 'Script is already installed (or database not empty).' ) {
                echo "Amazon Personalize Installation failed: ".$installed['notification_text'];
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
    
    public function setRule() {
        $this->configWriter->save('awsp_settings/awsp_general/rule_key', bin2hex(random_bytes(16)), $this->scope);
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
}
