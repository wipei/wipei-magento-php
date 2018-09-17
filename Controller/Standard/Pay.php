<?php

namespace Wipei\WipeiPayment\Controller\Standard;

/**
 * Class Pay action controller to pay order
 *
 * @package WipeiPayment\Controller\Standard
 */
class Pay
    extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Wipei\WipeiPayment\Model\WipeiPaymentFactory
     */
    protected $_paymentFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @param \Magento\Framework\App\Action\Context               $context
     * @param \Wipei\WipeiPayment\Model\WipeiPaymentFactory       $wipeiPaymentFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     * @param \Magento\Framework\UrlInterface                     $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Wipei\WipeiPayment\Model\WipeiPaymentFactory $wipeiPaymentFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->_paymentFactory = $wipeiPaymentFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Exception
     */
    public function execute()
    {
        // Payment object creation
        $standard = $this->_paymentFactory->create();

        // Payment information sent to Wipei server to obtain token for app
        $array_assign = $standard->submitPayment();
        $resultRedirect = $this->resultRedirectFactory->create();

        $checkoutType = $this->_scopeConfig->getValue('payment/wipei/checkout_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        // URL set up, according to answer and admin configuration
        if ($array_assign['status'] != 400) {
            $resultRedirect->setUrl($array_assign['init_point'] . $this->addCheckoutType($checkoutType));
        } else {
            if ($checkoutType == 'modal') {
                $resultRedirect->setUrl($this->_urlBuilder->getUrl('wipeipayment/standard/iframefailure'));
            } else {
                $resultRedirect->setUrl($this->_urlBuilder->getUrl('wipeipayment/standard/failure'));
            }
        }

        return $resultRedirect;
    }

    /**
     * @param $checkoutType
     * @return string
     */
    public function addCheckoutType($checkoutType)
    {
        return $checkoutType == 'modal' ? '&iframe=true' : '&iframe=false';
    }
}