<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class Campaign extends PersonalizeBase
{
	protected $campaignName;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
    {
        parent::__construct($nameConfig);
        $this->campaignName = $this->nameConfig->buildName('campaign');
        $this->campaignArn = $this->nameConfig->buildArn('campaign', $this->campaignName);
        $this->campaignVersionName = $this->nameConfig->buildName('campaign-version');
    }

    public function createCampaign() {
	$result = array();
	try {    
		if( ! $this->checkAssetCreatedAndSync('','campaign',$this->campaignName,$this->campaignArn) ) {

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
	} catch(\Exception $e) {
		$this->errorLogger->error( "\ncreate campaign error: " . $e->getMessage());
		$this->wizardTracking->setStepError('create_campaign',$e->getMessage());
        }
	
	return $result;
    }

    public function getStatus() {
	if( ! $this->checkAssetCreatedAndSync('','campaign',$this->campaignName,$this->campaignArn) ) {
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
            return $e->getMessage();
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
                $rtn = 'error';
                break;
            default:
                $rtn = 'not started';
                break;

        }
        return $rtn;

    }

}
