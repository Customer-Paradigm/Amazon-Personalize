<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use Aws\Personalize\PersonalizeClient;

class EventTracker extends PersonalizeBase
{
	protected $eventTrackerName;
	protected $pHelper;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
		\CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper,
		\CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient
	)
	{
		parent::__construct($nameConfig, $sdkClient);
		$this->eventTrackerName = $this->nameConfig->buildName('eventTracker');
		$this->eventTrackerVersionName = $this->nameConfig->buildName('eventTracker-version');
		$this->pHelper = $pHelper;
	}

	public function createEventTracker() {
		$datasetGroupArn = $this->nameConfig->getArn('datasetGroupArn');
		$result = $this->personalizeClient->{$this->apiCreate}([
			'name' => $this->eventTrackerName,
			'datasetGroupArn' => $datasetGroupArn,
		]
	)->wait();
		$this->nameConfig->saveName('eventTrackerName', $this->eventTrackerName);
		$this->nameConfig->saveArn('eventTrackerArn', $result['eventTrackerArn']);

		return $result;
	}

	public function getStatus() {
		try {
			$arn = $this->nameConfig->getArn('eventTrackerArn');
			$rslt = $this->personalizeClient->{$this->apiDescribe}([
				'eventTrackerArn' => $arn,
			]);
		} catch (\Exception $e) {
			$this->nameConfig->getLogger()->error( "\neventTracker getStatus error: " . $e->getMessage());
			return $e->getMessage();
		}
		if(empty($rslt)) {
			return 'not started';
		}

		switch ($rslt['eventTracker']['status']) {
		case 'ACTIVE':
			$rtn = 'complete';
			break;
		case 'CREATE PENDING':
		case 'CREATE IN_PROGRESS':
			$rtn = 'in progress';
			break;
		case 'CREATE FAILED':
			$this->pHelper->setStepError('create_event_tracker',$rslt['eventTracker']['failureReason']);
			$rtn = 'error';
			break;
		default:
			$rtn = 'not started';
			break;

		}

		return $rtn;
	}

}
