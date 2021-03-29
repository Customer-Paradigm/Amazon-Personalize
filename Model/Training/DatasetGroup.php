<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use CustomerParadigm\AmazonPersonalize\Model\Training;

class DatasetGroup extends PersonalizeBase
{
	protected $datasetGroupName;
	protected $datasetGroupArn;
	protected $wizardTracking;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
                \CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking $wizardTracking
	)
    {
        parent::__construct($nameConfig);
        $this->datasetGroupName = $this->nameConfig->buildName('dataset-group');
        $this->datasetGroupArn = $this->nameConfig->buildArn('dataset-group',$this->datasetGroupName);
        $this->wizardTracking = $wizardTracking;
    }

    public function createDatasetGroup() {
	$result = array();

        try {
	        if( ! $this->checkAssetCreatedAndSync('','datasetGroup',$this->datasetGroupName,$this->datasetGroupArn) ) {
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => $this->datasetGroupName,
			])->wait();
			$this->nameConfig->saveName('datasetGroupName', $this->datasetGroupName);
			$this->nameConfig->saveArn('datasetGroupArn', $result['datasetGroupArn']);
		}
	} catch(\Exception $e) {
		$this->nameConfig->getLogger()->error( "\ncreate dataset group error: " . $e->getMessage());
		$this->wizardTracking->setStepError('create_dataset_group',$e->getMessage());
        }

        return $result;
    }

    public function getStatus() {
	if( ! $this->checkAssetCreatedAndSync('','datasetGroup',$this->datasetGroupName,$this->datasetGroupArn) ) {
		return 'not started';
	}
        try {
            $arn = $this->nameConfig->getArn('datasetGroupArn');
            $rslt = $this->personalizeClient->{$this->apiDescribe}([
                'datasetGroupArn' => $arn,
            ]);
        } catch (\Exception $e) {
		$this->errorLogger->error( "\ndescribe dataset group error: " . $e->getMessage());
		$this->wizardTracking->setStepError('create_dataset_group',$e->getMessage());
        }
        if(empty($rslt)) {
            return 'not started';
        }

        switch ($rslt['datasetGroup']['status']) {
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
