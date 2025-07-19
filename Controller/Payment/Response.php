<?php

namespace Promantus\Benepay\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class Response extends Action
{
    protected $orderFactory;
    protected $checkoutSession;

    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $result = $this->getRequest()->getParam('result'); // From gateway
        $orderId = $this->getRequest()->getParam('order_id');

        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        if ($result === 'success') {
            $order->setState('processing')->setStatus('processing')->save();
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->_redirect('checkout/onepage/success');
        } else {
            $order->cancel()->save();
            $this->_redirect('checkout/cart');
        }
    }
}
