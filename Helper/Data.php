<?php
namespace Promantus\Benepay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;

class Data extends AbstractHelper
{
    /**
     * XML paths for your configuration settings
     */
    const XML_PATH_GENERAL_ENABLED = 'payment/benepay_gateway/active';
    const XML_PATH_API_URL = 'payment/benepay_gateway/api_url';
    const XML_PATH_API_KEY = 'payment/benepay_gateway/api_key';
    const XML_PATH_TOKEN_URL = 'payment/benepay_gateway/token_url';
    const XML_PATH_CLIENT_ID = 'payment/benepay_gateway/client_id';
    const XML_PATH_CLIENT_SECRET = 'payment/benepay_gateway/secret_key';
    const XML_PATH_BENEPAY_ENCRYPTION_KEY = 'payment/benepay_gateway/benepay_encryption_key';

    protected $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        LoggerInterface $logger // Optional logger for debugging
    ) {
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Get a specific configuration value.
     *
     * @param string $path The XML path of the configuration field.
     * @param string $scopeType Scope type (e.g., ScopeInterface::SCOPE_STORE, SCOPE_WEBSITE, SCOPE_DEFAULT)
     * @param int|string|null $scopeCode Scope code (store ID, website ID, or null for default)
     * @return mixed
     */
    public function getConfigValue(string $path, $scopeType = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        $value = $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
        $this->logger->debug(sprintf('Config value for path "%s": %s', $path, json_encode($value)));
        return $value;
    }

    /**
     * Check if Benepay Payment is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->getConfigValue(self::XML_PATH_GENERAL_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get the API Base URL.
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getApiUrl($storeId = null): ?string
    {
        return $this->getConfigValue(self::XML_PATH_API_URL, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get the API Key.
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getAPIKey($storeId = null): ?string
    {
        $encryptedKey = $this->getConfigValue(self::XML_PATH_API_KEY, ScopeInterface::SCOPE_STORE, $storeId);
        return $this->encryptor->decrypt($encryptedKey);
    }
    
    /**
     * Get the Token Endpoint URL.
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getTokenUrl($storeId = null): ?string
    {
        return $this->getConfigValue(self::XML_PATH_TOKEN_URL, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get the Client ID.
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getClientId($storeId = null): ?string
    {
        return $this->getConfigValue(self::XML_PATH_CLIENT_ID, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get the Secret Key (automatically decrypted).
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getClientSecret($storeId = null): ?string
    {
        // When using backend_model="Magento\Config\Model\Config\Backend\Encrypted",
        // the scopeConfig->getValue() method automatically decrypts the value.
        $encryptedSecret = $this->getConfigValue(self::XML_PATH_CLIENT_SECRET, ScopeInterface::SCOPE_STORE, $storeId);
        return $this->encryptor->decrypt($encryptedSecret);
    }

    /**
     * Get the Benepay specific data encryption key (automatically decrypted).
     *
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getBenepayEncryptionKey($storeId = null): ?string
    {
        $encryptedValue = $this->getConfigValue(self::XML_PATH_BENEPAY_ENCRYPTION_KEY, ScopeInterface::SCOPE_STORE, $storeId);
        return $this->encryptor->decrypt($encryptedValue);
    }

    // You can add an optional helper method for manual encryption/decryption
    // if you have other data fields that aren't system config, but use the same key.
    // However, for system config fields with 'obscure' type, it's automatic.
    public function encryptData(string $data): string
    {
        return $this->encryptor->encrypt($data, $this->getBenepayEncryptionKey());
    }

    public function decryptData(string $data): string
    {
        return $this->encryptor->decrypt($data, $this->getBenepayEncryptionKey());
    }
}