<?php
namespace Promantus\Benepay\Model\Api;

use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Promantus\Benepay\Api\CallbackManagementInterface;
use Promantus\Benepay\Api\Data\CallbackResponseInterfaceFactory; // Factory for the response DTO
use Promantus\Benepay\Model\Encryptor;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Promantus\Benepay\Helper\Data as BenepayHelper;

class CallbackManagement implements CallbackManagementInterface
{
    protected $orderRepository;
    protected $logger;
    protected $encryptor;
    protected $callbackResponseFactory;
    protected $benepayHelper;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        CallbackResponseInterfaceFactory $callbackResponseFactory,
        Encryptor $encryptor,
        BenepayHelper $benepayHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->callbackResponseFactory = $callbackResponseFactory;
        $this->encryptor = $encryptor;
        $this->benepayHelper = $benepayHelper;
    }

    /**
     * Handle the Benepay payment gateway callback.
     *
     * @param string $encData Encrypted data received from the Benepay gateway.
     * @return \Promantus\Benepay\Api\Data\CallbackResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processCallback(string $encData): \Promantus\Benepay\Api\Data\CallbackResponseInterface
    {
        /** @var \Promantus\Benepay\Api\Data\CallbackResponseInterface $response */
        $response = $this->callbackResponseFactory->create();

        try {
            $this->logger->info("Benepay Callback Received (encrypted): " . $encData);

            if (empty($encData)) {
                $this->logger->error('Missing encrypted data in callback.');
                return $response->setSuccess(false)->setMessage(__('Missing encrypted data'));
            }

             // Retrieve encryption key from admin settings
            $benepayEncryptionKey = $this->benepayHelper->getBenepayEncryptionKey();

            // Decrypt the data
            $decryptedData = $this->encryptor->decrypt($encData, $benepayEncryptionKey);
            $resData = json_decode($decryptedData, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Benepay Callback Error: Invalid JSON after decryption. Raw: ' . $decryptedData);
                return $response->setSuccess(false)->setMessage(__('Invalid callback data format.'));
            }

            $this->logger->info('Benepay Callback Data (decrypted): ' . $decryptedData);

            // --- Crucial Security Check for Callbacks ---
            // Implement signature verification here if the gateway provides one.
            // Example: if (!$this->encryptor->verifySignature($callbackData['signature'], $decryptedData, 'your_shared_secret')) {
            //     throw new LocalizedException(__('Invalid callback signature.'));
            // }

            $requestorTransactionId = $resData['requestorTransactionId'] ?? null;
            $paymentRequestStatus = $resData['paymentRequestStatus'] ?? null;
            $gatewayTransactionId = $resData['transactionId'] ?? null; // Assuming gateway sends a transaction ID

            if (!$requestorTransactionId) {
                $this->logger->error('Benepay Callback Error: Missing requestorTransactionId in decrypted data.');
                return $response->setSuccess(false)->setMessage(__('Missing transaction ID.'));
            }

            try {
                $order = $this->orderRepository->get($requestorTransactionId);
            } catch (NoSuchEntityException $e) {
                $this->logger->error('Benepay Callback Error: Order not found for ID ' . $requestorTransactionId);
                return $response->setSuccess(false)->setMessage(__('Order not found.'));
            }

            if ($paymentRequestStatus === 'PAID') {
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus(Order::STATE_PROCESSING);
                $order->addStatusHistoryComment(__('Payment successful via Benepay. Transaction ID: %1', $gatewayTransactionId));
            } elseif ($paymentRequestStatus === 'FAILED') {
                $order->setState(Order::STATE_CANCELED);
                $order->setStatus(Order::STATE_CANCELED);
                $order->addStatusHistoryComment(__('Payment failed or canceled via Benepay. Transaction ID: %1', $gatewayTransactionId));
            } else {
                // Handle other statuses if necessary
                $this->logger->warning('Benepay Callback: Unhandled payment status: ' . $paymentRequestStatus . ' for Order ID: ' . $requestorTransactionId);
                $order->addStatusHistoryComment(__('Benepay payment status: %1. Transaction ID: %2', $paymentRequestStatus, $gatewayTransactionId));
            }

            $payment = $order->getPayment();
            // Ensure $gatewayTransactionId is correctly extracted from $resData
            $payment->setTransactionId($gatewayTransactionId);
            $payment->setIsTransactionClosed(true); // Assuming the transaction is final after callback
            $payment->setAdditionalInformation('benepay_gateway_response', json_encode($resData)); // Log the full decrypted response
            $order->setPayment($payment); // Re-assign payment object to order

            $this->orderRepository->save($order);

            $this->logger->info('Benepay payment status update processed for Order ID: ' . $requestorTransactionId);

            return $response->setSuccess(true)->setMessage(__('Status updated successfully'));

        } catch (LocalizedException $e) {
            $this->logger->error('Benepay Callback Error (Localized): ' . $e->getMessage());
            return $response->setSuccess(false)->setMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical('Benepay Callback Error (General): ' . $e->getMessage());
            return $response->setSuccess(false)->setMessage(__('An unexpected error occurred while processing the request.'));
        }
    }
}