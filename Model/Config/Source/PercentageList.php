<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Config\Source;

class PercentageList implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
        ['value' => '100', 'label' => __('100% Control / 0% test (Default Magento is used 100% of the time)')],
        ['value' => '75', 'label' => __('75% Control / 25% test')],
        ['value' => '50', 'label' => __('50% Control / 50% test (50% of users will see Amazon Personalize / 50% will see default Magento)')],
        ['value' => '25', 'label' => __('25% Control / 75% test')],
        ['value' => '10', 'label' => __('10% Control / 90% test')],
        ['value' => '0', 'label' => __('0% Control / 100% test (Amazon Personalize is used 100% of the time)')]
        ];
    }
}
