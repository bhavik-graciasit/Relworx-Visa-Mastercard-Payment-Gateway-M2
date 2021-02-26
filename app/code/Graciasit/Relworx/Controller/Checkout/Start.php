<?php

namespace Graciasit\Relworx\Controller\Checkout;

class Start extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_paymentMethod;
    protected $_resultJsonFactory;
    protected $_logger;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_currency;
    protected $orderRepository;
    protected $messageManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Graciasit\Relworx\Model\CardPayment $paymentMethod,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutSession = $checkoutSession;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_currency = $currency;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        parent::__construct($context);
    }

    public function execute()
    {
        // Create Array for API data

        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strength = 10;

        $input_length = strlen($permitted_chars);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $permitted_chars[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $quoteData = $this->_checkoutSession->getQuote();
        $quoteID = $quoteData->getId();
        $quoteEntityID = $quoteData->getEntityId();
        $getOrigOrderId = $quoteData->getOrigOrderId();
        $reservedOrderID = $quoteData->getReservedOrderId();

        $apiKey = $this->_scopeConfig->getValue('payment/cardpayment/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $accountId = $this->_scopeConfig->getValue('payment/cardpayment/account_no', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $reference =  $random_string.'_'.$quoteID;
        $amount = $cart->getQuote()->getGrandTotal();

        $postAPIData = array(
            'account_no' => $accountId,
            'reference' => $reference,
            'currency' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            'amount' => $amount,
            'description' => 'Payment towards Sombha Solutions'
        );

        // API URL
        $APIURL = 'https://payments.relworx.com/api/visa/request-session';

        // Create a new cURL resource
        $ch = curl_init($APIURL);

        // Setup request to send json via POST
        $payload = json_encode($postAPIData);

        // Attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',"Authorization: Bearer $apiKey"));

        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $resultData = curl_exec($ch);

        // Close cURL resource
        curl_close($ch);

        $result = json_decode($resultData, true);

        $resultJson = $this->_resultJsonFactory->create();

        $sessionId = $_COOKIE['PHPSESSID'];

        header("Set-Cookie: PHPSESSID=$sessionId; SameSite=None; Secure");

        if(isset($result['success']) && $result['success'] == 1)
        {
            $url = $result['payment_url'];
            return $resultJson->setData(['paymentUrl' => $url]);
        }
        else
        {
            $this->_checkoutSession->setErrorMessage($result['message']);
            $url = 'checkout/onepage/failure';
            return $resultJson->setData(['errorUrl' => $url]);
        }
    }
}
