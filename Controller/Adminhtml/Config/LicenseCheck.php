<?php
 
namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Model\Error;
use CustomerParadigm\AmazonPersonalize\Helper\Db;

class LicenseCheck extends Action
{
    protected $resultJsonFactory;
    protected $loggerInterface;
    protected $calc;
    protected $errorLog;
 
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param LoggerInterface $loggerInterface
     * @param wizardTracking $wizardTracking
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $loggerInterface,
        Db $calc,
        Error $errorLog
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->loggerInterface = $loggerInterface;
        $this->calc = $calc;
        $this->errorLog = $errorLog;
        parent::__construct($context);
    }
 
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
	$data = $this->calc->checkAndUpdate();
	// If license corrupted, try clearing and re-running install()
	if($data['notification_case'] = "notification_license_corrupted") {
		$this->calc->resetRuleTable();
		$data = $this->calc->install();
	}
        $result->setData($data);
        return $result;
    }
 
 
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CustomerParadigm_AmazonPersonalize::config');
    }
}
