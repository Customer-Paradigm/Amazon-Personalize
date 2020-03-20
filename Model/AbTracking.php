<?php

namespace CustomerParadigm\AmazonPersonalize\Model;

class AbTracking extends \Magento\Framework\Model\AbstractModel 
{
    const CACHE_TAG = 'customerparadigm_amazonpersonalize_abtracking';
    protected $_cacheTag = 'customerparadigm_amazonpersonalize_abtracking';
    protected $_eventPrefix = 'customerparadigm_amazonpersonalize_abtracking';
	protected $controlPercent;
	protected $pConfig;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \CustomerParadigm\AmazonPersonalize\Model\Config\PersonalizeConfig $pConfig,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->pConfig = $pConfig;
        $this->controlPercent = $pConfig->getGaAbPercent();
    }

    protected function _construct() { 
        $this->_init('CustomerParadigm\AmazonPersonalize\Model\ResourceModel\AbTracking');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }

    public function getTrackingType($session_id) {
        // Bypass code for cases that don't need it.
        if( $this->controlPercent === '100' ) {
            return 'control';
        }
        if( $this->controlPercent === '0') {
            return 'personalize';
        }

        $collection = $this->getCollection()
            ->addFieldToFilter('customer_session_id', $session_id);
        $item = $collection->getFirstItem();
		
		// If session id hasn't been tracked, track it
		if( empty($item->getAbTrackingId()) ) {
			// save the opposite value of getIsControl
			$isPersonalize = ! $this->getIsControl();
			$this->saveData($session_id, $isPersonalize );	
			return $isPersonalize == 1 ? 'personalize' : 'control';
        }
		return $item->getUsingPersonalize() == 1 ? 'personalize' : 'control';
    }

	public function getIsControl() {
        // Control 100 percent means all users are control
		if($this->controlPercent == 100) {
			return true;
        }
		// Control percent 0 means all users are personalize (non-control)
		if($this->controlPercent == 0) {
			return true;
        }

        $rowCount =  $this->getCollection()->getSize();	

		$denom = $this->controlPercent < 50 ? $this->controlPercent : (100 - $this->controlPercent);
        $modulo = 100 / $denom;
        if($this->controlPercent >= 50) {
            $isControl = $rowCount % $modulo != 0;
        } else {
            $isControl = $rowCount % $modulo == 0;
        }
		return($isControl);
	}

    public function saveData($session_id, $is_personalize_user = true) {
        $this->setCustomerSessionId($session_id);
        $this->setUsingPersonalize($is_personalize_user);
        $this->save();
    }
    
    public function clearData() {
		$connection = $this->getResource()->getConnection();
		$tableName = $this->getResource()->getMainTable();
		$connection->truncateTable($tableName);
    }
    
    public function pruneData() {
        $date = date("Y-m-d H:i:s", strtotime('-2 days'));

        $collection = $this->getCollection()
            ->addFieldToFilter('created_at', array('lt' => $date));
        foreach($collection as $item) {
            $item->delete();
        }
    }

}
