<?php

namespace CustomerParadigm\AmazonPersonalize\Model\Data;

use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use \Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\WriteFactory;
use CustomerParadigm\AmazonPersonalize\Logger\InfoLogger;
use CustomerParadigm\AmazonPersonalize\Logger\ErrorLogger;

class UserGenerator extends \CustomerParadigm\AmazonPersonalize\Model\Data\AbstractGenerator
{
    /*
     * Array containing csv header keys
     */
    protected $csvHeaders = [
        "USER_ID",
        "GROUP",
//        "EMAIL",
        "COUNTRY",
        "CITY",
        "STATE",
 //       "STREET",
        "POSTCODE"
    ];

    protected $filename = "users";
    protected $infoLogger;
    protected $errorLogger;


    private $customerCollectionFactory;

    private $groupRepository;

    public function __construct(
        CollectionFactory $customerCollectionFactory,
        GroupRepositoryInterface $groupRepository,
        WriteFactory $writeFactory,
        DirectoryList $directoryList,
        InfoLogger $infoLogger,
        ErrorLogger $errorLogger,
        File $file
    ) {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->groupRepository = $groupRepository;
        parent::__construct($writeFactory, $directoryList, $file);
        $this->infoLogger = $infoLogger;
        $this->errorLogger = $errorLogger;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomerGroupCode($customer)
    {
        return $this->groupRepository
            ->getById($customer->getGroupId())
            ->getCode();
    }

    private function getCustomerAddressData($customer)
    {
        $shipAddress = $customer->getDefaultShippingAddress();

        $country = self::DEFAULT_NULL_DATA_VALUE;
        $city = self::DEFAULT_NULL_DATA_VALUE;
        $state = self::DEFAULT_NULL_DATA_VALUE;
//        $street = self::DEFAULT_NULL_DATA_VALUE;
        $postcode = 0;

        if ($shipAddress) {
            $country = $this->parseNullData($shipAddress->getCountryId());
            $city = $this->parseNullData($shipAddress->getCity());
            $state = $this->parseNullData($shipAddress->getRegion());
            $postcode = $this->parseNullData($shipAddress->getPostcode());
/*
            $street_array = $shipAddress->getStreet();
            if (!empty($street_array)) {
                $street = "";
                foreach($street_array as $item) {
                    $street .= $item;
                }
            }
*/
        }

        $data[] = $country;
        $data[] = $city;
        $data[] = $state;
//        $data[] = $street;
        $data[] = $postcode;

        return $data;
    }

    /**
     * @param \Magento\Customer\Model\Customer $customer
     * @return array
     */
    private function getUserDataFromCustomer($customer)
    {
        $data = [];
        $data[] = $this->parseNullData($customer->getId());
        $data[] = $this->parseNullData($this->getCustomerGroupCode($customer));
//        $data[] = $this->parseNullData($customer->getEmail());

        $data = array_merge($data, $this->getCustomerAddressData($customer));

        return $data;
    }
    
    private function getPaddedUserDataFromCustomer($num, $customer)
    {
        $data = [];
        $data[] = $this->parseNullData("cpgen$num-" . $customer->getId());
        $data[] = $this->parseNullData($this->getCustomerGroupCode($customer));
//        $data[] = $this->parseNullData($customer->getEmail());

        $data = array_merge($data, $this->getCustomerAddressData($customer));

        return $data;
    }

    private function writeCustomersToCsv($customers)
    {
        $count = count($customers);
        foreach ($customers as $customer) {
            $this->writer->writeCsv($this->getUserDataFromCustomer($customer));
        }
    // pad customer csv file if fewer than 25 customers
        while ($count < 25) {
    
            foreach ($customers as $customer) {
                $this->writer->writeCsv($this->getPaddedUserDataFromCustomer($count, $customer));
                $count++;
            }
        }

        return $this;
    }

    public function generateCsv()
    {
        try {
            /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $customers */
            //$customers = $this->customerCollectionFactory->create()->addFieldToFilter('created_at', array('gt' =>  '2017-09-15'));
            $customers = $this->customerCollectionFactory->create();
            $count = count($customers);

            $this->createWriter()
                ->writeHeadersToCsv()
                ->writeCustomersToCsv($customers)
                ->closeWriter();
        } catch (Exception $e) {
            $mssg = $e->getMessage();
            $this->errorLogger->error("UserGenerator Processing error: $mssg");
        }

        return $this;
    }
}
