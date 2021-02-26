<?php

namespace Graciasit\Relworx\Controller\Checkout;

use Magento\Framework\App\Action;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class Cancel extends Action\Action implements CsrfAwareActionInterface
{
    protected $_checkoutSession;

    public function __construct(
        Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->_checkoutSession
            ->unsLastQuoteId()
            ->unsLastSuccessQuoteId()
            ->unsLastOrderId()
            ->unsLastRealOrderId();

        $this->_redirect('checkout/onepage/failure');

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

