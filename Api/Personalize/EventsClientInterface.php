<?php
namespace CustomerParadigm\AmazonPersonalize\Api\Personalize;

interface EventsClientInterface
{
    /**
     * @api
     * @param string (JSON) $args
     */
    public function putEvents($args);
    
}
