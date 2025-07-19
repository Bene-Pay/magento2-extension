<?php
namespace Promantus\Benepay\Api\Data;

interface CallbackResourceInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const encData = '';

    /**
     * Get encData.
     *
     * @return string|null
     */
    public function getEncData();

    /**
     * Set encData.
     *
     * @param string $encData
     * @return $this
     */
    public function setEncData($encData);
}