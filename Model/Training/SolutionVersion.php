<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class SolutionVersion extends PersonalizeBase
{
	protected $solutionVersionName;
	protected $pHelper;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
		\CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper
	)
	{
		parent::__construct($nameConfig);
		$this->solutionVersionName = $this->nameConfig->buildName('solution-version');
		$this->pHelper = $pHelper;
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
			$this->nameConfig->getLogger('info')->info( "\nsolutionVersion arn: " . $arn);
			$this->nameConfig->getLogger('info')->info( "\nsolutionVersion result: " . print_r($rslt['solutionVersion']['status'],true));
		} catch (\Exception $e) {
			$this->pHelper->setStepError('create_solution_version', $e->getMessage());
			$this->nameConfig->getLogger('error')->error( "\nsolutionVersion getStatus error: " . $e->getMessage());
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
			$this->pHelper->setStepError('create_solution_version',$rslt['solutionVersion']['failureReason']);
			$rtn = 'error';
			break;
		default:
			$rtn = 'not started';
			break;

		}

		return $rtn;
	}

}
