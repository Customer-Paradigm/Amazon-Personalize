<?php 
/** 
 ** @author Customer Paradigm 
 ** @copyright Copyright (c) 2020 Customer Paradigm (https://www.customerparadigm.com/) 
 **/ 
namespace CustomerParadigm\AmazonPersonalize\Logger; 
 
use Magento\Framework\Logger\Handler\Base as BaseHandler; 
use Monolog\Logger as MonologLogger; 
 
/** 
 *  * Class Handler 
 *   */ 
class ErrorHandler extends BaseHandler 
{ 
        /** 
         ** Logging level 
         ** 
         ** @var int 
         **/ 
        protected $loggerType = MonologLogger::ERROR; 
 
        /** 
         ** File name 
         ** 
         ** @var string 
         **/ 
        protected $fileName = '/var/log/aws_personalize/error.log'; 
} 
