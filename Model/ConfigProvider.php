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

    /**
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper
    )
    {
        $this->methodInstance = $paymentHelper->getMethodInstance($this->methodCode);
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
                        'actionUrl' => $this->methodInstance->getActionUrl()
                    ],
                ],
            ];
        }

        return $config;
    }
}