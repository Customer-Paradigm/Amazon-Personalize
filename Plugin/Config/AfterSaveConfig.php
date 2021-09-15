<?php

namespace CustomerParadigm\AmazonPersonalize\Plugin\Config;

use Psr\Log\LoggerInterface;
use \Magento\Framework\Shell;
use \Magento\Framework\App\Filesystem\DirectoryList;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;
use CustomerParadigm\AmazonPersonalize\Model\AbTracking;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Helper\Db;
use CustomerParadigm\AmazonPersonalize\Model\Calc\Calculate;

class AfterSaveConfig
{
	/**
	 * @var Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \Magento\Framework\Shell
	 */
	protected $shell;
	
	/**
	 * @var \Magento\Framework\Module\Dir
	 */
	protected $moduleDir;

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
	 * @var CustomerParadigm\AmazonPersonalize\Helper\Db
	 */
	protected $dbHelper;

	/**
	 * @var CustomerParadigm\AmazonPersonalize\Model\Calc\Calculate
	 */
	protected $calc;


	/**
	 * AfterSaveConfig constructor.
	 */
	public function __construct(
		LoggerInterface $logger,
		Shell $shell,
		DirectoryList $moduleDir,
		PersonalizeConfig $pConfig,
		AbTracking $abTracking,
		WizardTracking $wizardTracking,
		Db $dbHelper,
		Calculate $calc
	) {
		$this->logger = $logger;
		$this->shell = $shell;
		$this->moduleDir = $moduleDir;
		$this->pConfig = $pConfig;
		$this->abTracking = $abTracking;
		$this->wizardTracking = $wizardTracking;
		$this->dbHelper = $dbHelper;
		$this->calc = $calc;
	}

	public function afterSave( \Magento\Config\Model\Config $subject, $result) {
		$section = $subject->getSection();
		if( $section === 'awsp_settings' ) {

			if( !$this->pConfig->getCalcInstalled() ) {
				$this->dbHelper->Install();
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
				if($this->pConfig->ec2Flag()) { // Bypass creds save if module is installed on an EC2 instance
					return $result;
				}

				$cred_dir = $this->moduleDir->getPath('media');
				$region = $this->pConfig->getAwsRegion();
				$access_key = $this->pConfig->getAccessKey();
				$secret_key = $this->pConfig->getSecretKey();
				$client_access_key = $this->pConfig->getClientAccessKey();
				$client_secret_key = $this->pConfig->getClientSecretKey();


				if(!empty($access_key)) {
					$save_key = $access_key;
				} else {
					$save_key = $client_access_key;
				}

				if(!empty($secret_key)) {
					$save_secret = $secret_key;
				} else {
					$save_secret = $client_secret_key;
				}

				$config_dir = $cred_dir .'/.aws';
				$cred_file = $cred_dir . '/.aws/credentials';
				$config_file = $cred_dir . '/.aws/config';
				$htaccess_file = $cred_dir . '/.aws/.htaccess';
				$cmd = "mkdir -p $config_dir && touch $config_file";
				$output = $this->shell->execute($cmd);
				$cmd = "touch $cred_file";
				$output = $this->shell->execute($cmd);
				$cmd = "touch $htaccess_file";
				$output = $this->shell->execute($cmd);
				$htaccess_entry = 'Deny from all';
				$cmd = 'echo "'. $htaccess_entry . '" >' . $htaccess_file;
				$output = $this->shell->execute($cmd);

				$cred_entry = "[default]
					aws_access_key_id = $save_key
					aws_secret_access_key = $save_secret";

				$cmd = 'echo "'. $cred_entry . '" >' . $cred_file;
				$output = $this->shell->execute($cmd);

				$config_entry = "[default]
					region=$region
					output=json";

				$cmd = 'echo "'. $config_entry . '" >' . $config_file;
				$output = $this->shell->execute($cmd);

				$this->pConfig->saveKeys($save_key,$save_secret);
				$this->calc->setRule();

				$procStatus =  $this->wizardTracking->getProcessStatus()['status'];
				// Enable/disable cron based on process status
				if( $this->pConfig->isEnabled() == false || $procStatus == 'hasError' || $procStatus == 'finished') {
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
