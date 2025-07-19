<?php
namespace Promantus\Benepay\Api;

/**
 * Benepay Callback Management Interface
 * @api
 */
interface CallbackManagementInterface
{
    /**
     * Handle the Benepay payment gateway callback.
     *
     * @param string $encData Encrypted data received from the Benepay gateway.
     * @return \Promantus\Benepay\Api\Data\CallbackResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processCallback(string $encData): \Promantus\Benepay\Api\Data\CallbackResponseInterface;
}