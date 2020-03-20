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
        $this->campaignVersionName = $this->nameConfig->buildName('campaign-version');
    }

    public function createcampaign() {
        $solutionVersionArn = $this->nameConfig->getArn('solutionVersionArn');
        $result = $this->personalizeClient->{$this->apiCreate}([
            'minProvisionedTPS' => 1,
            'name' => $this->campaignName,
            'solutionVersionArn' => $solutionVersionArn,
        ]
        )->wait();
		$this->nameConfig->saveName('campaignName', $this->campaignName);
		$this->nameConfig->saveArn('campaignArn', $result['campaignArn']);
	
		return $result;
    }

    public function getStatus() {
        try {
            $arn = $this->nameConfig->getArn('campaignArn');
			$rslt = $this->personalizeClient->{$this->apiDescribe}([
					'campaignArn' => $arn,
                ]);
        } catch (\Exception $e) {
            $this->nameConfig->getLogger()->error( "\ncampaign getStatus error: " . $e->getMessage());
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
