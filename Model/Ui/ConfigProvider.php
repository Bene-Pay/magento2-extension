<?php
namespace Promantus\Benepay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment Gateway Code
     */
    const CODE = 'benepay';

    protected $urlBuilder;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                'benepay' => [
                    'redirectUrl' => $this->urlBuilder->getUrl('benepay/payment/redirect')
                ]
            ]
        ];
    }
}
