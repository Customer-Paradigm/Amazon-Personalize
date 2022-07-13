<?php
namespace CustomerParadigm\AmazonPersonalize\Observer;

use Magento\Framework\Event\ObserverInterface;
use CustomerParadigm\AmazonPersonalize\Helper\Aws;

class AdminpageloadObserver implements ObserverInterface
{
    protected $awsHelper;

    public function __construct(
        Aws $awsHelper
    ) {
        $this->awsHelper = $awsHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	    $this->awsHelper->populateEc2CheckVal();
    }
}
