<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;
use Aws\Exception\AwsException;

class Wizard
{
    protected $wizardTracking;
    protected $nameConfig;
    protected $iam;
    protected $userGenerator;
    protected $itemGenerator;
    protected $interactionGenerator;
    protected $s3;
    protected $schema;
    protected $datasetGroup;
    protected $dataset;
    protected $importJob;
    protected $solution;
    protected $solutionVersion;
    protected $campaign;
    protected $eventTracker;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking $wizardTracking,
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Iam $iam,
        \CustomerParadigm\AmazonPersonalize\Model\Data\UserGenerator $userGenerator,
        \CustomerParadigm\AmazonPersonalize\Model\Data\ItemGenerator $itemGenerator,
        \CustomerParadigm\AmazonPersonalize\Model\Data\InteractionGenerator $interactionGenerator,
        \CustomerParadigm\AmazonPersonalize\Model\Training\s3 $s3,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Schema $schema,
        \CustomerParadigm\AmazonPersonalize\Model\Training\DatasetGroup $datasetGroup,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Dataset $dataset,
        \CustomerParadigm\AmazonPersonalize\Model\Training\ImportJob $importJob,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Solution $solution,
        \CustomerParadigm\AmazonPersonalize\Model\Training\SolutionVersion $solutionVersion,
        \CustomerParadigm\AmazonPersonalize\Model\Training\Campaign $campaign,
        \CustomerParadigm\AmazonPersonalize\Model\Training\EventTracker $eventTracker
    )
    {
        $this->wizardTracking = $wizardTracking;
        $this->nameConfig = $nameConfig;
        $this->iam = $iam;
        $this->userGenerator = $userGenerator;
        $this->itemGenerator = $itemGenerator;
        $this->interactionGenerator = $interactionGenerator;
        $this->s3 = $s3;
        $this->schema = $schema;
        $this->datasetGroup = $datasetGroup;
        $this->dataset = $dataset;
        $this->importJob = $importJob;
        $this->solution = $solution;
        $this->solutionVersion = $solutionVersion;
        $this->campaign = $campaign;
        $this->eventTracker = $eventTracker;
    }
    
    public function execute() {
        $this->wizardTracking->initStepsTable(); 
        $result = $this->wizardTracking->runSteps($this);
        return $result;
    }

    public function createPersonalizeUser() {
        $this->iam->listUsers();
    }

    public function createCsvFiles() {
        try {
            $generator = $this->userGenerator->generateCsv();
            $this->nameConfig->saveName("csvUserFile", $generator->getFilePath());
            $generator = $this->itemGenerator->generateCsv();
            $this->nameConfig->saveName("itemUserFile", $generator->getFilePath());
            $generator = $this->interactionGenerator->generateCsv();
            $this->nameConfig->saveName("interactionUserFile", $generator->getFilePath());
        } catch (AwsException $e) {
            $this->setStepError('create_csv_files',$e->getMessage());
        } catch (\Exception $e) {
            $this->setStepError('create_csv_files',$e->getMessage());
        }
        return false;
    }
    
    public function createS3Bucket() {
        $result = false;
        try {
            // $this->s3->createS3BucketAsync();
            $result = $this->s3->createS3Bucket();
            $this->setStepError('create_s3_bucket','');
        } catch (\Exception $e) {
            $this->setStepError('create_s3_bucket',$e->getMessage());
        }
        return $result;
    }

    public function uploadCsvFiles() {
        try {
            $this->s3->uploadCsvFiles();
            $this->setStepError('upload_csvs','');
        } catch (\Exception $e) {
            $this->setStepError('upload_csvs',$e->getMessage());
        }
    }

    public function createSchemas() {
        try {
            $this->schema->createSchemas();
            $this->setStepError('create_schemas','');
        } catch (\Exception $e) {
            $this->setStepError('create_schemas',$e->getMessage());
        }
    }
    
    public function createDatasetGroup() {
        try {
            $this->datasetGroup->createDatasetGroup();
            $this->setStepError('create_dataset_group','');
        } catch (\Exception $e) {
            $this->setStepError('create_dataset_group',$e->getMessage());
        }
    }
    
    public function createDatasets() {
        try {
            $this->dataset->createDatasets();
            $this->setStepError('create_datasets','');
        } catch (\Exception $e) {
            $this->setStepError('create_datasets',$e->getMessage());
        }
    }
    
    public function createImportJobs() {
        try {
            $this->importJob->createImportJobs();
            $this->setStepError('create_datasets','');
        } catch (\Exception $e) {
            $this->setStepError('create_import_jobs',$e->getMessage());
        }
    }
    
	public function createSolution() {
        try {
            $this->solution->createSolution();
            $this->setStepError('create_solution','');
        } catch (\Exception $e) {
            $this->setStepError('create_solution',$e->getMessage());
        }
    }
	
	public function createSolutionVersion() {
        try {
            $this->solutionVersion->createSolutionVersion();
            $this->setStepError('create_solution_version','');
        } catch (\Exception $e) {
            $this->setStepError('create_solution_version',$e->getMessage());
        }
    }
    
    public function createCampaign() {
        try {
            $this->campaign->createCampaign();
            $this->setStepError('create_campaign','');
        } catch (\Exception $e) {
            $this->setStepError('create_campaing',$e->getMessage());
        }
    }
    
    public function createEventTracker() {
        try {
            $this->eventTracker->createEventTracker();
            $this->setStepError('create_event_tracker','');
        } catch (\Exception $e) {
            $this->setStepError('create_event_tracker',$e->getMessage());
        }
    }

    public function checkStatus($step_name) {
		$rtn = null;
		switch ($step_name) {
			case 'create_csv_files':
				$rtn = 'complete';
				break;
			case 'create_s3_bucket':
				$rtn = $this->s3->checkBucketExists();
				break;
			case 'upload_csv_files':
                $rtn = $this->s3->getUploadStatus();
                break;
			case 'create_schemas':
                $rtn = $this->schema->getStatus();
                break;
			case 'create_dataset_group':
                $rtn = $this->datasetGroup->getStatus();
				break;
			case 'create_datasets':
                $rtn = $this->dataset->getStatus();
				break;
			case 'create_import_jobs':
                $rtn = $this->importJob->getStatus();
				break;
            case 'create_solution':
                $rtn = $this->solution->getStatus();
				break;
			case 'create_solution_version':
                $rtn = $this->solutionVersion->getStatus();
				break;
			case 'create_campaign':
                $rtn = $this->campaign->getStatus();
				break;
			case 'create_event_tracker':
                $rtn = $this->eventTracker->getStatus();
                break;
        }
		return $rtn;
    }

    protected function setStepError($step,$message) {
        $this->wizardTracking->saveStepData($step,'error',$message);
    }
}
