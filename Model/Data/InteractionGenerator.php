<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Data;

use CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\ReportEvent\CollectionFactory as
    InteractionReportCollectionFactory;
use CustomerParadigm\AmazonPersonalize\Model\ResourceModel\Data\Interaction\PurchaseEvent\CollectionFactory as
    IinteractionPurchaseCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\WriteFactory;

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

    private $interactionReportCollectionFactory;

    private $interactionPurchaseCollectionFactory;

    public function __construct(
        InteractionReportCollectionFactory $interactionReportCollectionFactory,
        IinteractionPurchaseCollectionFactory $interactionPurchaseCollectionFactory,
        WriteFactory $writeFactory,
        DirectoryList $directoryList,
        File $file
    ){
        $this->interactionReportCollectionFactory = $interactionReportCollectionFactory;
        $this->interactionPurchaseCollectionFactory = $interactionPurchaseCollectionFactory;
        parent::__construct($writeFactory, $directoryList, $file);
    }

    public function generateCsv()
    {
        try {
            $start_date =  date("Y-m-d", strtotime("-6 months"));
            $max_records = 2000;
            $reportInteractions = $this->interactionReportCollectionFactory->create()->addFieldToFilter('last_visit_at', array('gt' => '2019-01-01'));
            $purchaseInteractions = $this->interactionPurchaseCollectionFactory->create()->addFieldToFilter('sales_order.updated_at', array('gt' =>  $start_date))->setOrder('order_id','desc')->setPageSize($max_records);


            $rcount = count($reportInteractions);
            $pcount = count($purchaseInteractions);

            file_put_contents('/home/scott/public_html/wallstreetgreetings/var/log/test.log',"\n Report Count: $rcount", FILE_APPEND); 
            file_put_contents('/home/scott/public_html/wallstreetgreetings/var/log/test.log',"\n Purchase Count: $pcount", FILE_APPEND); 
            file_put_contents('/home/scott/public_html/wallstreetgreetings/var/log/test.log',"\n Total: $rcount + $pcount", FILE_APPEND); 

            $this->createWriter()
                ->writeHeadersToCsv()
                ->writeCollectionToCsv($reportInteractions)
                ->writeCollectionToCsv($purchaseInteractions)
                ->closeWriter();
            // Aws needs at least 1000 interactions
            if((int)$rcount + (int)$pcount < 1000) {
                file_put_contents('/home/scott/public_html/wallstreetgreetings/var/log/test.log',"\n Hit return -------------", FILE_APPEND); 
                $this->setDataError( "too_few_interactions");
            }
        } catch(Exception $e) {
            $mssg = $e->getMessage();
            file_put_contents('/home/scott/public_html/wallstreetgreetings/var/log/test.log',"\n  interaction gen message: $mssg", FILE_APPEND);
        }

        return $this;
    }
}
