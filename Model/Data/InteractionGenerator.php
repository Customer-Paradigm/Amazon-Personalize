<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Data;

use CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\ReportEvent\CollectionFactory as
    InteractionReportCollectionFactory;
use CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\PurchaseEvent\CollectionFactory as
    IinteractionPurchaseCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\WriteFactory;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;

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


    private $interactionPurchaseCollectionFactory;
    private $interactionReportCollectionFactory;

    public function __construct(
        InteractionReportCollectionFactory $interactionReportCollectionFactory,
        IinteractionPurchaseCollectionFactory $interactionPurchaseCollectionFactory,
        WriteFactory $writeFactory,
        DirectoryList $directoryList,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger,
        File $file
    ){
        $this->interactionReportCollectionFactory = $interactionReportCollectionFactory;
        $this->interactionPurchaseCollectionFactory = $interactionPurchaseCollectionFactory;
        parent::__construct($writeFactory, $directoryList, $file);
        $this->infoLogger = $infoLogger;
        $this->errorLogger = $errorLogger;
    }

    public function generateCsv()
    {
        try {
            $rstart_date =  date("Y-m-d", strtotime("-6 months"));
            $pstart_date =  date("Y-m-d", strtotime("-6 months"));
            $max_records = 2000;
            $reportInteractions = $this->interactionReportCollectionFactory->create()->addFieldToFilter('last_visit_at', array('gt' => $rstart_date))->setOrder('event_id','desc')->setPageSize($max_records);
            $purchaseInteractions = $this->interactionPurchaseCollectionFactory->create()->addFieldToFilter('sales_order.updated_at', array('gt' =>  $pstart_date))->setOrder('order_id','desc')->setPageSize($max_records);


            $rcount = count($reportInteractions);
            $pcount = count($purchaseInteractions);
            $total = (int)$rcount + (int)$pcount;

            $this->createWriter()
                ->writeHeadersToCsv()
                ->writeCollectionToCsv($reportInteractions)
                ->writeCollectionToCsv($purchaseInteractions)
                ->closeWriter();
            // Aws needs at least 1000 interactions
            if($total < 1000) {
                $this->setDataError("too_few_interactions");
				$this->errorLogger->error("Not enough interactions in csv. Total: $total");
            }
        } catch(Exception $e) {
            $mssg = $e->getMessage();
			$this->errorLogger->error("InteractionGenerator Processing error: $mssg");
        }

        return $this;
	}

}
