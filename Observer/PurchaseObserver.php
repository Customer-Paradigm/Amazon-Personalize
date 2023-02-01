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
