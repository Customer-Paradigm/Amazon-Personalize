<?php
namespace CustomerParadigm\AmazonPersonalize\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class LicenseMessage extends  \Magento\Config\Block\System\Config\Form\Field
{    
    protected function _getElementHtml(AbstractElement $element)
    {
	$val = $element->getValue();
        $newval = strpos($val,'error') !== false ? $val : '';
        $element->setValue($newval);
        return $element->getValue();
    }
}
