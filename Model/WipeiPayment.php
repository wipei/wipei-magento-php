<?php

namespace Wipei\WipeiPayment\Model;

/**
 * Class Payment
 *
 * TODO: Configure logs
 *
 * @package WipeiPayment\Model\
 */
class WipeiPayment extends \Magento\Payment\Model\Method\AbstractMethod {

    /**
     * Define payment method code
     */
    const CODE = 'wipei_wipeipayment';

    /**
     * define URL to go when an order is placed
     */
    const ACTION_URL = 'wipeipayment/standard/pay';

    /**
     * {@inheritdoc}
     */
    protected $_code = self::CODE;

    /**
     * {@inheritdoc}
     */
    protected $_canAuthorize = true;

    /**
     * {@inheritdoc}
     */
    protected $_canCapture = true;

    /**
     * @var \Wipei\WipeiPayment\Helper\Data
     */
    protected $_helperData;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_helperImage;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var string
     */
    // protected $_infoBlockType = 'MercadoPago\Core\Block\Info';

    /**
     * @param \Wipei\WipeiPayment\Helper\Data                              $helperData
     * @param \Magento\Catalog\Helper\Image                                $helperImage
     * @param \Magento\Checkout\Model\Session                              $checkoutSession
     * @param \Magento\Customer\Model\Session                              $customerSession
     * @param \Magento\Sales\Model\OrderFactory                            $orderFactory
     * @param \Magento\Framework\UrlInterface                              $urlBuilder
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                                 $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger                         $logger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Wipei\WipeiPayment\Helper\Data $helperData,
        \Magento\Catalog\Helper\Image $helperImage,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_helperData = $helperData;
        $this->_helperImage = $helperImage;

        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Return array with data of payment in the api
     *
     * @return array
     * @throws \Exception
     */
    public function submitPayment()
    {
        $client_id = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $client_secret = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $api = $this->_helperData->getApiInstance($client_id, $client_secret);

        $pref = $this->makePreference();
        $this->_helperData->log("Get order information", 'wipei.log', $pref);

        $response = $api->create_preference($pref);
        $this->_helperData->log("Create order on API", 'wipei.log', $response);

        if ($response['status'] == 200 || $response['status'] == 201) {
            $payment = $response['response'];
            $init_point = $payment['init_point'];

            $array_assign = [
                "init_point"      => $init_point,
                "status"          => 201
            ];

            $this->_helperData->log("Order creation on API ok", 'wipei.log');
        } else {
            $array_assign = [
                "message" => __('An error has occurred. Please refresh the page.'),
                "json"    => json_encode($response),
                "status"  => 400
            ];

            $this->_helperData->log("Order creation on API error", 'wipei.log');
        }

        return $array_assign;
    }

    /**
     * Return array with data about the purchase to send to the api
     *
     * @return array
     */
    public function makePreference()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
        $payment = $order->getPayment();
        $paramsShipment = new \Magento\Framework\DataObject();
        $paramsShipment->setParams([]);

        $arr['external_reference'] = $orderIncrementId;
        $arr['items'] = $this->getItems($order);

        $order_amount = (float)$order->getBaseGrandTotal();
        $shipment_cost = $order->getBaseShippingAmount();

        if (!$order_amount) {
            $order_amount = (float)$order->getBasePrice() + $shipment_cost;
        }
        $arr['total'] = $order_amount;

        if ($shipment_cost) {
            array_push($arr['items'], [
                "name"      => "Entrega",
                "quantity"  => 1,
                "price"     => (float)$shipment_cost
            ]);
        }

        $this->_helperData->log("Total: " . $order_amount, 'wipei.log');

        if (isset($payment['additional_information']['doc_number']) && $payment['additional_information']['doc_number'] != "") {
            $arr['payer']['identification'] = [
                "type"   => "CPF",
                "number" => $payment['additional_information']['doc_number']
            ];
        }

        $arr['url_success'] = $this->_urlBuilder->getUrl('checkout/onepage/success');
        $arr['url_notify'] = $this->_urlBuilder->getUrl('wipeipayment/notifications/notify');
        $arr['url_failure'] = $this->_urlBuilder->getUrl('checkout/onepage/failure');

        return $arr;
    }

    /**
     * Return array with data of items of order
     *
     * @param $order
     *
     * @return array
     */
    protected function getItems($order)
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $product = $item->getProduct();

            $items[] = [
                "id_ext"    => $item->getSku(),
                "name"      => $product->getName(),
                "quantity"  => (int)number_format($item->getQtyOrdered(), 0, '.', ''),
                "price"     => (float)number_format($item->getPrice(), 2, '.', '')
            ];
        }

        return $items;
    }

    /**
     * @return array
     */
    protected function getExcludedPaymentsMethods()
    {
        $excludedMethods = [];
        $excluded_payment_methods = $this->getConfigData('excluded_payment_methods');
        $arr_epm = explode(",", $excluded_payment_methods);
        if (count($arr_epm) > 0) {
            foreach ($arr_epm as $m) {
                $excludedMethods[] = ["id" => $m];
            }
        }

        return $excludedMethods;
    }

    /**
     * Return info of shipping address
     *
     * @param $shippingAddress
     *
     * @return array
     */
    protected function getReceiverAddress($shippingAddress)
    {
        return [
            "floor"         => "-",
            "zip_code"      => $shippingAddress->getPostcode(),
            "street_name"   => $shippingAddress->getStreet()[0] . " - " . $shippingAddress->getCity() . " - " . $shippingAddress->getCountryId(),
            "apartment"     => "-",
            "street_number" => ""
        ];
    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->_urlBuilder->getUrl(self::ACTION_URL);
    }

    /**
     * Return success page url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $url = $this->_helperData->getSuccessUrl();

        return $this->_urlBuilder->getUrl($url, ['_secure' => true]);
    }

    /**
     * Get payment info returned by api
     *
     * @param $payment_id
     *
     * @return array
     * @throws \Exception
     */
    public function getPayment($payment_id)
    {
        $client_id = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $client_secret = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $api = $this->_helperData->getApiInstance($client_id, $client_secret);

        return $api->get("/order" . "?order=" . $payment_id);
    }

    /**
     * Get payment info returned by api
     *
     * @param $payment_id
     *
     * @return array
     * @throws \Exception
     */
    public function getPaymentOrder($order_id)
    {
        $client_id = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $client_secret = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $api = $this->_helperData->getApiInstance($client_id, $client_secret);

        return $api->get("/order_by_reference" . "?reference=" . $order_id);
    }

    /**
     * Get Magento order
     *
     * @param integer $incrementId
     *
     * @return \Magento\Sales\Model\Order
     */
    public function _getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

}