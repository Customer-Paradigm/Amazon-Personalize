<?php
 
namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Model\Training\StepsReset;
use CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
 
class Reset extends Action
{
 
    protected $resultJsonFactory;
    protected $loggerInterface;
    protected $helper;
    protected $stepsReset;
    protected $nameConfig;
    protected $pConfig;
    protected $wizardTracking;
 
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param LoggerInterface $loggerInterface
     * @param StepsReset $stepsReset
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        LoggerInterface $loggerInterface,
        Data $helper,
        StepsReset $stepsReset,
        NameConfig $nameConfig,
        PersonalizeConfig $pConfig,
        WizardTracking $wizardTracking
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->loggerInterface = $loggerInterface;
        $this->helper = $helper;
        $this->stepsReset = $stepsReset;
        $this->nameConfig = $nameConfig;
        $this->pConfig = $pConfig;
        $this->wizardTracking = $wizardTracking;
        parent::__construct($context);
    }
 
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        try {
            $this->loggerInterface->info('Aws data reset Cron off -------------');
            $this->pConfig->setCron('aws_data_setup','off');
        } catch (\Exception $e) {
            $this->loggerInterface->critical($e);
            $err_mssg = "AWS reset disable cron error: " . $e->getMessage();
            $rtn = array('mssg'=>$err_mssg,'steps'=>array(), 'state'=>'error');
        }
		$test = $this->stepsReset->execute();
        $rtn = array();
        $mssg = null;
        try {
			$rtn['steps'] = $this->wizardTracking->displayProgress();
			$rtn['mssg'] = '';
			$rtn['state'] = 'success';

        } catch (\Exception $e) {
            $this->loggerInterface->critical($e);
			$rtn['steps'] = $this->wizardTracking->displayProgress();
			$rtn['mssg'] = $e->getMessage();
			$rtn['state'] = 'error';
        }
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        $mssg = $rtn['mssg'];
        $steps = $rtn['steps'];
        $success = $rtn['state'] == 'error' ? false : true;
        $result->setData(['success' => $success, 'mssg' => "$mssg", 'steps'=>$steps]);
        return $result;
    }
 
 
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CustomerParadigm_AmazonPersonalize::config');
    }
}
