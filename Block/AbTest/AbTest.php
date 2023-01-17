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

namespace CustomerParadigm\AmazonPersonalize\Block\AbTest;

use Magento\Framework\View\Element\Template;

class AbTest extends Template
{
    protected $customerSession;
    protected $abTracking;
    protected $pConfig;
    protected $data;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \CustomerParadigm\AmazonPersonalize\Model\AbTracking $abTracking,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->abTracking = $abTracking;
        $this->pConfig = $pConfig;
    }

    public function getAbTrackingType()
    {
        $sid =  $this->customerSession->getSessionId();
        return $this->abTracking->getTrackingType($sid);
    }

    public function getGaAccountNum()
    {
        $acctnum = $this->pConfig->getMagentoGaAccountNum();
        return $acctnum;
    }

    public function getGaAbEnabled()
    {
        $acctnum = $this->pConfig->getGaAbEnabled();
        return $acctnum;
    }

    public function hasGlobalSiteTag()
    {
        return $this->pConfig->checkHeaderSiteTag();
    }
}
