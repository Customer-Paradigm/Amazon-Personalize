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

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

/*
use Aws\Iam\IamClient;
Use Aws\Sts\StsClient;
 */
use Aws\Exception\AwsException;

class Iam extends PersonalizeBase
{
    protected $nameConfig;
    protected $region;
    protected $varDir;
    protected $sdkClient;
    protected $IamClient;
    protected $stsClient;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
        \CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient
    ) {
        parent::__construct($nameConfig, $sdkClient);
        $this->region = $this->nameConfig->getAwsRegion();
        $this->varDir = $this->nameConfig->getVarDir();
        $this->sdkClient = $sdkClient;
        $this->IamClient = $this->sdkClient->getClient('Iam');
        $this->stsClient = $this->sdkClient->getClient('Sts');
    }

    public function createPersonalizeS3Role()
    {
        try {
            $result = $this->stsClient->getSessionToken();
            $credentials = $this->stsClient->createCredentials($result);
            $this->iamClient = IamClient::factory([
                'profile' => 'default',
                'version' => 'latest',
                'region' => "$this->region",
                'credentials' => $credentials
            ]);
            $result = $this->iamClient->createRole([
                'AssumeRolePolicyDocument' => '{"Version":"2012-10-17",
				"Statement":[{
				"Effect":"Allow",
					"Principal":{"Service":["personalize.amazonaws.com"]},
					"Action":["sts:AssumeRole"]}]}',
                    'RoleName' => 'PersonalizeS3AcessRole'
                ])->wait();
            $this->infoLogger->info($result);
            $this->nameConfig->saveArn('personalizeS3RoleArn', $result[0]['Arn']);
        } catch (AwsException $e) {
            $this->errorLogger->error($e->getMessage());
        }
    }

    public function getStatus()
    {
        try {
            $arn = $this->nameConfig->getArn('userPolicyArn');
        } catch (\Exception $e) {
            $this->wizardTracking->setStepError('create_personalize_s3_role', $e->getMessage());
        }
        if (empty($arn)) {
            return 'not started';
        } else {
            return 'complete';
        }
    }

    public function listRoles()
    {
        $result = $this->IamClient->ListRoles();
        return $result;
    }

    public function assumeRole()
    {
        $ARN = "arn:aws:iam::138144570375:role/service-role/AmazonPersonalize-ExecutionRole-1571349773158";
        $sessionName = "iam-access-role";
        $result = $this->stsClient->AssumeRole([
          'RoleArn' => $ARN,
              'RoleSessionName' => $sessionName,
        ]);
        return $result;
    }
}
