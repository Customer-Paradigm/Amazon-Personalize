<?php
namespace CustomerParadigm\AmazonPersonalize\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Readonly extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        $val = $element->getValue();
        $newval = $val == 1 ? 'yes' : 'no';
        $element->setValue($newval);
        return $element->getElementHtml();
    }
}
