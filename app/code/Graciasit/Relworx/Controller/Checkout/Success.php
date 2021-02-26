<?php

namespace Graciasit\Relworx\Controller\Checkout;

use Magento\Framework\App\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Success extends Action\Action implements CsrfAwareActionInterface
{
    protected $_checkoutSession;
    protected $resultRedirect;
    private $orderRepository;
    protected $_invoiceService;
    protected $_invoiceSender;
    protected $_orderSender;
    protected $sessionManager;
    protected $_quoteManagement;
    protected $quoteFactory;
    protected $_eventManager;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Controller\ResultFactory $result,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Quote\Api\CartManagementInterface $quoteManagement,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager

    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->_invoiceService = $invoiceService;
        $this->_invoiceSender = $invoiceSender;
        $this->_orderSender = $orderSender;
        $this->_transaction = $transaction;
        $this->resultRedirect = $result;
        $this->sessionManager = $sessionManager;
        $this->_quoteManagement = $quoteManagement;
        $this->quoteFactory = $quoteFactory;
        $this->_eventManager = $eventManager;
        parent::__construct($context);
    }

    public function execute()
    {
        if(isset($_POST['customer_reference']) && !empty($_POST['customer_reference']))
        {
            $CustomerArr = explode('_',$_POST["customer_reference"]);
            $quoteID = $CustomerArr[1];

            $quote = $this->quoteFactory->create()->load($quoteID);

            if($quote->getCustomerGroupId() == 0)
            {
                $quote->setCustomerId(null)
                    ->setCustomerIsGuest(true)
                    ->setCustomerEmail($quote->getBillingAddress()->getEmail());
            }

            if($quote)
            {
                $this->_checkoutSession
                    ->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->clearHelperData();
            }

            $order = $this->_quoteManagement->submit($quote);

            if($order)
            {
                $this->_eventManager->dispatch(
                    'checkout_type_onepage_save_order_after',
                    ['order' => $order, 'quote' => $quote]
                );

                $this->_checkoutSession
                    ->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

            }

            $_order = $this->orderRepository->get($order->getId());

            $this->_orderSender->send($_order);

            if($_order->canInvoice()) {
                $invoice = $this->_invoiceService->prepareInvoice($_order);
                $invoice->register();
                $invoice->save();

                $transactionSave = $this->_transaction->addObject(
                    $invoice
                )->addObject(
                    $invoice->getOrder()
                );

                $transactionSave->save();

                $this->_invoiceSender->send($invoice);
                //send notification code
                $_order->addStatusHistoryComment(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )
                    ->setIsCustomerNotified(true)
                    ->save();
            }

            $this->_eventManager->dispatch(
                'checkout_submit_all_after',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );

            $_order->setState('processing');
            $_order->setStatus('processing');
            $_order->save();

            $this->_redirect('checkout/onepage/success');

        }

    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

}

