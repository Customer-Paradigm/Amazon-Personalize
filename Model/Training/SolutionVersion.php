<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class SolutionVersion extends PersonalizeBase
{
	protected $solutionVersionName;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
    {
        parent::__construct($nameConfig);
        $this->solutionVersionName = $this->nameConfig->buildName('solution-version');
    }

    public function createSolutionVersion() {
		$solutionArn = $this->nameConfig->getArn('solutionArn');
        $result = $this->personalizeClient->{$this->apiCreate}([
            'solutionArn' => $solutionArn,
            'trainingMode' => 'FULL',
        ]
        )->wait();
        $this->solutionVersionArn = $result['solutionVersionArn'];
        
		$this->nameConfig->saveName('solutionVersionName', $this->solutionVersionName);
		$this->nameConfig->saveArn('solutionVersionArn', $result['solutionVersionArn']);
		return $result;
    }
    
    public function getStatus() {
        try {
			$arn = $this->nameConfig->getArn('solutionVersionArn');
			$rslt = $this->personalizeClient->{$this->apiDescribe}([
					'solutionVersionArn' => $arn,
                ]);
        } catch (\Exception $e) {
            $this->nameConfig->getLogger('error')->error( "\ncampaignVersion getStatus error: " . $e->getMessage());
            return $e->getMessage();
        }
        if(empty($rslt)) {
            return 'not started';
        }

        switch ($rslt['solutionVersion']['status']) {
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
