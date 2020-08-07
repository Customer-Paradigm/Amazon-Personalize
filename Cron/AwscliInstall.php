<?php

/**
 * @author Customer Paradigm Team
 * @copyright Copyright (c) 2019 Customer Paradigm (https://www.customerparadigm.com)
 * @package CustomerParadigm_AmazonPersonalize
 */

namespace CustomerParadigm\AmazonPersonalize\Cron;

use Psr\Log\LoggerInterface;
use \Magento\Framework\Filesystem\DirectoryList;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Shell;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;


/**
 * Class AwscliInstall
 * @package CustomerParadigm\AmazonPersonalize\Cron
 */
class AwscliInstall
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    protected $directoryList;
    protected $scopeConfig;
    protected $pConfig;
    protected $shell;

    /**
     * constructor
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig,
	PersonalizeConfig $pConfig,
	Shell $shell
    )
    {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->scopeConfig = $scopeConfig;
        $this->pConfig = $pConfig;
        $this->shell = $shell;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->logger->info('Aws Creds Save Cron Job execute called');
        try {
            // Create aws creds directory
            $cmd = 'cd';
            $this->shell->execute($cmd);
            $cmd = "ls -la | grep '\.aws'";
            $output = $this->shell->execute($cmd);
            if(empty($output)) { 
                $this->logger->info('Creating aws directory');
                $cmd = 'mkdir .aws';
                $this->shell->execute($cmd);
                $this->logger->info('Aws directory created');
            } else {
                // Disable cron that calls this once aws directory is created
                $this->logger->info('Aws Creds Save Cron  Disable ---------:');
                $this->pConfig->setCron('aws_set_cli','off');
            }

        } catch (\Exception $e) {
            $this->logger->critical('Aws Creds Save Cron Job Error:', ['exception' => $e]);
        }
        
    }
}
