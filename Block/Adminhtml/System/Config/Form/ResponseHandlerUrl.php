<?php
/**
 * Promantus_Benepay Response Handler URL field frontend model.
 *
 * @category    Promantus
 * @package     Promantus_Benepay
 * @author      Your Name/Company
 * @copyright   Copyright (c) 2025 Promantus. All rights reserved.
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace Promantus\Benepay\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Dynamic URL field info block for various response handlers (Callback, Capture etc.).
 */
class ResponseHandlerUrl extends Field
{
    // Define the specific API endpoints, without the base URL or 'rest/V1/' prefix.
    public const ENDPOINT_CALLBACK = 'benepay/payment/callback';
    public const ENDPOINT_CAPTURE = 'benepay/payment/capture';

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout(): static
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Promantus_Benepay::config/responseHandlerUrl.phtml');
        }
        return $this;
    }

    /**
     * Get html
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    /**
     * Helper to build the full URL with or without rest/V1 prefix.
     *
     * @param string $endpoint The base endpoint (e.g., 'benepay/payment/callback')
     * @param bool $isRestApi True if 'rest/V1/' should be prepended.
     * @return string
     * @throws NoSuchEntityException
     */
    protected function _buildUrl(string $endpoint, bool $isRestApi): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $baseUrl = rtrim($baseUrl, '/') . '/';
        $endpoint = ltrim($endpoint, '/');

        if ($isRestApi) {
            return $baseUrl . 'rest/V1/' . $endpoint;
        }
        return $baseUrl . $endpoint;
    }

    /**
     * Get the formatted Callback Notification URL.
     * Needs 'rest/V1/' prefix.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCallbackNotificationUrl(): string
    {
        return $this->_buildUrl(self::ENDPOINT_CALLBACK, true);
    }

    /**
     * Get the formatted Capture Notification URL.
     * Does NOT need 'rest/V1/' prefix.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCaptureNotificationUrl(): string
    {
        return $this->_buildUrl(self::ENDPOINT_CAPTURE, false);
    }
}