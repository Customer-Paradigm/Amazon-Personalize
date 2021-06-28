<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class Campaign extends PersonalizeBase
{
	protected $campaignName;
	protected $pHelper;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
                \CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper
	)
    {
        parent::__construct($nameConfig);
        $this->campaignName = $this->nameConfig->buildName('campaign');
        $this->campaignArn = $this->nameConfig->buildArn('campaign', $this->campaignName);
	$this->campaignVersionName = $this->nameConfig->buildName('campaign-version');
	$this->pHelper = $pHelper;
    }

	public function createCampaign() {
		$result = array();
			if( ! $this->checkAssetCreatedAndSync('','campaign',$this->campaignName,$this->campaignArn) ) {
				$this->infoLogger->info( "\ncampaign createCampaign() checkAssetCreatedAndSync returns false, camapaign: " . $this->campaignName);

				$solutionVersionArn = $this->nameConfig->getArn('solutionVersionArn');
				$result = $this->personalizeClient->{$this->apiCreate}([
					'minProvisionedTPS' => 1,
					'name' => $this->campaignName,
					'solutionVersionArn' => $solutionVersionArn,
				]
				)->wait();
				$this->nameConfig->saveName('campaignName', $this->campaignName);
				$this->nameConfig->saveArn('campaignArn', $result['campaignArn']);
			}
		return $result;
	}

	public function getStatus() {
	$rslt = array();
	$created = $this->checkAssetCreatedAndSync('','campaign',$this->campaignName,$this->campaignArn);
	if( ! $created ) {
		$this->infoLogger->info( "\ncampaign getStatus() checkAssetCreatedAndSync false, camapaign: " . $this->campaignName);
		return 'not started';
	}

        try {
		$arn = $this->nameConfig->buildArn('campaign', $this->campaignName);
		$rslt = $this->personalizeClient->{$this->apiDescribe}([
			'campaignArn' => $arn,
                ]);
        } catch (\Exception $e) {
            $this->nameConfig->getLogger()->info( "\ncampaign getStatus error: " . $e->getMessage());
	    $this->pHelper->setStepError('create_campaign',$e->getMessage());
            return 'error';
        }
	if(empty($rslt)) {
            return 'not started';
        }

        switch ($rslt['campaign']['status']) {
            case 'ACTIVE':
                $rtn = 'complete';
                break;
            case 'CREATE PENDING':
	    case 'CREATE IN_PROGRESS':
                $rtn = 'in progress';
                break;
	    case 'CREATE FAILED':
                $this->pHelper->setStepError('create_campaign',$rslt['campaign']['failureReason']);
                $rtn = 'error';
                break;
            default:
                $rtn = 'not started';
                break;

        }
        return $rtn;

    }

}
