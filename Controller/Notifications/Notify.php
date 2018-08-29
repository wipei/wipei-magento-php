<?php
namespace Wipei\WipeiPayment\Controller\Notifications;

/**
 * Class Notify
 *
 * @package Wipei\WipeiPayment\Controller\Notifications
 */
class Notify
    extends \Magento\Framework\App\Action\Action

{
    /**
     * @var \Wipei\WipeiPayment\Model\WipeiPaymentFactory
     */
    protected $_paymentFactory;

    /**
     * @var \Wipei\WipeiPayment\Helper\Data
     */
    protected $coreHelper;

    /**
     * @var \Wipei\WipeiPayment\Model\WipeiPayment
     */
    protected $paymentModel;
//
//    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charge_back'];
//    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Wipei\WipeiPayment\Helper\Status
     */
    protected $_statusHelper;
    protected $_order;

    /**
     * Standard constructor.
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Wipei\WipeiPayment\Model\WipeiPaymentFactory   $paymentFactory
     * @param \Wipei\WipeiPayment\Helper\Data                 $coreHelper
     * @param \Wipei\WipeiPayment\Model\WipeiPayment          $paymentModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Wipei\WipeiPayment\Model\WipeiPaymentFactory $paymentFactory,
        \Wipei\WipeiPayment\Helper\Data $coreHelper,
        \Wipei\WipeiPayment\Helper\Status $statusHelper,
        \Wipei\WipeiPayment\Model\WipeiPayment $paymentModel,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->_paymentFactory = $paymentFactory;
        $this->coreHelper = $coreHelper;
        $this->paymentModel = $paymentModel;
        $this->_orderFactory = $orderFactory;
        $this->_statusHelper = $statusHelper;
        parent::__construct($context);
    }

    protected function _isValidResponse($response)
    {
        return ($response['status'] == 200 || $response['status'] == 201);
    }

//    protected function _responseLog()
//    {
//        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());
//    }

    /**
     * @throws \Exception
     */
    protected function _getFormattedPaymentData($paymentId, $data = [])
    {
        $response = $this->paymentModel->getPayment($paymentId);
        $payment = $response['response'];

        return  $this->_statusHelper->formatArrayPayment($data, $payment, "payment");
    }

//    protected function _shipmentExists($shipmentData, $merchantOrder)
//    {
//        return (!empty($shipmentData) && !empty($merchantOrder));
//    }

    /**
     * Controller Action
     */
    public function execute()
    {
        $request = $this->getRequest();
        //notification received
//        $this->coreHelper->log("Standard Received notification", self::LOG_NAME, $request->getParams());

        $shipmentData = '';
        $merchantOrder = '';
        $id = $request->getParam('id');
//        $topic = $request->getParam('topic');

        if (empty($id)) {
//            $this->coreHelper->log("Merchant Order not found", self::LOG_NAME, $request->getParams());
            $this->getResponse()->setBody("Merchant Order not found");
            $this->getResponse()->setHttpResponseCode(404);

            return;
        }

//        if ($topic == 'merchant_order') {
//            $response = $this->coreModel->getMerchantOrder($id);
////            $this->coreHelper->log("Return merchant_order", self::LOG_NAME, $response);
//            if (!$this->_isValidResponse($response)) {
//                $this->_responseLog();
//
//                return;
//            }
//
//            $merchantOrder = $response['response'];
//            if (count($merchantOrder['payments']) == 0) {
//                $this->_responseLog();
//
//                return;
//            }
//            $data = $this->_getDataPayments($merchantOrder);
//            $statusFinal = $this->_statusHelper->getStatusFinal($data['status'], $merchantOrder);
//            $shipmentData = $this->_statusHelper->getShipmentsArray($merchantOrder);
//
//        } elseif ($topic == 'payment') {

        $data = $this->_getFormattedPaymentData($id);
        $statusFinal = $data['status'];

            // TO DO: check and test if IPN updates the payment information
            // $response = $this->coreModel->getPaymentV1($id);	
            // $payment = $response['response'];	
            // $payment = $this->coreHelper->setPayerInfo($payment);
//        } else {
//            $this->_responseLog();
//
//            return;
//        }

//        // if this happens, we need to generate a credit memo
//        if (isset($data["amount_refunded"]) && $data["amount_refunded"] > 0) {
//            $this->_statusHelper->generateCreditMemo($data);
//        }

        $this->_order = $this->paymentModel->_getOrder($data['external_reference']);

        if (!$this->_orderExists() || $this->_order->getStatus() == 'canceled') {
            return;
        }

//        $this->coreHelper->log("Update Order", self::LOG_NAME);
        $this->_statusHelper->setStatusUpdated($data, $this->_order);
        $this->_statusHelper->updateOrder($data, $this->_order);

//        if ($this->_shipmentExists($shipmentData, $merchantOrder)) {
//            $this->_eventManager->dispatch(
//                'mercadopago_standard_notification_before_set_status',
//                ['shipmentData' => $shipmentData, 'orderId' => $merchantOrder['external_reference']]
//            );
//        }

        if ($statusFinal != false) {
            $data['status_final'] = $statusFinal;
//            $this->coreHelper->log("Received Payment data", self::LOG_NAME, $data);
            $setStatusResponse = $this->_statusHelper->setStatusOrder($data);
            $this->getResponse()->setBody($setStatusResponse['text']);
            $this->getResponse()->setHttpResponseCode($setStatusResponse['code']);
        } else {
            $this->getResponse()->setBody("Status not final");
            $this->getResponse()->setHttpResponseCode(200);
        }
//        if ($this->_shipmentExists($shipmentData, $merchantOrder)) {
//            $this->_eventManager->dispatch('mercadopago_standard_notification_received',
//                ['payment'        => $data,
//                 'merchant_order' => $merchantOrder]
//            );
//        }

//        $this->_responseLog();

    }

    /**
     * Collect data from notification content
     *
     * @param $merchantOrder
     *
     * @return array
     */
    protected function _getDataPayments($merchantOrder)
    {
        $data = array();
        foreach ($merchantOrder['payments'] as $payment) {
            $response = $this->paymentModel->getPayment($payment['id']);
            $payment = $response['response'];
            $data = $this->_statusHelper->formatArrayPayment($data, $payment, self::LOG_NAME);
        }
        return $data;
    }

    public static function _dateCompare($a, $b)
    {
        $t1 = strtotime($a['value']);
        $t2 = strtotime($b['value']);

        return $t2 - $t1;
    }

    protected function _orderExists()
    {
        if ($this->_order->getId()) {
            return true;
        }
//        $this->coreHelper->log(\MercadoPago\Core\Helper\Response::INFO_EXTERNAL_REFERENCE_NOT_FOUND, self::LOG_NAME, $this->_requestData->getParams());
        $this->getResponse()->getBody('External reference not found');
        $this->getResponse()->setHttpResponseCode(404);
//        $this->coreHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());

        return false;
    }
}