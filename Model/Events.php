<?php

namespace CustomerParadigm\AmazonPersonalize\Model;
use Aws\Exception\AwsException;
use Aws\PersonalizeRuntime\PersonalizEventsClient;

class Events
{
    protected $awsException;
    protected $eventsClient;
    protected $pConfig;
    protected $abTracking;
    protected $customerSession;
    protected $checkoutSession;
    protected $registry;
    protected $queryFactory;
    protected $directoryList;
    protected $logger;
    protected $trackingId;
    protected $debugLog;
    protected $debugLogFile;

    public function __construct(
    	\CustomerParadigm\AmazonPersonalize\Api\Personalize\EventsClient $eventsClient,
    	\CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        \CustomerParadigm\AmazonPersonalize\Model\AbTracking $abTracking,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Registry $registry,
        \Magento\Search\Model\QueryFactory $queryFactory,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    )
    {
        $this->eventsClient = $eventsClient;
        $this->pConfig = $pConfig;
        $this->abTracking = $abTracking;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
        $this->queryFactory = $queryFactory;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
        $this->trackingId = 'c61e8b60-0fee-44e3-867a-660d77d45e20';
        $this->debugLog = false && $this->pConfig->isEnabled();
        $this->debugLogFile = $this->directoryList->getRoot() . "/var/log/event-debug.log";
    }

    public function putObsAddtocart($observer, $request) {
        try {
            $params = $request->getParams();
            $cust_id = $this->_findCustomerId();
            $sess_id = $this->customerSession->getSessionId();
            $cust_email = $this->_findCustomerEmail();
            $prod = null;
            $items = $observer->getEvent()->getItems();
            foreach($items as $item) {
                $eventsList = null;
                $events = array();
                $prod = $item->getProduct();
                $itemId = $prod->getId();
                $itemType = $prod->getTypeId();
                $itemName = addslashes($prod->getName());
                $eventType = "checkout_cart_add_product";
                $eventValue = $prod->getFinalPrice();
                $eventQty   = array_key_exists('qty',$params) ? $params['qty'] : 1;

                $event = array('sentAt'=>time(), 
                'eventType'=>$eventType, 'eventValue'=>$eventValue, 'eventQty'=>$eventQty,
                'properties'=>"{\"customerEmail\":\"$cust_email\",\"itemId\":\"$itemId\",\"itemType\":\"$itemType\", \"itemName\":\"$itemName\"}");
                $events[] = $event;

                $eventsList = array('eventList'=>$events, 
                    'sessionId'=>"$sess_id", 'trackingId'=>$this->trackingId, 'userId'=>"$cust_id");

                $this->eventsClient->putEvents($eventsList);
                if($this->debugLog) {
                    file_put_contents($this->debugLogFile, "\nAdd to cart", FILE_APPEND);
                    file_put_contents($this->debugLogFile, print_r($eventsList,true), FILE_APPEND);
                }
            }

        } catch(\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }
    }

    public function putObsPurchase($observer) {
        try {
            $prod = null;
            $items = $observer->getEvent()->getOrder()->getItems();
            $cust_email = $this->_findCustomerEmail();

            foreach($items as $item) {
                $prod = $item->getProduct();
                $cust_id = $this->_findCustomerId();
                $sess_id = $this->customerSession->getSessionId();
                $itemId = $prod->getId();
                $itemType = $prod->getTypeId();
                $itemName = addslashes($prod->getName());
                $eventType = "checkout_purchase_product";
                $eventValue = $prod->getFinalPrice();
                $eventQty = $item->getQtyOrdered();
                $event = array('sentAt'=>time(), 
                'eventType'=>$eventType, 'eventValue'=>$eventValue, 'eventQty'=>$eventQty,
                'properties'=>"{\"customerEmail\":\"$cust_email\",\"itemId\":\"$itemId\",\"itemType\":\"$itemType\", \"itemName\":\"$itemName\"}");
                $events[] = $event;
            }
            $eventsList = array('eventList'=>$events, 
                'sessionId'=>"$sess_id", 'trackingId'=>$this->trackingId, 'userId'=>"$cust_id");

            if($this->debugLog) {
                file_put_contents($this->debugLogFile, "\nPurchase", FILE_APPEND);
                file_put_contents($this->debugLogFile, print_r($eventsList,true), FILE_APPEND);
            }
            $this->eventsClient->putEvents($eventsList);
        } catch(\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }
    }

