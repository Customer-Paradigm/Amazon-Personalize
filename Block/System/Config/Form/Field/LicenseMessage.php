<?php
namespace CustomerParadigm\AmazonPersonalize\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LicenseMessage extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(AbstractElement $element)
    {
	$val = $element->getValue();
	$val = is_null($val) ? '' : $val;
        $newval = strpos(strtolower($val), 'error') !== false ? $val : '';
        $pattern = '/^Configuration.+salt;/';
        $newval = preg_replace($pattern, '', $newval);
        $pattern = '/Configuration.+installation;/';
        $newval = preg_replace($pattern, '', $newval);
        $element->setValue($newval);
        return $element->getValue();
    }
}
