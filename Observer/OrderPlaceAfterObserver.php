<?php

namespace Promantus\Benepay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\ResponseInterface;
use Promantus\Benepay\Model\Encryptor;
// use Promantus\Benepay\Block\MainBlock;
use Psr\Log\LoggerInterface;

class OrderPlaceAfterObserver implements ObserverInterface
{
    protected $checkoutSession;
    protected $response;
    protected $encryptor;
    protected $mainBlock;
    protected $logger;

    public function __construct(
        CheckoutSession $checkoutSession,
        ResponseInterface $response,
        Encryptor $encryptor,
        // MainBlock $mainBlock,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->response = $response;
        $this->encryptor = $encryptor;
        // $this->mainBlock = $mainBlock;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $this->logger->info('OrderPlaceAfterObserver executed.');

            // Get the order object from the event
            $order = $observer->getEvent()->getOrder();
            // $order  = null;
            // Additional logic can be added here
            // For example, you can log the order ID
            $this->logger->info('Order ID: ' . $order->getId());
            $this->logger->info('Order State: ' . $order->getState());
            $this->logger->info('Order Status: ' . $order->getStatus());

            // $this->mainBlock->processOrderPlaceAfter($order);

            if ($order === null) {
                // Log error if order is not available    
                $this->logger->info('Order is null in OrderPlaceAfterObserver');
                return;
            }
            
            // Set order status to pending_payment if needed
            if ($order->getStatus() != 'pending_payment') {
                $order->setStatus('pending_payment');
                $order->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
                $this->orderRepository->save($order);
            }

        } catch (\Exception $e) {
            $this->logger->error('Error in OrderPlaceAfterObserver: ' . $e->getMessage());
        }
    }
}