    public function setAbTracking($observer, $request) {
        $item = $this->_getPageviewItemInfo($request);
        $trackingType = $item['persAbType'];
        $this->customerSession->setAbTrackingType($trackingType);
    }

    public function putObsPageView($observer, $request) {
        $item = $this->_getPageviewItemInfo($request);
        $itemId = $item['itemId'];
        $itemType = $item['type'];
        $itemName = $item['name'];
        $eventType = $item['eventType'];
        $eventValue = $item['eventValue'];
        $time = time();
        $sess_id = null;
        $cust_id = null;
        try {
            $sess_id = $this->customerSession->getSessionId();
            $cust_id = $this->_findCustomerId();
            $cust_email = $this->_findCustomerEmail();
            $event = array('sentAt'=>time(), 'eventType'=>$eventType, 'eventValue'=>$eventValue,'properties'=>"{\"customerEmail\":\"$cust_email\",\"itemId\":\"$itemId\",\"itemType\":\"$itemType\", \"itemName\":\"$itemName\"}");
	    $eventsList = array('eventList'=>array($event), 'sessionId'=>"$sess_id", 'trackingId'=>$this->trackingId, 'userId'=>"$cust_id");

            if($this->debugLog) {
                file_put_contents($this->debugLogFile, "\n=========", FILE_APPEND);
                file_put_contents($this->debugLogFile, print_r($eventsList,true), FILE_APPEND);
            }
            $this->eventsClient->putEvents($eventsList);
        } catch(\Exception $e) {
            $this->logger->critical('Error message', ['exception' => $e]);
        }
    }

    protected function _findCustomerId() {
        $sess_id = $this->customerSession->getSessionId();
        $cust_id = $sess_id;

        if( $this->customerSession->isLoggedIn() )  {
            $cust_id = $this->customerSession->getCustomer()->getId();
        }
        return $cust_id;
    }

    protected function _findCustomerEmail() {
        $cust_email = 'none';

        if( $this->customerSession->isLoggedIn() )  {
            $cust_email = $this->customerSession->getCustomer()->getEmail();
        }
        return $cust_email;
    }

    protected function _getProductPageviewPrice($product) {
        $price = $product->getFinalPrice();
        $type = $product->getTypeId();
        if($type == "bundle" || $type == "configurable" ) {
            $priceObj=$product->getPriceInfo()->getPrice('final_price');
            $minRaw = $priceObj->getMinimalPrice()->getValue();
            $maxRaw = $priceObj->getMaximalPrice()->getValue();
            $price = "$minRaw - $maxRaw";
        }
        return $price;
    }

    protected function _getPageviewItemInfo($request) {
        $action = $request->getFullActionName();
        $info = array();
        $id = "";
        $name = "";
        $abType = $this->personalizeAbType();

        if( $action == 'checkout_index_index' ) {
            $id = "0";
            $name = $this->queryFactory->get()->getQueryText();
            $info['eventValue'] = 0;
            $info['eventType'] = "checkout-page";
            $info['type'] = "checkout";
            $info['eventValue'] = 0;
        } 
        if( $action == 'catalogsearch_result_index' ) {
            $id = "0";
            $name = $this->queryFactory->get()->getQueryText();
            $info['eventValue'] = 0;
            $info['eventType'] = "search";
            $info['type'] = "search";
            $info['eventValue'] = 0;
        } 
        if( $action == 'catalog_category_view' ) {
            $id =  $this->_getCurrentCategory()->getId();
            $name =  $this->_getCurrentCategory()->getName();
            $info['eventValue'] = '0';
            $info['eventType'] = "category-view";
            $info['type'] = "category";
            $info['eventValue'] = 0;
        } 
        if( $action == 'catalog_product_view' ) {
            $prod = $this->_getCurrentProduct();
            $id = $prod->getId();
            $name = $prod->getName();
            $price =  $this->_getProductPageviewPrice($prod);
            $type = $prod->getTypeId();
            $info['type'] = $type;
            $info['eventType'] = "product-view";
            $info['eventValue'] = $price;
        } 
        $info['itemId'] = "$id";
        $info['name'] = "$name";
        $info['persAbType'] = $abType;

        return $info;
    }

    protected function _getCurrentCategory()
    {        
        return $this->registry->registry('current_category');
    }

    protected function _getCurrentProduct()
    {        
        return $this->registry->registry('current_product');
    }  

    public function personalizeAbType()
    {   
        $sess_id = $this->customerSession->getSessionId(); 
        return $this->abTracking->getTrackingType($sess_id);
    }  
}
