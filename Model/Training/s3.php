<?php
namespace CustomerParadigm\AmazonPersonalize\Model\Training;

use Aws\S3\S3Client;

class s3
{
    protected $nameConfig;
    protected $s3Bucketname;
    protected $s3Client;
    protected $region;
    protected $varDir;

    public function __construct(
        \CustomerParadigm\AmazonPersonalize\Model\Training\NameConfig $nameConfig
    )
    {
        $this->nameConfig = $nameConfig;
        $this->region = $this->nameConfig->getAwsRegion();
        $this->s3BucketName = $this->nameConfig->buildName('personalize-s3bucket');
		$this->varDir = $this->nameConfig->getVarDir();
        $this->s3Client =   new S3Client(
            [ 'profile' => 'default',
            'version' => 'latest',
            'region' => "$this->region" ]
		);
    }
/* Maybe later
    public function createS3BucketAsync() {
		$this->createS3Bucket();	
        $waiterName = 'BucketExists';
        $waiterOptions = [
            'Bucket' => $this->s3BucketName, 
			'@waiter' => [
				'delay'       => 3,
				'maxAttempts' => 5
			]
		];
        try {
			$waiter = $this->s3Client->getWaiter($waiterName, $waiterOptions);
			$promise = $waiter->promise();
			$promise
				->then(function () {
					echo "Waiter completed\n";
				})
				->otherwise(function (\Exception $e) {
					echo "Waiter failed: " . $e . "\n";
				});

			// Block until the waiter completes or fails. Note that this might throw
			// a \RuntimeException if the waiter fails.
			$promise->wait();
        } catch(\Exception $e) {
			$this->nameConfig->getLogger('error')->error( "\ncreate bucket async error : \n" . $e->getMessage());
        }
    }
*/
    
    public function createS3Bucket() {
        try {
            //$this->checkBucketExists($this->s3BucketName);
            $result = $this->s3Client->createBucket([
                'ACL' => 'authenticated-read',
                'Bucket' => $this->s3BucketName, 
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => $this->region,
                ],
            ]);
		} catch(\Exception $e) {
			$this->nameConfig->getLogger('error')->error( "\ncreate bucket error : \n" . $e->getMessage());
		}
		$this->nameConfig->saveName('s3BucketName', $this->s3BucketName);
        $this->addS3BucketPolicy();
        return $result;
    }

    public function addS3BucketPolicy() {
        $result = $this->s3Client->putBucketPolicy([
            'Bucket' => $this->s3BucketName,
            'Policy' => '{
            "Version": "2012-10-17",
            "Id": "PersonalizeS3BucketAccessPolicy",
            "Statement": [
                {
                    "Sid": "PersonalizeS3BucketAccessPolicy",
                    "Effect": "Allow",
                    "Principal": {
                        "Service": "personalize.amazonaws.com"
                    },
                    "Action": "s3:*",
                    "Resource": [
                        "arn:aws:s3:::'.$this->s3BucketName.'",
                        "arn:aws:s3:::'. $this->s3BucketName.'/*"
                    ]
                }
             ]
            }',
        ]);
    }

    public function uploadCsvFiles() {
        $files = $this->getCsvFiles();
        foreach( $files as $type => $file ) {
            $this->uploadFileToS3($type, $file);
        }
    }

    public function getBucketStatus() {
        return $this->checkBucketExists() ? 'complete' : 'not started';
    }

    public function checkBucketExists() {
        $buckets = $this->listS3Buckets();
        foreach($buckets['Buckets'] as $bucket) {
            if($bucket['Name'] == $this->s3BucketName) {
                return true;
            }
        }
        return false;
    }

    public function listS3Buckets() {
        return $this->s3Client->listBuckets([
        ]);
    }

    public function getUploadStatus() {
		$result = $this->s3Client->listObjects([
			'Bucket' => $this->s3BucketName,
			'Delimiter' => ',',
        ]);
		if(!empty($result) || ! array_key_exists('Contents',$result) ) {
            $files = $result['Contents'];
			if(count($files) == 3) {
				return 'complete';
			} elseif( count($files) > 0 ) {
				return 'in progress';
			}	
		} else {
			return 'not started';
		}
	} 

    
    public function deleteS3Bucket($name) {
        return $this->s3Client->deleteBucket([
            'Bucket' => $name,
        ]);
    }
    
    public function deleteCsvs($bucketname) {
        return $this->s3Client->deleteObjects ([
			'Bucket' => $bucketname, 
			'Delete' => [ 
				'Objects' => [ 
					[
						'Key' => 'interactions.csv', // REQUIRED
					],
					[
						'Key' => 'users.csv', // REQUIRED
					],
					[
						'Key' => 'items.csv', // REQUIRED
					],
				],
				'Quiet' => true,
			],
		]);
    }

    protected function uploadFileToS3($type, $filepath) {
        $data = file_get_contents($filepath);
        $result = $this->s3Client->putObjectAsync([
            'ACL' => 'authenticated-read',
            'Body' => $data,
            'Bucket' => $this->s3BucketName,
            'Key' => $type . ".csv",
        ])->wait();
    }

    protected function getCsvFiles() {
        $filenames = array();
        $csvDir = $this->varDir . "/export/amazonpersonalize/";
        $filelist = scandir($csvDir, SCANDIR_SORT_DESCENDING);
        foreach( $filelist as $item ) {
            $breakout = explode('-',$item);
            $type = $breakout[0];
            if( $type == "." || $type == "..") {
                continue;
            }
            if(! array_key_exists($type, $filenames) ) {
               $filenames[$type] = $csvDir . $item;
            }
        }
        return $filenames;
    }
}
