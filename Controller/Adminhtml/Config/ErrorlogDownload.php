<?php
 
namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
use CustomerParadigm\AmazonPersonalize\Model\Error;
 
class ErrorlogDownload extends Action
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
        $this->array_csv_download($log);
    }

    public function array_csv_download($array, $filename = "aws_campaign_logs.csv", $delimiter = ";")
    {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        // clean output buffer
        ob_end_clean();
    
        $handle = fopen('php://output', 'w');

        // use keys as column titles
        fputcsv($handle, array_keys($array['0']), $delimiter);

        foreach ($array as $value) {
            fputcsv($handle, $value, $delimiter);
        }

        fclose($handle);

        // use exit to get rid of unexpected output afterward
        exit();
    }
 
 
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CustomerParadigm_AmazonPersonalize::config');
    }
}
