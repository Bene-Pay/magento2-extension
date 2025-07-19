<?php
namespace Promantus\Benepay\Block;

use Magento\Framework\Phrase;

class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    /**
     * getLabel
     * @param string $field
     * @return Phrase|string
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * getValueView
     * @param string $field
     * @param string $value
     * @return Phrase|string
     */
    protected function getValueView($field, $value)
    {
        return parent::getValueView($field, $value);
    }

}
