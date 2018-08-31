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
    protected $dataHelper;

    /**
     * @var \Wipei\WipeiPayment\Model\WipeiPayment
     */
    protected $paymentModel;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Wipei\WipeiPayment\Helper\Status
     */
    protected $_statusHelper;
    protected $_order;

    const LOG_NAME = 'wipei.log';

    /**
     * Standard constructor.
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Wipei\WipeiPayment\Model\WipeiPaymentFactory   $paymentFactory
     * @param \Wipei\WipeiPayment\Helper\Data                 $dataHelper
     * @param \Wipei\WipeiPayment\Model\WipeiPayment          $paymentModel
     * @param \Magento\Sales\Model\OrderFactory               $orderFactory
     * @param \Wipei\WipeiPayment\Helper\Status               $statusHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Wipei\WipeiPayment\Model\WipeiPaymentFactory $paymentFactory,
        \Wipei\WipeiPayment\Helper\Data $dataHelper,
        \Wipei\WipeiPayment\Helper\Status $statusHelper,
        \Wipei\WipeiPayment\Model\WipeiPayment $paymentModel,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->_paymentFactory = $paymentFactory;
        $this->dataHelper = $dataHelper;
        $this->paymentModel = $paymentModel;
        $this->_orderFactory = $orderFactory;
        $this->_statusHelper = $statusHelper;
        parent::__construct($context);
    }

    protected function _isValidResponse($response)
    {
        return ($response['status'] == 200 || $response['status'] == 201);
    }

    /**
     * @throws \Exception
     */
    protected function _getFormattedPaymentData($paymentId, $data = [])
    {
        $response = $this->paymentModel->getPayment($paymentId);
        $payment = $response['response'];

        return  $this->_statusHelper->formatArrayPayment($data, $payment, self::LOG_NAME);
    }

    /**
     * Controller Action
     */
    public function execute()
    {
        $request = $this->getRequest();
        //notification received
        $this->dataHelper->log("Notification received ", self::LOG_NAME, $request->getParams());

        $id = $request->getParam('id');
        if (empty($id)) {
            $this->dataHelper->log("Order id not found", self::LOG_NAME, $request->getParams());
            $this->getResponse()->setBody("Merchant Order not found");
            $this->getResponse()->setHttpResponseCode(404);

            return;
        }

        $data = $this->_getFormattedPaymentData($id);
        $statusFinal = $data['status'];

        $this->_order = $this->paymentModel->_getOrder($data['external_reference']);

        if (!$this->_orderExists() || $this->_order->getStatus() == 'canceled') {
            return;
        }

        $this->dataHelper->log("Update Order", self::LOG_NAME);
        $this->_statusHelper->setStatusUpdated($data, $this->_order);
        $this->_statusHelper->updateOrder($data, $this->_order);

        if ($statusFinal != false) {
            $data['status_final'] = $statusFinal;
            $this->dataHelper->log("Received Payment data", self::LOG_NAME, $data);
            $setStatusResponse = $this->_statusHelper->setStatusOrder($data);
            $this->getResponse()->setBody($setStatusResponse['text']);
            $this->getResponse()->setHttpResponseCode($setStatusResponse['code']);
        } else {
            $this->getResponse()->setBody("Status not final");
            $this->getResponse()->setHttpResponseCode(200);
        }

        $this->dataHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());

    }

    /**
     * Collect data from notification content
     *
     * @param $merchantOrder
     *
     * @return array
     * @throws \Exception
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

    protected function _orderExists()
    {
        if ($this->_order->getId()) {
            return true;
        }
        $this->getResponse()->getBody('External reference not found');
        $this->getResponse()->setHttpResponseCode(404);
        $this->dataHelper->log("Http code", self::LOG_NAME, $this->getResponse()->getHttpResponseCode());

        return false;
    }
}