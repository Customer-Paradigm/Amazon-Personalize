<?php

namespace CustomerParadigm\AmazonPersonalize\Api;

interface AwsSdkClientInterface
{
    public function getClient($type);
}
