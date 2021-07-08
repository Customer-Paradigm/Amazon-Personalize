<?php 
/** 
 ** @author Customer Paradigm 
 ** @copyright Copyright (c) 2020 Customer Paradigm (https://www.customerparadigm.com/) 
 **/ 
namespace CustomerParadigm\AmazonPersonalize\Logger; 

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseHandler; 
use Monolog\Logger as MonologLogger; 
use Magento\Framework\Filesystem\DirectoryList;
 
/** 
 *  * Class Handler 
 *   */ 
class InfoHandler extends BaseHandler 
{ 
        /** 
         ** Logging level 
         ** 
         ** @var int 
         **/ 
        protected $loggerType = MonologLogger::INFO; 
	protected $error;
        protected $dirlist;

        public function __construct(
                DirectoryList $dirlist,
                DriverInterface $filesystem,
                $filePath = null,
                $fileName = null,
                \CustomerParadigm\AmazonPersonalize\Model\Error $error
        ) {
                $this->dirlist = $dirlist;
                $this->error = $error;
                $webroot = $this->dirlist->getRoot();
                parent::__construct(
                        $filesystem,
                        $webroot . '/var/log/aws_personalize/',
                        'info.log'
                );

        }

        /**
         * @inheritDoc
         */
        public function write(array $record)
        {
 //               $this->error->writeError($record);
                parent::write($record);
        }

 
} 
