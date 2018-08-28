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
     * @param \Magento\Sales\Model\ResourceModel\Status\Collection $statusFactory
     * @param \Magento\Framework\Module\ResourceInterface          $moduleResource
     */
    public function __construct(
      \Magento\Framework\App\Helper\Context $context,
      LayoutFactory $layoutFactory,
      \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
      \Magento\Store\Model\App\Emulation $appEmulation,
      \Magento\Payment\Model\Config $paymentConfig,
      \Magento\Framework\App\Config\Initial $initialConfig,
      \Magento\Sales\Model\ResourceModel\Status\Collection $statusFactory,
      \Magento\Sales\Model\OrderFactory $orderFactory,
      \Magento\Backend\Block\Store\Switcher $switcher,
      \Magento\Framework\Composer\ComposerInformation $composerInformation,
      \Magento\Framework\Module\ResourceInterface $moduleResource

  )
  {
      parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
      $this->_statusFactory = $statusFactory;
      $this->_orderFactory = $orderFactory;
      $this->_switcher = $switcher;
      $this->_composerInformation = $composerInformation;
      $this->_moduleResource = $moduleResource;
  }

  /**
   * Return Api instance given AccessToken or ClientId and Secret
   *
   * @return \Wipei\WipeiPayment\Lib\Api
   * @throws \Exception
   */
    public function getApiInstance($access_or_client_id = null, $client_secret = null) {

        if(is_null($access_or_client_id) && is_null($client_secret)){
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN'));
        }

        $api = new \Wipei\WipeiPayment\Lib\Api($access_or_client_id, $client_secret);
        //$api->set_platform(self::PLATFORM_STD);

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

    /**
     * Return success url
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return 'checkout/onepage/success';
    }
}
