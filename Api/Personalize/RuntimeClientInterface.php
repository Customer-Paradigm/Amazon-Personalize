<?php

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

    /**
     * @api
     * @param string $campaignArn
     * @param array $inputList
     * @param string $usrId
     * @return string (JSON)
     */
//    public function getPersonalizedRanking($campaignArn,$inputList,$userId);
}
