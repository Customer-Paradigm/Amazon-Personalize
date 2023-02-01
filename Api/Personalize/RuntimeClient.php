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

namespace CustomerParadigm\AmazonPersonalize\Api\Personalize;

use CustomerParadigm\AmazonPersonalize\Model\PersonalizeAwsClient;
use Aws\PersonalizeEvents\PersonalizeEventsClient;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;
use CustomerParadigm\AmazonPersonalize\Helper\Data;

class RuntimeClient implements RuntimeClientInterface
{
    protected PersonalizeConfig $pConfig;
    protected PersonalizeAwsClient $pRuntimeClient;
    protected Data $pHelper;

    /**
     * @param PersonalizeConfig $pConfig
     * @param PersonalizeAwsClient $pRuntimeClient
     * @param Data $pHelper
     */
    public function __construct(
        PersonalizeConfig $pConfig,
        PersonalizeAwsClient $pRuntimeClient,
        Data $pHelper
    ) {
        $this->pConfig = $pConfig;
        $this->pRuntimeClient = $pRuntimeClient;
        $this->pHelper = $pHelper;
    }

    /**
     * @api
     * @param string $campaignArn
     * @param string $userId
     * @param string $itemId
     * @return string (JSON)
     */
    public function getRecommendations($campaignArn, $userId = null, $count = 30, $itemId = null)
    {
        $pRuntimeClient = $this->pRuntimeClient->pRuntimeClient();

        $data = [];
        if ($this->pHelper->canDisplay()) {
            $count = intval($count);
            $data = $pRuntimeClient->getRecommendations([
                'campaignArn' => $campaignArn, // REQUIRED
                'numResults' => $count,
                'userId' => "$userId",
            ]);
        }
        return $data;
    }
}
