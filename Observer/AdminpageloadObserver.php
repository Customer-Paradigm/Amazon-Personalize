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
