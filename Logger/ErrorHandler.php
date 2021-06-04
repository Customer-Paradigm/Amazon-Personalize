<?php 
/** 
 ** @author Customer Paradigm 
 ** @copyright Copyright (c) 2020 Customer Paradigm (https://www.customerparadigm.com/) 
 **/ 
namespace CustomerParadigm\AmazonPersonalize\Logger; 

use Magento\Framework\Filesystem\DriverInterface;
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
	protected $error;

	public function __construct(
		DriverInterface $filesystem,            
		$filePath = null,
		$fileName = null,
		\CustomerParadigm\AmazonPersonalize\Model\Error $error
	) { 
		$this->error = $error;
		parent::__construct(
			$filesystem,
			'var/log/aws_personalize/',
			'error.log'
		);

	}

	/**
	 * @inheritDoc
	 */
	public function write(array $record)
	{
		$this->error->writeError($record);
		parent::write($record);
	}

} 
