<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class Solution extends PersonalizeBase
{
	protected $solutionName;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
    {
        parent::__construct($nameConfig);
        $this->solutionName = $this->nameConfig->buildName('solution');
        $this->solutionVersionName = $this->nameConfig->buildName('solution-version');
    }

    public function createSolution() {
		$datasetGroupArn = $this->nameConfig->getArn('datasetGroupArn');
        $result = $this->personalizeClient->{$this->apiCreate}([
            'name' => $this->solutionName,
            'datasetGroupArn' => $datasetGroupArn,
            // Auto Ml (machine learning) -- let personalize decide recipe and parameters
            'performAutoML' => true,

            /*
            // Non-auto -- specify recipe and other params
                        'performAutoML' => false,
                        'recipeArn' => $this->recipeArn,
                        'performHPO' => false,
                        "solutionConfig" => [
                            "featureTransformationParameters" => [
                                "cold_start_max_interactions" => "15",
                                "cold_start_relative_from" => "latestItem",
                                "cold_start_max_duration" => "5"
                            ]   
                        ],
            */
        ]
        )->wait();
		$this->nameConfig->saveName('solutionName', $this->solutionName);
		$this->nameConfig->saveArn('solutionArn', $result['solutionArn']);
	
		return $result;
    }

    public function getStatus() {
        try {
            $arn = $this->nameConfig->getArn('solutionArn');
			$rslt = $this->personalizeClient->{$this->apiDescribe}([
					'solutionArn' => $arn,
                ]);
        } catch (\Exception $e) {
            $this->nameConfig->getLogger()->error( "\nsolution getStatus error: " . $e->getMessage());
            return $e->getMessage();
        }
    
    	if(empty($rslt)) {
            return 'not started';
        }

        switch ($rslt['solution']['status']) {
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
