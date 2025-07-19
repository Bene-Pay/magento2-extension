<?php
namespace Promantus\Benepay\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Psr\Log\LoggerInterface;

class CaptureRequest implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;
    protected $logger;

    /**
     * CaptureRequest constructor.
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $this->logger->info('CaptureRequest build started.');

        // if (!isset($buildSubject['payment'])
        //     || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        // ) {
        //     throw new \InvalidArgumentException('Payment data object should be provided');
        // }

        // $paymentDO = $buildSubject['payment'];
        // $payment = $paymentDO->getPayment();

        // // $this->logger->debug('CaptureRequest buildSubject', $buildSubject);

        // if (!$payment instanceof OrderPaymentInterface) {
        //     throw new \LogicException('Order payment should be provided.');
        // }

        // return [
        //     'TXN_TYPE' => 'S',
        //     'TXN_ID' => $payment->getLastTransId(),
        //     'MERCHANT_KEY' => ''
        // ];


        // $order = $paymentDO->getOrder();
        // $this->config->getValue(
        //         'merchant_gateway_key',
        //         $order->getStoreId()
        //     )
    }
}
