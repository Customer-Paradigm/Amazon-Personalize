<?php
/**
 * * @author Customer Paradigm
 * * @copyright Copyright (c) 2020 Customer Paradigm (https://www.customerparadigm.com/)
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
	 * * Logging level
	 * *
	 * * @var int
	 **/
	protected $loggerType = MonologLogger::INFO;
	protected $dirlist;

	public function __construct(
		DirectoryList $dirlist,
		DriverInterface $filesystem,
		$filePath = null,
		$fileName = null
	) {
		$this->dirlist = $dirlist;
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
	public function write(array $record): void
	{
		parent::write($record);
	}
}
