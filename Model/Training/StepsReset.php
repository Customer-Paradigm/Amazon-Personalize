<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Training;

class StepsReset extends PersonalizeBase
{
    protected $nameConfig;
    protected $wizardTracking;
    protected $s3Client;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig,
		\CustomerParadigm\AmazonPersonalize\Model\Training\WizardTracking $wizardTracking,
		\CustomerParadigm\AmazonPersonalize\Model\Training\s3 $s3Client
    ) {
        parent::__construct($nameConfig);
		$this->wizardTracking = $wizardTracking;
		$this->s3Client = $s3Client;
    }

    protected function processAssets($assets){
        foreach($assets as $idx=>$item) {
            if(strpos($item['path'],'solutionVersionName') !== false ||
                strpos($item['path'],'solutionVersionArn') !== false ||
                strpos($item['path'],'ImportJobName') !== false ||
                strpos($item['path'],'ImportJobArn') !== false
                ) {
                unset($assets[$idx]); 
            }
        }
        return array_reverse($assets);
    }

    protected function filterName($name) {
        $filter = array('Arn','Name');
        foreach($filter as $item) {
            $name = str_replace($item, '', $name);
        }
        return $name;
    }
    
    protected function filterType($name) {
        $filter = array('users','items','interactions');
        foreach($filter as $item) {
            $name = str_replace($item, '', $name);
        }
        return $name;
    }
    
    protected function filterAssets($name) {
        $replace = strpos($name, 'test') !== false ? 'Dataset' : '';
        foreach($filter as $item) {
            $name = str_replace($item, '', $name);
        }
        return $name;
    }

	public function execute() {
		$resetTracking = array();
        $assets = $this->processAssets($this->wizardTracking->getAssets());
        foreach($assets as $item) {
            $tmp = explode("/",$item['path']);
            $name = $this->filterName($tmp[2]);
            $asset = $this->filterType($name);

            if( array_key_exists($name, $resetTracking)) {
                $resetTracking[$name]['name'] = $item['value'];
            } else {
                $arn = $name == 's3Bucket' ? 'name' : 'arn';
                $resetTracking[$name][$arn] = $item['value'];
            }

            $resetTracking[$name]['asset'] = $asset;
        }

        foreach( $resetTracking as $item ) {
            if( $item['asset'] == 's3Bucket' ) {
                $this->deleteS3Bucket($item);
            } elseif($item['asset'] == 'Dataset') {
                // last data item, delete EVENT_INTERACTIONS too
                if(strstr($item['arn'],'USERS')) {
                    $tmp = explode('/',$item['arn']);
                    $event_arn = $tmp[0] . '/' . $tmp[1] . '/EVENT_INTERACTIONS';
                    $this->deleteAsset('Dataset',$event_arn);
                }
                $this->deleteAsset($item['asset'],$item['arn']);
            }
            else {
                $this->deleteAsset($item['asset'],$item['arn']);
            }
        }
        $this->wizardTracking->clearData();
	}

    public function assetExists($name,$arn) {
        
        $ucname = ucfirst($name);
        $lcname = lcfirst($name);
        $rtn = false;
        if(strpos($name, 'File')) {
            return $rtn;
        }
        $fname = 'list' . $ucname . 's';
        $info = $this->personalizeClient->{$fname}();
        foreach($info[$lcname.'s'] as $item) {
            if(array_key_exists($lcname."Arn", $item) && $item[$lcname."Arn"] == $arn) {
                $rtn = true;
                break;
            } 
        }
        return $rtn;
    }

    public function deleteAsset($name, $arn) {
        $ucname = ucfirst($name);
        $lcname = lcfirst($name);
		$fname = 'delete' . $ucname . 'Async';
        if($this->assetExists($name, $arn)) {
            $result = $this->personalizeClient->{$fname}([
                $lcname . 'Arn' => $arn,
            ])->wait();
        }
    }

    public function deleteS3Bucket($array) {
        if($this->s3Client->checkBucketExists() ) {
            $this->s3Client->deleteCsvs($array['name']);
            $this->s3Client->deleteS3Bucket($array['name']);
        }
    }



}