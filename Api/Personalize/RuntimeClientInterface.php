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

interface RuntimeClientInterface
{
    /**
     * @api
     * @param string $campaignArn
     * @param string $userId
     * @param string $itemId
     * @return string (JSON)
     */
    public function getRecommendations($campaignArn, $userId = null, $itemId = null);
}
