<?php

namespace CustomerParadigm\AmazonPersonalize\Block\Adminhtml\Config;

use Magento\Framework\View\Element\Template;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\Dir\Reader;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;

class Train extends Field
{
    /**
     * @var string
     */
    protected $_template = 'CustomerParadigm_AmazonPersonalize::system/config/training_section.phtml';
    protected $dirReader;
    protected $tracking;

    public function __construct(
        Context $context,
        Reader $dirReader,
        WizardTracking $tracking,
        array $data = []
    ) {
        $this->dirReader = $dirReader;
        $this->tracking = $tracking;
        parent::__construct($context, $data);
    }
    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for start training button
     *
     * @return string
     */
    public function getAjaxRunUrl()
    {
        return $this->getUrl('cpaws/config/train') . "?isAjax=true";
    }
    
    /**
     * Return ajax url for license status check
     *
     * @return string
     */
    public function getAjaxLicenseCheckUrl()
    {
        return $this->getUrl('cpaws/config/licensecheck') . "?isAjax=true";
    }

    /**
     * Return ajax url for error log entries
     *
     * @return string
     */
    public function getAjaxErrorlogUrl()
    {
        return $this->getUrl('cpaws/config/errorlog') . "?isAjax=true";
    }
    
    /**
     * Return ajax url for asset display
     *
     * @return string
     */
    public function getAjaxAssetDisplayUrl()
    {
        return $this->getUrl('cpaws/config/assetdisplay') . "?isAjax=true";
    }
    
    /**
     * Return ajax url for error log entries
     *
     * @return string
     */
    public function getAjaxErrorlogDownloadUrl()
    {
        return $this->getUrl('cpaws/config/errorlogdownload') . "?isAjax=true";
    }
    
    /**
     * Return ajax url for interactions progress gauge
     *
     * @return string
     */
    public function getAjaxInteractionUrl()
    {
        return $this->getUrl('cpaws/config/gauge') . "?isAjax=true";
    }

    /**
     * Return ajax url for steps display
     *
     * @return string
     */
    public function getAjaxDisplayUrl()
    {
        return $this->getUrl('cpaws/config/display');
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'training_section',
                'onClick' => 'startProcess',
                'label' => __('Start Process nnn'),
            ]
        );

        return $button->toHtml();
    }
    
    /**
     * Get image Url
     *
     * @param  string $type
     * @return string
     */
    public function getImageUrl($type)
    {
        $rtn = $this->getViewFileUrl('CustomerParadigm_AmazonPersonalize::images/success.gif');

        switch ($type) {
            case 'refresh':
                    $rtn = $this->getViewFileUrl('CustomerParadigm_AmazonPersonalize::images/refresh.png');
                break;
            case 'info':
                    $rtn = $this->getViewFileUrl('CustomerParadigm_AmazonPersonalize::images/info.png');
                break;
            case 'pending':
                $rtn = $this->getViewFileUrl('CustomerParadigm_AmazonPersonalize::images/pending.png');
                break;
            case 'processing':
                $rtn = $this->getViewFileUrl('CustomerParadigm_AmazonPersonalize::images/processing.gif');
                break;
            case 'error':
                $rtn = $this->getViewFileUrl('CustomerParadigm_AmazonPersonalize::images/error.gif');
                break;
            case 'success':
                $rtn = $this->getViewFileUrl('CustomerParadigm_AmazonPersonalize::images/success.gif');
                break;
        }
        return $rtn;
    }

    public function getProcessStatus()
    {
        return $this->tracking->getProcessStatus()['status'];
    }

    public function needsInteractions()
    {
        return $this->tracking->pConfig->needsInteractions();
    }
    
    public function interactionsCount()
    {
        return $this->tracking->pConfig->getInteractionsCount();
    }
}
