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

class WizardRunStepsAfterObserver implements ObserverInterface
{
    protected $awsEvents;
    protected $request;


    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Events $awsEvents,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->awsEvents = $awsEvents;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    }
}
