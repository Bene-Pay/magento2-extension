<?php
namespace Promantus\Benepay\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;
use Promantus\Benepay\Model\Encryptor;
use Promantus\Benepay\Helper\Data as BenepayHelper;

class Capture extends Action
{
    protected $orderRepository;
    protected $checkoutSession;
    protected $logger;
    protected $encryptor;
    protected $benepayHelper;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Session $checkoutSession,
        LoggerInterface $logger,
        Encryptor $encryptor,
        BenepayHelper $benepayHelper
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->benepayHelper = $benepayHelper;
    }

    public function execute()
    {
        try {
            $request = $this->getRequest();
            $response = $request->getParam('response');

            if (empty($response)) {
                $this->messageManager->addErrorMessage(__('Missing response data.'));
                return $this->_redirect('checkout/cart');
            }

            // Retrieve encryption key from admin settings
            $benepayEncryptionKey = $this->benepayHelper->getBenepayEncryptionKey();

            // Decrypt the data (assuming you have a decryption method)
            // Encryptor decryption logic should be implemented here
            $decryptedData = $this->encryptor->decrypt($response, $benepayEncryptionKey);
            $resData = json_decode($decryptedData, true);

            $this->logger->info('Benepay Return Data: ' . $decryptedData);

            $order = $this->orderRepository->get($resData['requestorTransactionId']);

            if ($resData['transactionStatus'] === 'PAID') {
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->addStatusHistoryComment(__('Payment successful via Benepay.'));
            } elseif ($resData['transactionStatus'] === 'FAILED') {
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->setStatus(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->addStatusHistoryComment(__('Payment failed or canceled.'));
            }

            $payment = $order->getPayment();
            $payment->setTransactionId($resData['transactionId']); // ← from gateway
            $payment->setIsTransactionClosed(true); // or false if still open
            $payment->setAdditionalInformation('gateway_response', $decryptedData); // log full gateway response
            $order->setPayment($payment);

            $this->orderRepository->save($order);

            // Clear session cart
            $this->checkoutSession->clearQuote();

            if($resData['transactionStatus'] === 'PAID'){
                $this->messageManager->addSuccessMessage(__('Payment captured successfully. Items in your shopping cart have been processed. Your payment transaction ID is: %1', $resData['transactionId']));
                return $this->_redirect('checkout/onepage/success');
            }
        } catch (\Exception $e) {
            $this->logger->error('Benepay Return Error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong.'));
        }

        return $this->_redirect('checkout/cart');
    }
}
