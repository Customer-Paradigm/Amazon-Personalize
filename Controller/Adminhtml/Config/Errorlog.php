<?php
 
namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
 
class Errorlog extends Action
{
    protected $resultJsonFactory;
    protected $loggerInterface;
    protected $helper;
    protected $wizardTracking;
 
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
        wizardTracking $wizardTracking
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->loggerInterface = $loggerInterface;
        $this->helper = $helper;
        $this->wizardTracking = $wizardTracking;
        parent::__construct($context);
    }
 
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
	$count = $this->wizardTracking->pConfig->getInteractionsCount();
	$result->setData(['date'=>'today','error_message'=>'oh bother']);
	/*
	$result->setData(['value'=>$count,'paused'=>true]);
	if($count >= 1000) {
		$this->wizardTracking->resetStep('create_csv_files');
		$result->setData(['value'=>$count,'paused'=>false]);
	} else {
		$this->wizardTracking->setStepInprogress('create_csv_files');
	}
	 */
        return $result;
    }
 
 
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CustomerParadigm_AmazonPersonalize::config');
    }
}
