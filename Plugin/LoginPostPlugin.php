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

namespace CustomerParadigm\AmazonPersonalize\Plugin;

class LoginPostPlugin
{
    protected $storeManager;
    protected $customerRepository;
    protected $recommendResult;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \CustomerParadigm\AmazonPersonalize\Model\Result $recommendResult
    ) {
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->recommendResult = $recommendResult;
    }

    /**
     * Check for updated AWS Personalize recommendations for this user.
     *
     * @param \Magento\Customer\Controller\Account\LoginPost $subject
     * @param \Magento\Framework\Controller\Result\Redirect $result
     */
    public function afterExecute(
        \Magento\Customer\Controller\Account\LoginPost $subject,
        $result
    ) {
        if ($subject->getRequest()->isPost()) {
            $login = $subject->getRequest()->getPost('login');
            $email = $login['username'];
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            try {
                $customer = $this->customerRepository->get($email, $websiteId);
            } catch (\Exception $e) {
                return $result;
            }
            $cid = $customer->getId();
            $this->recommendResult->getRecommendation($cid);
        }

        return $result;
    }
}
