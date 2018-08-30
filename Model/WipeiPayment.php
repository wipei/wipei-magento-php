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
        //$this->_helperData->log("make array", 'mercadopago-standard.log', $pref);

        $response = $api->create_preference($pref);
        //$this->_helperData->log("create preference result", 'mercadopago-standard.log', $response);

        if ($response['status'] == 200 || $response['status'] == 201) {
            $payment = $response['response'];
            $init_point = $payment['init_point'];

            $array_assign = [
                "init_point"      => $init_point,
                "status"          => 201
            ];

            //$this->_helperData->log("Array preference ok", 'mercadopago-standard.log');
        } else {
            $array_assign = [
                "message" => __('An error has occurred. Please refresh the page.'),
                "json"    => json_encode($response),
                "status"  => 400
            ];

            //$this->_helperData->log("Array preference error", 'mercadopago-standard.log');
        }

        return $array_assign;

//        $array_assign = [
//            "message" => __('An error has occurred. Please refresh the page.'),
//            "status"  => 400
//        ];
//        return $array_assign;
    }

    /**
     * Return array with data about the purchase to send to the api
     *
     * @return array
     */
    public function makePreference()
    {
//        $arr = [];

        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
        $customer = $this->_customerSession->getCustomer();
        $payment = $order->getPayment();
        $paramsShipment = new \Magento\Framework\DataObject();
        $paramsShipment->setParams([]);

//        $this->_eventManager->dispatch(
//            'mercadopago_standard_make_preference_before',
//            ['params' => $paramsShipment, 'order' => $order]
//        );

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
            //$this->_helperData->log("Total itens: " . $total_item, 'mercadopago-standard.log');
            //$this->_helperData->log("Total order: " . $order_amount, 'mercadopago-standard.log');
            //$this->_helperData->log("Difference add itens: " . $diff_price, 'mercadopago-standard.log');
        }
//        if ($order->canShip()) {
//            $shippingAddress = $order->getShippingAddress();
//            $shipping = $shippingAddress->getData();
//
//            $arr['payer']['phone'] = [
//                "area_code" => "-",
//                "number"    => $shipping['telephone']
//            ];
//
//            $arr['shipments'] = $this->_getParamShipment($paramsShipment, $order, $shippingAddress);
//        }

//        $billingAddress = $order->getBillingAddress()->getData();
//        $arr['payer']['date_created'] = date('Y-m-d', $customer->getCreatedAtTimestamp()) . "T" . date('H:i:s', $customer->getCreatedAtTimestamp());
//        if (!$customer->getId()) {
//            $arr['payer']['email'] = htmlentities($billingAddress['email']);
//            $arr['payer']['first_name'] = htmlentities($billingAddress['firstname']);
//            $arr['payer']['last_name'] = htmlentities($billingAddress['lastname']);
//        } else {
//            $arr['payer']['email'] = htmlentities($customer->getEmail());
//            $arr['payer']['first_name'] = htmlentities($customer->getFirstname());
//            $arr['payer']['last_name'] = htmlentities($customer->getLastname());
//        }

        if (isset($payment['additional_information']['doc_number']) && $payment['additional_information']['doc_number'] != "") {
            $arr['payer']['identification'] = [
                "type"   => "CPF",
                "number" => $payment['additional_information']['doc_number']
            ];
        }

//        $arr['payer']['address'] = [
//            "zip_code"      => $billingAddress['postcode'],
//            "street_name"   => $billingAddress['street'] . " - " . $billingAddress['city'] . " - " . $billingAddress['country_id'],
//            "street_number" => ""
//        ];

        $arr['url_success'] = $this->_urlBuilder->getUrl('checkout/onepage/success');
        $arr['url_pending'] = $this->_urlBuilder->getCurrentUrl();
        $arr['url_failure'] = $this->_urlBuilder->getUrl('checkout/onepage/failure');

        return $arr;
    }

//    protected function _getParamShipment($params, $order, $shippingAddress) {
//        $paramsShipment = $params->getParams();
//        if (empty($paramsShipment)) {
//            $paramsShipment = $params->getData();
//            $paramsShipment['cost'] = (float)$order->getBaseShippingAmount();
//            $paramsShipment['mode'] = 'custom';
//        }
//        $paramsShipment['receiver_address'] = $this->getReceiverAddress($shippingAddress);
//        return $paramsShipment;
//    }

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
//
//    /**
//     * Calculate discount of magento site and set data in arr param
//     *
//     * @param $arr
//     * @param $order
//     */
//    protected function _calculateDiscountAmount(&$arr, $order)
//    {
//        if ($order->getDiscountAmount() < 0) {
//            $arr[] = [
//                "title"       => "Store discount coupon",
//                "description" => "Store discount coupon",
//                "quantity"    => 1,
//                "unit_price"  => (float)$order->getDiscountAmount()
//            ];
//        }
//    }
//
//    /**
//     * @param $arr
//     * @param $order
//     */
//    protected function _calculateBaseTaxAmount(&$arr, $order)
//    {
//        if ($order->getBaseTaxAmount() > 0) {
//            $arr[] = [
//                "title"       => "Store taxes",
//                "description" => "Store taxes",
//                "quantity"    => 1,
//                "unit_price"  => (float)$order->getBaseTaxAmount()
//            ];
//        }
//    }
//
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

//    /**
//     * @return mixed
//     */
//    public function getBannerCheckoutUrl()
//    {
//        return $this->getConfigData('banner_checkout');
//    }

    /**
     * @return string
     */
    public function getActionUrl()
    {
        return $this->_urlBuilder->getUrl(self::ACTION_URL);
    }

//    /**
//     * Check whether payment method can be used
//     *
//     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
//     *
//     * @return bool
//     * @throws \Exception
//     */
//    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
//    {
//        $parent = parent::isAvailable($quote);
//        $clientId = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_ID, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//        $clientSecret = $this->_scopeConfig->getValue(\Wipei\WipeiPayment\Helper\Data::XML_PATH_CLIENT_SECRET, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//        $standard = (!empty($clientId) && !empty($clientSecret));
//
//        if (!$parent || !$standard) {
//            return false;
//        }
//
//        return $this->_helperData->isValidClientCredentials($clientId, $clientSecret);
//
//    }
//
    /**
     * Return success page url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
//        $url = 'http://localhost/index.php/checkout/onepage/success';
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