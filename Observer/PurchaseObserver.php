<?php

namespace CustomerParadigm\AmazonPersonalize\Observer;

use Magento\Framework\Event\ObserverInterface;

class PurchaseObserver implements ObserverInterface
{
    protected $awsEvents;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Events $awsEvents
    ) {
        $this->awsEvents = $awsEvents;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->awsEvents->putObsPurchase($observer);
    }
}
