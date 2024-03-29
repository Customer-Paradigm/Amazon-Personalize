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

use Aws\PersonalizeEvents\PersonalizeEventsClient;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;

class EventsClient implements EventsClientInterface
{
    protected PersonalizeConfig $pConfig;

    /**
     * @param PersonalizeConfig $pConfig
     */
    public function __construct(
        PersonalizeConfig $pConfig
    ) {
        $this->pConfig = $pConfig;
    }

    /**
     * @api
     * @param array $eventlist
     */
    public function putEvents($eventlist)
    {
        $homedir = $this->pConfig->getUserHomeDir();
        $region = $this->pConfig->getAwsRegion();

        putenv("HOME=$homedir");

        // TODO: make this a factory instead of instantiating here
        $this->pEventsClient = new PersonalizeEventsClient(
            [
            'profile' => 'default',
            'version' => 'latest',
            'region' => "$region"
            ]
        );

        if ($this->pConfig->isEnabled()) {
            $this->pEventsClient->putEvents($eventlist);
        }
    }
}
