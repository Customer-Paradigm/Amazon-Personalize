<?php
/**
 * CustomerParadigm_AmazonPersonalize
 *
 * @category   CustomerParadigm
 * @package    CustomerParadigm_AmazonPersonalize
 * @copyright  Copyright (c) 2023 Customer Paradigm (https://customerparadigm.com/)
 * @license    https://github.com/Customer-Paradigm/Amazon-Personalize/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace CustomerParadigm\AmazonPersonalize\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class ReadonlyField extends \Magento\Config\Block\System\Config\Form\Field
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
