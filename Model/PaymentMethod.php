<?php
namespace Promantus\Benepay\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\UrlInterface;

class PaymentMethod extends AbstractMethod
{
    protected $_code = 'benepay';
    
    protected $_isOffline = false;
    protected $_isGateway = true;

    protected $_canAuthorize = false;
    protected $_canCapture = false;
    protected $_canOrder = true;
}
