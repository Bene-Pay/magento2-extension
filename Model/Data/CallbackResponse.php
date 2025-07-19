<?php
namespace Promantus\Benepay\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Promantus\Benepay\Api\Data\CallbackResponseInterface;

class CallbackResponse extends AbstractExtensibleObject implements CallbackResponseInterface
{
    /**
     * Get success status.
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return (bool)$this->_get(self::SUCCESS);
    }

    /**
     * Set success status.
     *
     * @param bool $success
     * @return $this
     */
    public function setSuccess(bool $success): self
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return (string)$this->_get(self::MESSAGE);
    }

    /**
     * Set message.
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self
    {
        return $this->setData(self::MESSAGE, $message);
    }
}