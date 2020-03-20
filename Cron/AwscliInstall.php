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

    /**
     * constructor
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger,
        DirectoryList $directoryList,
        ScopeConfigInterface $scopeConfig,
        PersonalizeConfig $pConfig
    )
    {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->scopeConfig = $scopeConfig;
        $this->pConfig = $pConfig;
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
            shell_exec($cmd);
            $cmd = "ls -la | grep '\.aws'";
            $output = shell_exec($cmd);
            if(empty($output)) { 
                $this->logger->info('Creating aws directory');
                $cmd = 'mkdir .aws';
                shell_exec($cmd);
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
