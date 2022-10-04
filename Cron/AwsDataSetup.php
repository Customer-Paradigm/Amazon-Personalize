<?php

/**
 * @author Customer Paradigm Team
 * @copyright Copyright (c) 2019 Customer Paradigm (https://www.customerparadigm.com)
 * @package CustomerParadigm_AmazonPersonalize
 */

namespace CustomerParadigm\AmazonPersonalize\Cron;

use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;

/**
 * Class AwsDataSetup
 * @package CustomerParadigm\AmazonPersonalize\Cron
 */
class AwsDataSetup
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
    ) {
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
        $this->logger->info('------------AWS data setup cron run-----------');
    }
}
