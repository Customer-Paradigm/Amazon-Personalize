<?php

namespace CustomerParadigm\AmazonPersonalize\Block\Adminhtml\Config;

use Magento\Framework\View\Element\Template;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\Dir\Reader;
use CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking;

class TrainAdvanced extends Train
{
    /**
     * @var string
     */
    protected $_template = 'CustomerParadigm_AmazonPersonalize::system/config/training_section_adv.phtml';

    public function __construct(
        Context $context,
        Reader $dirReader,
        WizardTracking $tracking,
        array $data = []
    ) {
        parent::__construct($context, $dirReader, $tracking, $data);
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
     * Return ajax url for reset training button
     *
     * @return string
     */
    public function getAjaxResetUrl()
    {
        return $this->getUrl('cpaws/config/reset');
    }

    /**
     * Return ajax url for reset campaign button
     *
     * @return string
     */
    public function getAjaxResetCampUrl()
    {
        return $this->getUrl('cpaws/config/resetcamp');
    }

    /**
     * Return ajax url for resume progress button
     *
     * @return string
     */
    public function getAjaxResetLicenseUrl()
    {
        return $this->getUrl('cpaws/config/resetlicense');
    }

    /**
     * Return ajax url for restart cron button
     *
     * @return string
     */
    public function getAjaxResumeUrl()
    {
        return $this->getUrl('cpaws/config/restartcron');
    }
}
