<?php
/**
 * @author CustomerParadigm Team
 * @copyright Copyright (c) 2019 CustomerParadigm (https://www.customerparadigm.com)
 * @package CustomerParadigm_AmazonPersonalize
 */


namespace CustomerParadigm\AmazonPersonalize\Block\Adminhtml\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Information extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var string
     */
    private $licenseLink = '<a target=blank href="https://www.customerparadigm.com/amazon-personalize-magento/">https://www.customerparadigm.com/amazon-personalize-magento</a>';

    /**
     * @var string
     */
    private $content;

    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->content = "<span id='personalize-system-message'><div>Deliver personalized, real-time recommendations using Amazon Personalize - a machine learning system.</div>
                <div class='personalize-message-line'>Free 15 day trial, A-B Split testing built in, no design file changes are needed.</div>
                <div class='personalize-message-line'> More details / get license key: </div>
		<div class='personalize-message-link'>$this->licenseLink</div>
		<div>&nbsp;</div>
                <div class='personalize-message-line note'>Note: If you reinstall or update the Amazon Personalize extension and then encounter license errors</div>
                <div class='personalize-message-line note'>please click on the license key link above and re-submit your key request.</div>
		</span>";
        $html = $this->_getHeaderHtml($element);
        $this->setContent(__("$this->content"));

        $this->_eventManager->dispatch(
            'CustomerParadigm_base_add_information_content',
            ['block' => $this]
        );

        $html .= $this->getContent();
        $html .= $this->_getFooterHtml($element);

        $html = str_replace(
            'CustomerParadigm_information]" type="hidden" value="0"',
            'CustomerParadigm_information]" type="hidden" value="1"',
            $html
        );
        $html = preg_replace('(onclick=\"Fieldset.toggleCollapse.*?\")', '', $html);

        return $html;
    }

    /**
     * @return string
     */
    public function getLicenseLink()
    {
        return $this->LicenseLink;
    }

    /**
     * @param string $LicenseLink
     */
    public function setLicenseLink($licenseLink)
    {
        $this->LicenseLink = $licenseLink;
    }



    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
