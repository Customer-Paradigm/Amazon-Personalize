<?php
namespace CustomerParadigm\AmazonPersonalize\Api\Personalize;

use Aws\PersonalizeEvents\PersonalizeEventsClient;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;

class EventsClient implements EventsClientInterface {
    
    protected $pEventsClient;
    protected $pConfig;

    public function __construct(
        // PersonalizeEventsClient $pEventsClient
        PersonalizeConfig $pConfig
    ) {
        $this->pConfig = $pConfig;
        $homedir = $this->pConfig->getUserHomeDir();
        $region = $this->pConfig->getAwsRegion();

        putenv("HOME=$homedir");

		// TODO: make this a factory instead of instantiating here
        $this->pEventsClient = new PersonalizeEventsClient(
            [ 
            'profile' => 'default',
            'version' => 'latest',
            'region' => "$region" ]
        );
    }
/*

data format for putEvents:

POST /events HTTP/1.1
Content-type: application/json

{
   "eventList": [ 
      { 
         "eventId": "string",
         "eventType": "string",
         "properties": "string",
         "sentAt": number
      }
   ],
   "sessionId": "string",
   "trackingId": "string",
   "userId": "string"
}
*/
 
    /**
     * @api
     * @param array $eventlist
     */
    public function putEvents($eventlist) {
        if( $this->pConfig->isEnabled() ) {
                $this->pEventsClient->putEvents( $eventlist );
        }
    }
}
