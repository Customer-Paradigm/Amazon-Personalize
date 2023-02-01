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

namespace CustomerParadigm\AmazonPersonalize\Model;

use Aws\CommandInterface;
use Aws\PersonalizeRuntime\PersonalizeRuntimeClient as PersonalizeRuntimeClientAws;
use CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig;

class PersonalizeAwsClient extends PersonalizeRuntimeClientAws
{
    protected PersonalizeConfig $pConfig;

    /**
     * @param PersonalizeConfig $pConfig
     */
    public function __construct(
        PersonalizeConfig $pConfig,
    ) {
        $this->pConfig = $pConfig;
    }

    /**
     * @return PersonalizeRuntimeClientAws
     */
    public function pRuntimeClient(): PersonalizeRuntimeClientAws
    {
        $homedir = $this->pConfig->getUserHomeDir();
        $region = $this->pConfig->getAwsRegion();

        putenv("HOME=$homedir");

        $this->pRuntimeClient = new PersonalizeRuntimeClientAws(
            [
                'profile' => 'default',
                'version' => 'latest',
                'region' => "$region"
            ]
        );

        return $this->pRuntimeClient;
    }
}
