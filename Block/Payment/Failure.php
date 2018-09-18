<?php
namespace Wipei\WipeiPayment\Block\Payment;

/**
* Block to checkout failure iframe page
*
* Class Failure
*
* @package Wipei\WipeiPayment\Core\Block\Payment
*/
class Failure extends \Magento\Framework\View\Element\Template
{
    /**
    * Set template in constructor method
    */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('iframe_failure.phtml');
    }
}