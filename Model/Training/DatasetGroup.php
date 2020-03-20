<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

Use CustomerParadigm\AmazonPersonalize\Model\Training;

class DatasetGroup extends PersonalizeBase
{
	protected $datasetGroupName;

	public function __construct(
		\CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
	)
    {
        parent::__construct($nameConfig);
        $this->datasetGroupName = $this->nameConfig->buildName('dataset-group');
    }

    public function createDatasetGroup() {
        try {
			//$result = $this->personalizeClient->createDatasetGroupAsync([
			$result = $this->personalizeClient->{$this->apiCreate}([
				'name' => $this->datasetGroupName,
			])->wait();
			$this->nameConfig->saveName('datasetGroupName', $this->datasetGroupName);
			$this->nameConfig->saveArn('datasetGroupArn', $result['datasetGroupArn']);
		} catch(\Exception $e) {
			$this->nameConfig->getLogger()->error( "\ncreate dataset group error : \n" . $e->getMessage());
        }

        return $result;
    }

    public function getStatus() {
        $rslt = array();
        try {
            $arn = $this->nameConfig->getArn('datasetGroupArn');
            $rslt = $this->personalizeClient->{$this->apiDescribe}([
                'datasetGroupArn' => $arn,
            ]);
        } catch (\Exception $e) {
			$this->nameConfig->getLogger()->error( "\ndescribe dataset group error : \n" . $e->getMessage());
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
