<?php

namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Model\Training\Wizard;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;

class Train extends Action
{
    protected $resultJsonFactory;
    protected $infoLogger;
    protected $errorLogger;
    protected $helper;
    protected $wizard;
    protected $wizardTracking;
    protected $pConfig;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param LoggerInterface $loggerInterface
     * @param Wizard $wizard
     * @param WizardTracking $wizardTracking
     * @param PersonalizeConfig $pConfig
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Data $helper,
        Wizard $wizard,
        WizardTracking $wizardTracking,
        PersonalizeConfig $pConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->wizard = $wizard;
        $this->wizardTracking = $wizardTracking;
        $this->pConfig = $pConfig;
        $this->infoLogger = $pConfig->getLogger('info');
        $this->errorLogger = $pConfig->getLogger('error');

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->infoLogger->info("------------AWS data setup cotroller execute-----------");

        $rtn = [];
        $mssg = null;
        try {
            $procStatus =  $this->wizardTracking->getProcessStatus()['status'];
            // Enable/disable cron based on process status
            if ($procStatus == 'hasError' || $procStatus == 'finished') {
                $this->infoLogger->info("Aws data setup Cron off -------------");
                $this->pConfig->setCron('aws_data_setup', 'off');
            } else {
                $this->infoLogger->info("Aws data setup Cron on -------------");
                $this->pConfig->setCron('aws_data_setup', 'on');
            }
            $rtn = $this->wizard->execute();
        } catch (\Exception $e) {
            $err_mssg = "AWS API error: " . $e->getMessage();
            $this->errorLogger->error($err_mssg);
            $rtn = ['mssg'=>$err_mssg,'steps'=>[], 'state'=>'error'];
        }
        /* @var \Magento\Framework\Controller\Result\Json $result */
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
