<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use Aws\Personalize\PersonalizeClient;

class EventTracker extends PersonalizeBase
{
    protected $eventTrackerName;
    protected $eventTrackerArn;
    protected $pHelper;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
        \CustomerParadigm\AmazonPersonalize\Helper\Data $pHelper,
        \CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient
    ) {
        parent::__construct($nameConfig, $sdkClient);
        $this->eventTrackerName = $this->nameConfig->buildName('eventTracker');
        $this->eventTrackerArn = $this->nameConfig->buildArn('event-tracker', '');
        $this->eventTrackerVersionName = $this->nameConfig->buildName('eventTracker-version');
        $this->pHelper = $pHelper;
    }

    public function createEventTracker()
    {
        $datasetGroupArn = $this->nameConfig->getArn('datasetGroupArn');
        try {
            $result = $this->personalizeClient->{$this->apiCreate}([
                'name' => $this->eventTrackerName,
                'datasetGroupArn' => $datasetGroupArn,
                ])->wait();
        } catch (\Exception $e) {
            $mssg = $e->getMessage();
            $exists = strpos($mssg, 'ResourceAlreadyExistsException');
            if ($exists === false) {
                return false;
            } else {
                if ($aarn = $this->getAssetArn('eventTrackers', $this->eventTrackerName)) {
                    $this->nameConfig->saveName('eventTrackerName', $this->eventTrackerName);
                    $this->nameConfig->saveArn('eventTrackerArn', $aarn);
                }
                return true;
            }
        }
        $this->nameConfig->saveName('eventTrackerName', $this->eventTrackerName);
        $this->nameConfig->saveArn('eventTrackerArn', $result['eventTrackerArn']);

        return $result;
    }

    public function getStatus()
    {
        if ($this->assetExists('eventTrackers', $this->eventTrackerName)) {
            return 'complete';
        }

        try {
            $rslt = $this->personalizeClient->{$this->apiDescribe}([
                'eventTrackerArn' => $arn,
            ]);
        } catch (\Exception $e) {
            $this->nameConfig->getLogger()->error("\neventTracker getStatus error: " . $e->getMessage());
            return $e->getMessage();
        }
        if (empty($rslt)) {
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
                $this->pHelper->setStepError('create_event_tracker', $rslt['eventTracker']['failureReason']);
                $rtn = 'error';
                break;
            default:
                $rtn = 'not started';
                break;
        }

        return $rtn;
    }
}
