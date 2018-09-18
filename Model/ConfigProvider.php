<?php

namespace Wipei\WipeiPayment\Model;

/**
 * Return configs
 *
 * Class ConfigProvider
 *
 * @package Wipei\WipeiPayment\Model
 */
class ConfigProvider
    implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $methodInstance;

    /**
     * @var string
     */
    protected $methodCode = WipeiPayment::CODE;

    protected $_scopeConfig;

    /**
     * @param  \Magento\Payment\Helper\Data $paymentHelper
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Helper\Data $paymentHelper)
    {
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Return Wipei config
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];
        if ($this->methodInstance->isAvailable()) {
            $config = [
                'payment' => [
                    $this->methodCode => [
                        'actionUrl'     => $this->methodInstance->getActionUrl(),
                        'failureUrl'     => $this->methodInstance->getFailureActionUrl(),
                        'successUrl'     => $this->methodInstance->getSuccessUrl(),
                        'logoUrl'       => '',
                        'checkout_type' => $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CHECKOUT_TYPE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
                    ],
                ],
            ];
        }

        return $config;
    }
}