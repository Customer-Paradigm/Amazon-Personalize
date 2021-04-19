<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Data;

use CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\ReportEvent\CollectionFactory as
InteractionReportCollectionFactory;
use CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\PurchaseEvent\CollectionFactory as
InteractionPurchaseCollectionFactory;
use CustomerParadigm\AmazonPersonalize\Model\ResourceModel\InteractionCheck\CollectionFactory as
InteractionCheckCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\WriteFactory;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;
use CustomerParadigm\AmazonPersonalize\Helper\Data;

class InteractionGenerator extends \CustomerParadigm\AmazonPersonalize\Model\Data\AbstractGenerator
{
	/*
	 * Array containing csv header keys
	 */
	protected $csvHeaders = [
		'USER_ID',
		'ITEM_ID',
		'EVENT_TYPE',
		'TIMESTAMP'
	];

	protected $filename = "interactions";
	protected $infoLogger;
	protected $errorLogger;
	protected $pHelper;


	private $interactionPurchaseCollectionFactory;
	private $interactionReportCollectionFactory;

	public function __construct(
		InteractionReportCollectionFactory $interactionReportCollectionFactory,
		InteractionPurchaseCollectionFactory $interactionPurchaseCollectionFactory,
		InteractionCheckCollectionFactory $interactionCheckCollectionFactory,
		WriteFactory $writeFactory,
		DirectoryList $directoryList,
		InfoLogger $infoLogger,
		ErrorLogger $errorLogger,
		Data $pHelper,
		File $file
	){
		$this->interactionReportCollectionFactory = $interactionReportCollectionFactory;
		$this->interactionPurchaseCollectionFactory = $interactionPurchaseCollectionFactory;
		$this->interactionCheckCollectionFactory = $interactionCheckCollectionFactory;
		parent::__construct($writeFactory, $directoryList, $file);
		$this->infoLogger = $infoLogger;
		$this->errorLogger = $errorLogger;
		$this->pHelper = $pHelper;
	}

	public function generateCsv()
	{
		try {
			$months = $this->pHelper->getConfigValue('awsp_settings/awsp_general/data_range');
			$rstart_date =  date("Y-m-d", strtotime("-$months months"));
			$pstart_date =  date("Y-m-d", strtotime("-$months months"));
			$max_records = 2000;
			$reportInteractions = $this->interactionReportCollectionFactory->create()->addFieldToFilter('last_visit_at', array('gt' => $rstart_date))->setOrder('event_id','desc')->setPageSize($max_records);
			$purchaseInteractions = $this->interactionPurchaseCollectionFactory->create()->addFieldToFilter('sales_order.updated_at', array('gt' =>  $pstart_date))->setOrder('order_id','desc')->setPageSize($max_records);
			$addedInteractions = $this->interactionCheckCollectionFactory->create()->setOrder('interaction_check_id','desc')->setPageSize($max_records);

			$rcount = count($reportInteractions);
			$pcount = count($purchaseInteractions);
			$acount = count($addedInteractions);
			$total = (int)$rcount + (int)$pcount + (int)$acount;

			$this->createWriter()
				->writeHeadersToCsv()
				->writeCollectionToCsv($reportInteractions)
				->writeCollectionToCsv($purchaseInteractions)
				->writeCollectionToCsv($addedInteractions);
			// pad interactions if fewer than 1000
		/*
	    if($total < 1000) {
		    $count = $total;
		    $diff = 1200 - $total; // a few extra
		    while($diff > 0) {
			    $user_id = 1000 % $diff;
			    $item_id = 1200 - $diff;
			    $timestamp = time();
			    $this->writer->writeCsv(array($user_id,$item_id,'none',$timestamp));
			    $diff--;
		    }
	    }
		 */
			$this->writer->close();
			// Aws needs at least 1000 interactions
			if($total < 1000) {
				$this->setDataError("too_few_interactions:$total");
				$this->errorLogger->error("Not enough interactions in csv. Total: $total");
			}
		} catch(Exception $e) {
			$mssg = $e->getMessage();
			$this->errorLogger->error("InteractionGenerator Processing error: $mssg");
		}
		return $this;
	}

}
