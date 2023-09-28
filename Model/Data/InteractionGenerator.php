<?php
/**
 * CustomerParadigm_AmazonPersonalize
 *
 * @category   CustomerParadigm
 * @package    CustomerParadigm_AmazonPersonalize
 * @copyright  Copyright (c) 2023 Customer Paradigm (https://customerparadigm.com/)
 * @license    https://github.com/Customer-Paradigm/Amazon-Personalize/blob/master/LICENSE.md
 */

declare(strict_types=1);

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
    protected $enablePadding = true;
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
    protected $itemCount;


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
    ) {
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

            // bypass all the collection counting if file is already created with > 1000 entries
            $file_total = $this->checkActualFileCount();
            if ($file_total > 1001) {
                $this->setDataError(null);
                return $this;
            }

            $reportInteractions = $this->interactionReportCollectionFactory->create()->addFieldToFilter('last_visit_at', ['gt' => $rstart_date])->setOrder('event_id', 'desc')->setPageSize($max_records);
            $purchaseInteractions = $this->interactionPurchaseCollectionFactory->create()->addFieldToFilter('sales_order.updated_at', ['gt' =>  $pstart_date])->setOrder('order_id', 'desc')->setPageSize($max_records);
            $addedInteractions = $this->interactionCheckCollectionFactory->create()->setOrder('interaction_check_id', 'desc')->setPageSize($max_records);

            $rcount = count($reportInteractions);
            $pcount = count($purchaseInteractions);
            $acount = count($addedInteractions);

            $total = (int)$rcount + (int)$pcount + (int)$acount;

            $this->infoLogger->info("InteractionGenerator reportInteractions count: $rcount");
            $this->infoLogger->info("InteractionGenerator purchaseInteractions count: $pcount");
            $this->infoLogger->info("InteractionGenerator addedInteractions count: $acount");
            $this->infoLogger->info("InteractionGenerator total count: $total");

            $this->createWriter()
                ->writeHeadersToCsv()
                ->writeCollectionToCsv($reportInteractions)
                ->writeCollectionToCsv($purchaseInteractions)
                ->writeCollectionToCsv($addedInteractions);
            // pad interactions if fewer than 1000

            if ($this->enablePadding && $total < 1000) {
                $this->infoLogger->info("InteractionGenerator padding enabled");
                $count = 1;
                $diff = 1010 - $total; // a few extra
            //    $total = 0; // reset
                while ($diff > 0) {
                    $user_id = 1000 % $diff;
                    $item_id = 1010 - $diff;
                    $timestamp = time();
                    $this->writer->writeCsv([$user_id,$item_id,'none',$timestamp]);
                    $diff--;
                    $count++;
                }
                $total += $count;
            }
            $this->setItemCount($total);
            $this->writer->close();
            $this->pHelper->setConfigValue("awsp_settings/awsp_general/order-interactions-count", $rcount + $pcount);
            $this->pHelper->setConfigValue("awsp_settings/awsp_general/file-interactions-count", $total);
            // Aws needs at least 1000 interactions
            if ($total < 1000) {
                $this->setDataError("too_few_interactions:$total");
                $this->errorLogger->error("Not enough interactions in csv. Total: $total");
            }
        } catch (Exception $e) {
            $mssg = $e->getMessage();
            $this->errorLogger->error("InteractionGenerator Processing error: $mssg");
        }
        return $this;
    }

    public function checkActualFileCount()
    {
        $linecount = 0;
        $current_file_path = $this->pHelper->getConfigValue('awsp_wizard/data_type_name/interactionUserFile');
        if (!empty($current_file_path)) {
            // Don't include header in data count
            $linecount = count(file($current_file_path)) - 1;
            $this->setItemCount($linecount);
        }
        $this->infoLogger->info("InteractionGenerator file path: $current_file_path");
        $this->infoLogger->info("InteractionGenerator actual file count: $linecount");
        return $linecount;
    }

    public function getItemCount()
    {
        return $this->itemCount;
    }

    public function setItemCount($ct)
    {
        $this->itemCount = $ct;
    }
}
