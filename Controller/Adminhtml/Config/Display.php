<?php
 
namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
 
class Display extends Action
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
        $rtn = array();
        $mssg = null;
	try {
		if(array_key_exists('license',$rtn) && $rtn['license'] === false ) {
			$rtn['steps'] = array();
			$rtn['mssg'] = 'License or AWS credentials are incorrect';
			$rtn['state'] = 'error';
		} else {

			$rtn['steps'] = $this->wizardTracking->displayProgress();
			$rtn['mssg'] = '';
			$rtn['state'] = 'success';
		}

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
