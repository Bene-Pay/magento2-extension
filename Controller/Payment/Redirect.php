<?php

namespace Promantus\Benepay\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Promantus\Benepay\Model\Encryptor;
use Promantus\Benepay\Helper\Data as BenepayHelper;

class Redirect extends Action
{
    protected $checkoutSession;
    protected $redirectFactory;
    protected $orderRepository;
    protected $serializer;
    protected $request;
    protected $encryptor;
    protected $logger;
    protected $benepayHelper;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        RedirectFactory $redirectFactory,
        OrderRepositoryInterface $orderRepository,
        SerializerInterface $serializer,
        RequestInterface $request,
        Encryptor $encryptor,
        LoggerInterface $logger,
        BenepayHelper $benepayHelper
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->redirectFactory = $redirectFactory;
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
        $this->request = $request;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->benepayHelper = $benepayHelper;
    }

    public function execute()
    {
        try{
            // log the request for debugging
            $this->logger->info('Redirect action executed.');
            // print the request parameters
            $this->logger->info('Request parameters: ' . json_encode($this->request->getParams()));

            // Retrieve encryption key from admin settings
            $benepayEncryptionKey = $this->benepayHelper->getBenepayEncryptionKey();
            $apiUrl = $this->benepayHelper->getApiUrl();
            $apiKey = $this->benepayHelper->getAPIKey();
            $clientId = $this->benepayHelper->getClientId();
            $clientSecret = $this->benepayHelper->getClientSecret();
            $tokenUrl = $this->benepayHelper->getTokenUrl();
            
            $this->logger->debug(sprintf('Config value for path %s : %s : %s', $apiKey, $clientSecret, $benepayEncryptionKey));

            $orderId = $this->request->getParam('order_id');

            if (!$orderId) {
                $this->messageManager->addErrorMessage(__('Order ID is missing'));
                return $this->_redirect('checkout/cart');
            }

            try {
                $order = $this->orderRepository->get($orderId);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Order not found'));
                return $this->_redirect('checkout/cart');
            }
            
            if (!$order || !$order->getId()) {
                $this->messageManager->addErrorMessage(__('No active order found.'));
                return $this->_redirect('checkout/cart');
            }
            
            $data = [
                'collectionAmountCurrency' => $order->getOrderCurrencyCode(),
                'collectionReferenceNumber' => $order->getId(),
                'debtorEmailId' => $order->getCustomerEmail(),
                'debtorName' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
                'finalDueAmount' => $order->getGrandTotal(),
                'requestorTransactionId' => $order->getId(),
                // 'debtorMobileNumber' => $order->getBillingAddress() ? $order->getBillingAddress()->getTelephone() : null,
                'reasonForCollection' => 'Payment for Order #' . $order->getId(),
            ];

            $json = $this->serializer->serialize($data);

            // log the JSON for debugging
            $this->logger->info('Request data for payment gateway: ' . $json);

            $encrypted = $this->encryptor->encrypt($json, $benepayEncryptionKey);

            // get the payment gateway authorization
            $gateway = $this->initPayment($encrypted);

            if ($gateway == null) {
                $this->messageManager->addErrorMessage(__('Payment gateway request failed.'));
                return $this->_redirect('checkout/cart');
            }

            // redirect to the payment gateway
            return $this->redirectFactory->create()->setUrl($gateway);
        } catch (\Exception $e) {
            $this->logger->error('Error in Redirect execute: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred while processing your request.'));
            return $this->_redirect('checkout/cart');
        }

    }

    protected function initPayment($encryptedData)
    {
        try{
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                return null;
            } 

            $payload = json_encode(['encryptedData' => $encryptedData]);
            $apiUrl = $this->benepayHelper->getApiUrl();
            $apiKey = $this->benepayHelper->getAPIKey();

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', $apiUrl, [
                'body' => $payload,
                'headers' => [
                    'x-api-key' => $apiKey,
                    'Content-Type' => 'application/json', 
                    'Authorization' => 'Bearer ' . $accessToken
                ],
            ]);

            // API status response code checking
            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Payment gateway request failed with status code: ' . $response->getStatusCode());
                return null;
            }

            $response = json_decode($response->getBody(), true);

            $this->logger->info('Payment gateway response message: ' . json_encode($response));

            // gateway payment request response checking
            if (isset($response['statusCode']) && $response['statusCode'] != 302) {
                $this->logger->error('Payment gateway error response: ' . $response['message']);
                return null;
            }
            
            return $response['message'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error('Error during payment gateway request: ' . $e->getMessage());
            return null;
        } catch (\Error $e) {
            $this->logger->error('Error during payment gateway request: ' . $e->getMessage());
            return null;
        }
    }

    protected function getAccessToken()
    {
        try {
            // Retrieve values from admin settings
            $clientId = $this->benepayHelper->getClientId();
            $clientSecret = $this->benepayHelper->getClientSecret();
            $tokenUrl = $this->benepayHelper->getTokenUrl();

            $settings = [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'auth_url' => $tokenUrl
            ];

            $client = new \GuzzleHttp\Client();

            $response = $client->request('POST', $settings['auth_url'], [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $settings['client_id'],
                    'client_secret' => $settings['client_secret']
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Failed to retrieve access token: ' . $response->getBody());
                return null;
            }

            $body = json_decode($response->getBody(), true);
            $accessToken = $body['access_token'];
            
            $this->logger->info('Access token retrieved: ' . $accessToken);

            return $accessToken;
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving access token: ' . $e->getMessage());
            return null;
        } catch (\Error $e) {
            $this->logger->error('Error retrieving access token: ' . $e->getMessage());
            return null;
        }
    }
}
