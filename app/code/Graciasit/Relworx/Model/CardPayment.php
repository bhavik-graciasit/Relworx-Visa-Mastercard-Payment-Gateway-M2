<?php

namespace Graciasit\Relworx\Model;

use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\ScopeInterface;

class CardPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    protected $_code = 'cardpayment';

    protected $_isInitializeNeeded = true;
    protected $_canCapture = true;

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

    }

    public function initialize($paymentAction, $stateObject)
    {
        $payment = $this->getInfoInstance();
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }
}
