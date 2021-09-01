<?php
namespace CustomerParadigm\AmazonPersonalize\Controller\Personalize;

Use Aws\Personalize\PersonalizeClient;

class TestDetect extends \Magento\Framework\App\Action\Action {

    protected $sdkClient;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
	\CustomerParadigm\AmazonPersonalize\Api\AwsSdkClient $sdkClient

    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->sdkClient = $sdkClient;

        parent::__construct($context);
    }

    public function execute()
    {
/* Comment out this redirect to homepage to use the test controller 
*/
            $resultRedirect = $this->resultRedirectFactory->create();
           $resultRedirect->setPath('');
            return $resultRedirect;
	    
	$this->detectClient();
        echo("\n--------end tests---------");
    }

    public function detectClient() {
        echo("<pre><div>is EC2 install</div>");
        var_dump($this->sdkClient->isEc2Install());
        echo("</pre>");
    }
}
