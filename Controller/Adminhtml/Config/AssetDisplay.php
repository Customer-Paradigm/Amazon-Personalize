<?php
 
namespace CustomerParadigm\AmazonPersonalize\Controller\Adminhtml\Config;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Data;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;
use CustomerParadigm\AmazonPersonalize\Model\Asset;
 
class AssetDisplay extends Action
{
    protected $resultJsonFactory;
    protected $loggerInterface;
    protected $helper;
    protected $wizardTracking;
    protected $asset;
 
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
        Asset $asset
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->loggerInterface = $loggerInterface;
        $this->helper = $helper;
        $this->wizardTracking = $wizardTracking;
        $this->asset = $asset;
        parent::__construct($context);
    }
 
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $data = [];
        $result = $this->resultJsonFactory->create();
        $dispData = $this->asset->getAwsDisplayData();
        $settings = $dispData['settings'];
        $assets = $dispData['assets'];
        foreach ($settings as $item) {
            $data[] = $item;
        }
        foreach ($assets as $item) {
            $data[] = $item;
        }
        $result->setData($data);
        return $result;
    }
 
 
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('CustomerParadigm_AmazonPersonalize::config');
    }
}
