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
    protected $infoLogger;
    protected $errorLogger;

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
        \CustomerParadigm\AmazonPersonalize\Model\Training\EventTracker $eventTracker,
        \CustomerParadigm\AmazonPersonalize\Logger\InfoLogger $infoLogger,
        \CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger $errorLogger
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
        $this->infoLogger = $infoLogger;
        $this->errorLogger = $errorLogger;
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
		    if(empty($this->nameConfig->getConfigVal('awsp_wizard/data_type_name/csvUserFile'))) {
			    $generator = $this->userGenerator->generateCsv();
			    $this->nameConfig->saveName("csvUserFile", $generator->getFilePath());
		    }

		    if(empty($this->nameConfig->getConfigVal('awsp_wizard/data_type_name/itemUserFile'))) {
			    $generator = $this->itemGenerator->generateCsv();
			    $this->nameConfig->saveName("itemUserFile", $generator->getFilePath());
		    }

		    $generator = $this->interactionGenerator->generateCsv();
		    $interactionRtn = $this->nameConfig->saveName("interactionUserFile", $generator->getFilePath());
		   // if( $generator->checkActualFileCount() < 1001 ) {
		   // }

		    $intTotal = $generator->getItemCount();
		    $this->nameConfig->saveConfigSetting("awsp_settings/awsp_general/file-interactions-count",$intTotal);
		    $err_mssg = $generator->getDataError();

		    if( strpos($err_mssg, 'too_few_interactions') !== false ) {
			    $mssg_array = explode(":",$err_mssg);
			    $mssg_total = $mssg_array[1];
			    $this->setStepError('create_csv_files',"Interaction file error: You have $mssg_total interactions--you need at least 1000 to train your model");
			    $this->nameConfig->saveConfigSetting("awsp_settings/awsp_general/file-interactions-count",$mssg_total);
		    }

	    } catch (AwsException $e) {
		    $this->setStepError('create_csv_files',$e->getMessage());
	    } catch (\Exception $e) {
		    $this->setStepError('create_csv_files',$e->getMessage());
	    }
	    return array();
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
		$rtn = $this->s3->uploadCsvFiles();

            $this->setStepError('upload_csv_files',print_r($rtn,true));
        } catch (\Exception $e) {
            $this->setStepError('upload_csv_files',$e->getMessage());
        }
    }

    public function createSchemas() {
        try {
	    $this->infoLogger->info("\nCreate Schemas top: \n" );
            $this->schema->createSchemas();
            $this->setStepError('create_schemas','');
	} catch (\Exception $e) {
	    $this->errorLogger->error("\nCreate Schemas error: \n" . $e->getMessage());
            $this->setStepError('create_schemas',$e->getMessage());
        }
    }
    
    public function createDatasetGroup() {
        try {
            $this->datasetGroup->createDatasetGroup();
            $this->setStepError('create_dataset_group','');
        } catch (\Exception $e) {
	    $this->errorLogger->error("\nCreate DatasetGroup error: \n" . $e->getMessage());
            $this->setStepError('create_dataset_group',$e->getMessage());
        }
    }
    
    public function createDatasets() {
        try {
            $this->dataset->createDatasets();
            $this->setStepError('create_datasets','');
        } catch (\Exception $e) {
	    $this->errorLogger->error("\nCreate Dataset error: \n" . $e->getMessage());
            $this->setStepError('create_datasets',$e->getMessage());
        }
    }
    
    public function createImportJobs() {
        try {
            $this->importJob->createImportJobs();
            $this->setStepError('create_datasets','');
        } catch (\Exception $e) {
	    $this->errorLogger->error("\nCreate ImportJobs error: \n" . $e->getMessage());
            $this->setStepError('create_import_jobs',$e->getMessage());
        }
    }
    
	public function createSolution() {
        try {
            $this->solution->createSolution();
            $this->setStepError('create_solution','');
        } catch (\Exception $e) {
	    $this->errorLogger->error("\nCreate Solution error: \n" . $e->getMessage());
            $this->setStepError('create_solution',$e->getMessage());
        }
    }
	
	public function createSolutionVersion() {
        try {
            $this->solutionVersion->createSolutionVersion();
            $this->setStepError('create_solution_version','');
        } catch (\Exception $e) {
	    $this->errorLogger->error("\nCreate SolutionVersion error: \n" . $e->getMessage());
            $this->setStepError('create_solution_version',$e->getMessage());
        }
    }
    
    public function createCampaign() {
	$this->infoLogger->info("\nWizardTracking ) createCampaign() called");
        try {
            $this->campaign->createCampaign();
            $this->setStepError('create_campaign','');
	    $this->infoLogger->info("\nWizardTracking createCampaign() try block");
	} catch (\Exception $e) {
	    $errMssg = $e->getMessage();
		$this->errorLogger->error("\nWizardTracking createCampaign() exception caught: " . $errMssg);
	    if( strpos($errMssg,'LimitExceededException') !== false ) {
		    $readable = 'You have reached the limit for campaigns in ACTIVE state. Please delete one or more, or request a quota increase. More info at: https://docs.aws.amazon.com/servicequotas/latest/userguide/request-quota-increase.html';
		    $this->setStepError('create_campaign',$readable);
	    } else {
		    $this->setStepError('create_campaign',$e->getMessage());
	    }
        }
    }
    
    public function createEventTracker() {
            if($tracker = $this->eventTracker->createEventTracker() === true) {
		    $this->setStepError('create_event_tracker','');
	    } else {
	    	$this->errorLogger->error("\nCreate EventTracker error: \n" . $tracker->getMessage());
            	$this->setStepError('create_event_tracker',$tracker->getMessage());
	    }
    }

    public function checkStatus($step_name) {
	        $this->infoLogger->info("\nWizardTracking runSteps() checkStatus step: " . $step_name);
		$rtn = null;
		switch ($step_name) {
			case 'create_personalize_s3_role':
				$rtn = 'complete';
				break;
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
