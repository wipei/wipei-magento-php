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
        
        // URL set up, according to answer and admin configuration
        if ($array_assign['status'] != 400) {
            $resultRedirect->setUrl($array_assign['init_point']);
        } else {
            $resultRedirect->setUrl($this->_urlBuilder->getUrl('checkout/onepage/failure'));
        }

        return $resultRedirect;
    }
}