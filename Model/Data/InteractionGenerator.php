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
        $reportInteractions = $this->interactionReportCollectionFactory->create()->addFieldToFilter('last_visit_at', array('gt' => '2019-01-01'));
        $purchaseInteractions = $this->interactionPurchaseCollectionFactory->create()->addFieldToFilter('sales_order.updated_at', array('gt' =>  '2018-02-01'));

        $this->createWriter()
            ->writeHeadersToCsv()
            ->writeCollectionToCsv($reportInteractions)
            ->writeCollectionToCsv($purchaseInteractions)
            ->closeWriter();

        return $this;
    }
}
