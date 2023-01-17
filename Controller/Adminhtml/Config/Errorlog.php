<?php
/**
 * CustomerParadigm_AmazonPersonalize
 *
 * @category   CustomerParadigm
 * @package    CustomerParadigm_AmazonPersonalize
 * @copyright  Copyright (c) 2023 Customer Paradigm (https://customerparadigm.com/)
 * @license    https://github.com/Customer-Paradigm/Amazon-Personalize/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
use CustomerParadigm\AmazonPersonalize\Model\Error;

class Errorlog extends Action
{
    protected $resultJsonFactory;
    protected $loggerInterface;
    protected $helper;
    protected $wizardTracking;
    protected $errorLog;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param LoggerInterface $loggerInterface
     * @param wizardTracking $wizardTracking
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $loggerInterface,
        Data $helper,
        WizardTracking $wizardTracking,
        Error $errorLog
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->loggerInterface = $loggerInterface;
        $this->helper = $helper;
        $this->wizardTracking = $wizardTracking;
        $this->errorLog = $errorLog;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $data = [];
        $result = $this->resultJsonFactory->create();
        $log = $this->errorLog->getAllErrors();
        foreach ($log as $item) {
            $data[] = str_replace("\n", "<br>", trim($item['error_message'], '"'));
        }
        $result->setData($data);
        return $result;
    }


    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CustomerParadigm_AmazonPersonalize::config');
    }
}
