<?php
namespace Promantus\Benepay\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\Value\ResourceModel\Sync;

class EndpointDisplay extends Value
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param UrlInterface $urlBuilder
     * @param Sync|null $resource
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        UrlInterface $urlBuilder,
        Sync $resource = null,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resourceCollection = null,
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Get the full API URL (Callback or Capture)
     *
     * @param string $endpoint The specific API endpoint (e.g., 'benepay/payment/callback')
     * @return string
     */
    protected function getFullApiUrl(string $endpoint): string
    {
        $baseUrl = $this->urlBuilder->getBaseUrl();
        // Ensure base URL ends with a slash and endpoint doesn't start with one
        $baseUrl = rtrim($baseUrl, '/') . '/';
        $endpoint = ltrim($endpoint, '/');
        
        return $baseUrl . 'rest/V1/' . $endpoint;
    }

    /**
     * Load value from database.
     * Overridden to display the generated URL.
     *
     * @return $this
     */
    public function afterLoad()
    {
        // Get the field ID from system.xml to determine which URL to generate
        $fieldId = $this->getFieldConfig()->offsetGet('id');

        $generatedUrl = '';
        if ($fieldId === 'callback_url_display') {
            $generatedUrl = $this->getFullApiUrl('benepay/payment/callback');
        } elseif ($fieldId === 'capture_url_display') {
            // Assuming your capture endpoint is 'benepay/payment/capture'
            $generatedUrl = $this->getFullApiUrl('benepay/payment/capture');
        }

        // Set the value to be displayed in the input field
        $this->setValue($generatedUrl);

        return parent::afterLoad();
    }

    /**
     * Prevent saving the value of this field to the database,
     * as it's a display-only field.
     *
     * @return $this
     */
    public function beforeSave()
    {
        // Do nothing, as this field should not be saved.
        return $this;
    }
}