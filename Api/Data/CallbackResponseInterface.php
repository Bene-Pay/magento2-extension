<?php
namespace Promantus\Benepay\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Benepay Callback Response Interface
 * @api
 */
interface CallbackResponseInterface extends ExtensibleDataInterface
{
    const SUCCESS = 'success';
    const MESSAGE = 'message';

    /**
     * Get success status.
     *
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * Set success status.
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): self;

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage(): string;

    /**
     * Set message.
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self;
}