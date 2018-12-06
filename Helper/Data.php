<?php

namespace Wipei\WipeiPayment\Helper;

use Magento\Framework\View\LayoutFactory;

/**
 * Class Data
 * 
 * TODO: Add methods on demand
 * 
 * @package Wipei\WipeiPayment\Helper
 */
class Data extends \Magento\Payment\Helper\Data
{
  /**
   * path to client id and secret in admin config
   */
  const XML_PATH_CLIENT_ID = 'payment/wipei/client_id';
  const XML_PATH_CLIENT_SECRET = 'payment/wipei/client_secret';
  const XML_PATH_CHECKOUT_TYPE = 'payment/wipei/checkout_type';
  const TYPE = 'magento';

  /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context                $context
     * @param LayoutFactory                                        $layoutFactory
     * @param \Magento\Payment\Model\Method\Factory                $paymentMethodFactory
     * @param \Magento\Store\Model\App\Emulation                   $appEmulation
     * @param \Magento\Payment\Model\Config                        $paymentConfig
     * @param \Magento\Framework\App\Config\Initial                $initialConfig
     * @param \Wipei\WipeiPayment\Logger\Logger                    $logger
     */
    public function __construct(
      \Magento\Framework\App\Helper\Context $context,
      LayoutFactory $layoutFactory,
      \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
      \Magento\Store\Model\App\Emulation $appEmulation,
      \Magento\Payment\Model\Config $paymentConfig,
      \Magento\Framework\App\Config\Initial $initialConfig,
      \Wipei\WipeiPayment\Logger\Logger $logger

  )
  {
      parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
      $this->_logger = $logger;
  }

    /**
     * Logger
     *
     * @param        $message
     * @param string $name
     * @param null   $array
     */
    public function log($message, $name = "wipei", $array = null)
    {
        // get log configuration value
        $actionLog = $this->scopeConfig->getValue('payment/wipei_wipeipayment/logs', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$actionLog) {
            return;
        }

        // if extra data is provided, it's encoded for better visualization
        if (!is_null($array)) {
            $message .= " - " . json_encode($array);
        }

        // set log
        $this->_logger->setName($name);
        $this->_logger->debug($message);
    }

  /**
   * Return Api instance given ClientId and Secret
   *
   * @return \Wipei\WipeiPayment\Lib\Api
   * @throws \Exception
   */
    public function getApiInstance($client_id = null, $client_secret = null) {

        if(is_null($client_id) || is_null($client_secret)){
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid CLIENT_ID and CLIENT_SECRET for Wipei.'));
        }

        $api = new \Wipei\WipeiPayment\Lib\Api($client_id, $client_secret);

        $api->set_type(self::TYPE);

        return $api;
    }

    /**
     * ClientId and Secret valid?
     *
     * @param $clientId
     * @param $clientSecret
     *
     * @return bool
     * @throws \Exception
     */
    public function isValidClientCredentials($clientId, $clientSecret)
    {
        $api = $this->getApiInstance($clientId, $clientSecret);
        try {
            $api->get_access_token();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
