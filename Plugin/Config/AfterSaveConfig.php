<?php

namespace CustomerParadigm\AmazonPersonalize\Plugin\Config;

use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;
use CustomerParadigm\AmazonPersonalize\Model\AbTracking;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Helper\Db;

class AfterSaveConfig
{
    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig
     */
    protected $pConfig;
    
    /**
     * @var CustomerParadigm\AmazonPersonalize\Model\AbTracking
     */
    protected $abTracking;
    
    /**
     * @var CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking
     */
    protected $wizardTracking;

    /**
     * @var CustomerParadigm\AmazonPersonalize\Helper\Data
     */
	protected $pHelper;
    
    /**
     * @var CustomerParadigm\AmazonPersonalize\Helper\Db
     */
	protected $dbHelper;
    

    /**
     * AfterSaveConfig constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        PersonalizeConfig $pConfig,
        AbTracking $abTracking,
        WizardTracking $wizardTracking,
        Data $pHelper,
        Db $dbHelper
    ) {
        $this->logger = $logger;
        $this->pConfig = $pConfig;
        $this->abTracking = $abTracking;
        $this->wizardTracking = $wizardTracking;
        $this->pHelper = $pHelper;
        $this->dbHelper = $dbHelper;
    }

    public function afterSave( \Magento\Config\Model\Config $subject, $result) {
        $section = $subject->getSection();
        if( $section === 'awsp_settings' ) {

            if( !$this->pConfig->getCalcInstalled() ) {
                $this->dbHelper->Install();
            }

            // Encrypt AWS account number if not encrypted ( it will be all numerical if not encrypted )
            if( is_numeric($this->pConfig->getAwsAccount()) ) {
                $this->pConfig->encryptAwsAccount();
            }

            // If a/b testing percentage changed, clear a/b tracking table
            $last_ab_val = $this->pConfig->getLastAbPercent();
            $saved_ab_val = $this->pConfig->getGaAbPercent();
            if ($last_ab_val !== $saved_ab_val) {
                $this->abTracking->clearData();
                $this->pConfig->setLastAbPercent($saved_ab_val);
            }

            // Set credentials for aws php sdk
            try {

                $home_dir = $this->pConfig->getUserHomeDir();
                $region = $this->pConfig->getAwsRegion();
                $access_key = $this->pConfig->getAccessKey();
                $secret_key = $this->pConfig->getSecretKey();
                $client_access_key = $this->pConfig->getClientAccessKey();
                $client_secret_key = $this->pConfig->getClientSecretKey();


                if(!empty($access_key)
                    && $access_key !== "saved") {
                    $save_key = $access_key;
                } else {
                    $save_key = $client_access_key;
                }

                if(!empty($secret_key)
                    && $secret_key !== "saved") {
                    $save_secret = $secret_key;
                } else {
                    $save_secret = $client_secret_key;
                }

                $config_dir = $home_dir .'/.aws';
                $cred_file = $home_dir . '/.aws/credentials';
                $config_file = $home_dir . '/.aws/config';
                $cmd = "mkdir -p $config_dir && touch $config_file";
                $output = shell_exec($cmd);
                $cmd = "touch $cred_file";
                $output = shell_exec($cmd);

                $cred_entry = "[default]
                    aws_access_key_id = $save_key
                    aws_secret_access_key = $save_secret";

                $cmd = 'echo "'. $cred_entry . '" >' . $cred_file;
                $output = shell_exec($cmd);

                $config_entry = "[default]
                    region=$region
                    output=json";

                $cmd = 'echo "'. $config_entry . '" >' . $config_file;
                $output = shell_exec($cmd);

                $this->pConfig->saveKeys('saved','saved');
                $this->dbHelper->setRule();

                $procStatus =  $this->wizardTracking->getProcessStatus()['status'];
                // Enable/disable cron based on process status
                if( $this->pHelper->isEnabled() == false || $procStatus == 'hasError' || $procStatus == 'finished') {
                    $this->logger->info('Aws plugin data create Cron off -------------');
                    $this->pConfig->setCron('aws_data_setup','off');
                } else {
                    $this->logger->info('Aws plugin data create Cron on -------------');
                    $this->pConfig->setCron('aws_data_setup','on');
                }
                } catch (\Exception $e) {
                    $this->logger->critical('Aws Creds Save Error:', ['exception' => $e]);
                }

        } 
        return $result;
    }

}
