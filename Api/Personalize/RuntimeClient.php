<?php

namespace CustomerParadigm\AmazonPersonalize\Api\Personalize;

use Aws\PersonalizeRuntime\PersonalizeRuntimeClient;
use Aws\PersonalizeEvents\PersonalizeEventsClient;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;
use CustomerParadigm\AmazonPersonalize\Helper\Data;

class RuntimeClient implements RuntimeClientInterface
{
    protected $pRuntimeClient;
    protected $pHelper;
    protected $pConfig;

    public function __construct(
        // PersonalizeRuntimeClient $pRuntimeClient
        PersonalizeConfig $pConfig,
        Data $pHelper
    ) {
        $this->pConfig = $pConfig;
        $homedir = $this->pConfig->getUserHomeDir();
        $region = $this->pConfig->getAwsRegion();

        putenv("HOME=$homedir");

        // TODO: make this a factory instead of instantiating here
        $this->pHelper = $pHelper;
        $this->pRuntimeClient = new PersonalizeRuntimeClient(
            [
            // 'profile' => 'default',
            'version' => 'latest',
            'region' => "$region" ]
        );
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
        $data = [];
        if ($this->pHelper->canDisplay()) {
            $count = intval($count);
            $data = $this->pRuntimeClient->getRecommendations([
                'campaignArn' => $campaignArn, // REQUIRED
                'numResults' => $count,
                'userId' => "$userId",
            ]);
        }
        return $data;
    }

    /**
     * @api
     * @param string $campaignArn
     * @param array $inputList
     * @param string $usrId
     * @return string (JSON)
     */
//    public function getPersonalizedRanking($campaignArn,$inputList,$userId);
}
