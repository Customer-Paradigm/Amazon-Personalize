<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class Solution extends PersonalizeBase
{
	protected $solutionName;
	protected $solutionVersionName;
	protected $recipeArn;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
	{
		parent::__construct($nameConfig);
		$this->solutionName = $this->nameConfig->buildName('solution');
		$this->solutionArn = $this->nameConfig->buildArn('solution',$this->solutionName);

		$this->solutionVersionName = $this->nameConfig->buildName('solution-version');
		$this->recipeArn = 'arn:aws:personalize:::recipe/aws-user-personalization';
	}

	public function createSolution() {
		$result = array();

		try {
			if( ! $this->checkAssetCreatedAndSync('','solution',$this->solutionName,$this->solutionArn) ) {
				$datasetGroupArn = $this->nameConfig->getArn('datasetGroupArn');
				$result = $this->personalizeClient->{$this->apiCreate}([
					'name' => $this->solutionName,
					'datasetGroupArn' => $datasetGroupArn,
					// Auto Ml (machine learning) -- let personalize decide recipe and parameters
					//'performAutoML' => true,

					// Non-auto -- specify recipe and other params
					'performAutoML' => false,
					'recipeArn' => $this->recipeArn,
					'performHPO' => false,
				]
			)->wait();
				$this->nameConfig->saveName('solutionName', $this->solutionName);
				$this->nameConfig->saveArn('solutionArn', $result['solutionArn']);
			}

		} catch(\Exception $e) {
			$this->errorLogger->error( "\ncreate solution error : \n" . $e->getMessage());
			$this->wizardTracking->setStepError('create_solution',$e->getMessage());
		}
		return $result;
	}

	public function getStatus() {
		if( ! $this->checkAssetCreatedAndSync('','solution',$this->solutionName,$this->solutionArn) ) {
			return 'not started';
		}
		try {
			$arn = $this->solutionArn;
			$rslt = $this->personalizeClient->{$this->apiDescribe}([
				'solutionArn' => $arn,
			]);
		} catch (\Exception $e) {
			$this->errorLogger->error( "\nsolution getStatus error: " . $e->getMessage());
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
