<?php

namespace CustomerParadigm\AmazonPersonalize\Observer;

use Magento\Framework\Event\ObserverInterface;

class OrderAttributeObserver implements ObserverInterface
{
    protected $awsEvents;
    protected $pConfig;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Events $awsEvents,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig
    ) {
        $this->awsEvents = $awsEvents;
        $this->pConfig = $pConfig;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->pConfig->getGaAbEnabled()) {
            $user_type = $this->awsEvents->personalizeAbType();
            $observer->getOrder()->setAbCustomerType($user_type);
        }
    }
}
