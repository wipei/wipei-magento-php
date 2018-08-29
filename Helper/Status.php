<?php
namespace Wipei\WipeiPayment\Helper;

/**
 * Class StatusUpdate
 *
 * @package Wipei\WipeiPayment\Helper
 */
class Status
    extends \Magento\Payment\Helper\Data
{

    protected $_finalStatus = ['rejected', 'cancelled', 'refunded', 'charge_back'];
    protected $_notFinalStatus = ['authorized', 'process', 'in_mediation'];

    /**
     * @var bool flag indicates when status was updated by notifications.
     */
    protected $_statusUpdatedFlag = false;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Status\Collection
     */
    protected $_statusFactory;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $_creditmemoFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    protected $_dataHelper;
    protected $_coreHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Sales\Model\ResourceModel\Status\Collection $statusFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Wipei\WipeiPayment\Helper\Data $dataHelper,
        \Wipei\WipeiPayment\Model\WipeiPayment $coreHelper,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_orderFactory = $orderFactory;
        $this->_statusFactory = $statusFactory;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_dataHelper = $dataHelper;
        $this->_coreHelper = $coreHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_orderSender = $orderSender;
    }

    /**
     * @return bool return updated flag
     */
    public function isStatusUpdated()
    {
        return $this->_statusUpdatedFlag;
    }

//    /**
//     * @return mixed
//     */
//    public function getOrderStatusRefunded()
//    {
//        return $this->scopeConfig->getValue('payment/mercadopago/order_status_refunded');
//    }

    /**
     * Set flag status updated
     *
     * @param $notificationData
     * @param $order
     */
    public function setStatusUpdated($notificationData, $order)
    {
        $status = $notificationData['status'];
        $statusDetail = $notificationData['status_detail'];

        if (!is_null($order->getPayment()) && $order->getPayment()->getAdditionalInformation('second_card_token')) {
            $this->_statusUpdatedFlag = false;
            return;
        }

        //get the posible status update order
        $statusToUpdate = $this->getStatusOrder($status);
        $order = $this->_coreHelper->_getOrder($notificationData["external_reference"]);
        $commentsObject = $order->getStatusHistoryCollection(true);

        //check if the status has been updated in some time
        foreach ($commentsObject as $commentObj) {
            if($commentObj->getStatus() == $statusToUpdate){
                $this->_statusUpdatedFlag = true;
            }
        }
    }

//    protected function _getMulticardLastValue($value)
//    {
//        $statuses = explode('|', $value);
//
//        return str_replace(' ', '', array_pop($statuses));
//    }

    /**
     * Return order status based on admin configuration
     *
     * @param $status
     *
     * @return mixed
     */
    public function getStatusOrder($status)
    {
        switch ($status) {
            case 'approved': {
                $status = $this->scopeConfig->getValue('payment/wipei_wipeipayment/order_status_approved', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                break;
            }
            case 'cancelled': {
                $status = $this->scopeConfig->getValue('payment/wipei_wipeipayment/order_status_cancelled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                break;
            }
            default: {
                $status = $this->scopeConfig->getValue('payment/wipei_wipeipayment/order_status_pending', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            }
        }

        return $status;
    }

    /**
     * Get the Magento assigned state of an order status
     *
     * @param string $status
     * @return string
     */
    public function _getAssignedState($status)
    {
        $collection = $this->_statusFactory
            ->joinStates()
            ->addFieldToFilter('main_table.status', $status);

        $collectionItems = $collection->getItems();

        return array_pop($collectionItems)->getState();
    }

    /**
     * Return raw message for payment detail
     *
     * @param $status
     * @param $payment
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getMessage($status, $payment)
    {
        $rawMessage = __('Approved.');
        $rawMessage .= __('<br/> Payment id: %1', $payment['order_id']);
        $rawMessage .= __('<br/> Status: %1', $payment['status']);
        $rawMessage .= __('<br/> Status Detail: %1', $payment['status_detail']);

        return $rawMessage;
    }
//
//    /**
//     * Returns status that must be set to order, if a not final status exists then the last of this statuses
//     * is returned. Else the last of final statuses is returned.
//     *
//     * @param $dataStatus
//     * @param $merchantOrder
//     *
//     * @return string
//     */
//    public function getStatusFinal($dataStatus, $merchantOrder)
//    {
//        //if (isset($merchantOrder['paid_amount']) && $merchantOrder['total_amount'] == $merchantOrder['paid_amount']) {
//        //  return 'approved';
//        //}
//        if ($merchantOrder['total_amount'] == $merchantOrder['paid_amount']) {
//            return 'approved';
//        }
//        $payments = $merchantOrder['payments'];
//        $statuses = explode('|', $dataStatus);
//        foreach ($statuses as $status) {
//            $status = str_replace(' ', '', $status);
//            if (in_array($status, $this->_notFinalStatus)) {
//                $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $this->_notFinalStatus);
//
//                return $payments[$lastPaymentIndex]['status'];
//            }
//        }
//
//        $lastPaymentIndex = $this->_getLastPaymentIndex($payments, $this->_finalStatus);
//
//        return $payments[$lastPaymentIndex]['status'];
//    }
//
//    /**
//     * @param $payments
//     * @param $status
//     *
//     * @return int
//     */
//    protected function _getLastPaymentIndex($payments, $status)
//    {
//        $dates = [];
//        foreach ($payments as $key => $payment) {
//            if (in_array($payment['status'], $status)) {
//                $dates[] = ['key' => $key, 'value' => $payment['last_modified']];
//            }
//        }
//        usort($dates, ['MercadoPago\Core\Controller\Notifications\Standard', "_dateCompare"]);
//        if ($dates) {
//            $lastModified = array_pop($dates);
//
//            return $lastModified['key'];
//        }
//
//        return 0;
//    }
//
//    /**
//     * @param $merchantOrder
//     *
//     * @return array
//     */
//    public function getShipmentsArray($merchantOrder)
//    {
//        return (isset($merchantOrder['shipments'][0])) ? $merchantOrder['shipments'][0] : [];
//    }
//
//    /**
//     * @param $payment \Magento\Sales\Model\Order\Payment
//     */
//    public function generateCreditMemo($payment, $order = null)
//    {
//        if (empty($order)) {
//            $order = $this->_orderFactory->create()->loadByIncrementId($payment["order_id"]);
//        }
//
//        if ($payment['amount_refunded'] == $payment['total_paid_amount']) {
//            $this->_createCreditmemo($order, $payment);
//            $order->setForcedCanCreditmemo(false);
//            $order->setActionFlag('ship', false);
//            $order->save();
//        } else {
//            $this->_createCreditmemo($order, $payment);
//        }
//    }
//
//    /**
//     * @var $order      \Magento\Sales\Model\Order
//     * @var $creditMemo \Magento\Sales\Model\Order\Creditmemo
//     * @var $payment    \Magento\Sales\Model\Order\Payment
//     */
//    protected function _createCreditmemo($order, $data)
//    {
//        $order->setExternalRequest(true);
//        $creditMemos = $order->getCreditmemosCollection()->getItems();
//
//        $previousRefund = 0;
//        foreach ($creditMemos as $creditMemo) {
//            $previousRefund = $previousRefund + $creditMemo->getGrandTotal();
//        }
//        $amount = $data['amount_refunded'] - $previousRefund;
//        if ($amount > 0) {
//            $order->setExternalType('partial');
//            $creditmemo = $this->_creditmemoFactory->createByOrder($order, [-1]);
//            if (count($creditMemos) > 0) {
//                $creditmemo->setAdjustmentPositive($amount);
//            } else {
//                $creditmemo->setAdjustmentNegative($amount);
//            }
//            $creditmemo->setGrandTotal($amount);
//            $creditmemo->setBaseGrandTotal($amount);
//            //status "Refunded" for creditMemo
//            $creditmemo->setState(2);
//            $creditmemo->getResource()->save($creditmemo);
//            $order->setTotalRefunded($data['amount_refunded']);
//            $order->getResource()->save($order);
//        }
//    }

    /**
     * Collect data from notification content to update order info
     *
     * @param $data
     * @param $payment
     * @param $logName
     *
     * @return mixed
     */
    public function formatArrayPayment($data, $payment, $logName)
    {
//      $this->_dataHelper->log("Format Array", $logName);

//      $data = $this->_updateAtributesData($data, $payment);
      
      $data['external_reference'] = $payment['external_reference'];
      $data['order_id'] = $payment['id'];
      $data['total_paid_amount'] = $payment['total'];
      $data['status'] = $payment['status'];
      $data['status_detail'] = 'test ok';

      return $data;
    }

    /**
     * Updates order status ond creates invoice
     *
     * @param      $payment
     *
     * @return array
     * @throws \Exception
     */
    public function setStatusOrder($payment)
    {
        $order = $this->_coreHelper->_getOrder($payment["external_reference"]);

        $statusDetail = $payment['status_detail'];
        $status = $payment['status'];

        if (isset($payment['status_final'])) {
            $status = $payment['status_final'];
        }

        $message = $this->getMessage($status, $payment);
        if ($this->isStatusUpdated()) {
            return ['text' => $message, 'code' => 200];
        }

        //if state is not complete updates according to setting
        $this->_updateStatus($order, $status, $message, $statusDetail);

        $statusSave = $order->save();
//        $this->_dataHelper->log("Update order", 'mercadopago.log', $statusSave->getData());
//        $this->_dataHelper->log($message, 'mercadopago.log');

//        try {
//            $infoPayments = $order->getPayment()->getAdditionalInformation();
//            if ($this->_getMulticardLastValue($status) == 'approved') {
//                $this->_handleTwoCards($payment, $infoPayments);
//
//                $this->_dataHelper->setOrderSubtotals($payment, $order);
//                $this->_createInvoice($order, $message);
//
//                //Associate card to customer
//                if (isset($payment['metadata']) && isset($payment['metadata']['token'])) {
//                    $order->getPayment()->getMethodInstance()->customerAndCards($payment['metadata']['token'], $payment);
//                }
//
//            } elseif ($status == 'refunded' || $status == 'cancelled') {
//                $order->setExternalRequest(true);
//                $order->cancel();
//            }

            return ['text' => $message, 'code' => 200];
//        } catch (\Exception $e) {
//            $this->_dataHelper->log("erro in set order status: " . $e, 'mercadopago.log');
//
//            return ['text' => $e, 'code' => \MercadoPago\Core\Helper\Response::HTTP_BAD_REQUEST];
//        }
    }

//    protected function _handleTwoCards(&$payment, $infoPayments)
//    {
//        if (isset($infoPayments['second_card_token']) && !empty($infoPayments['second_card_token'])) {
//            $payment['total_paid_amount'] = $infoPayments['total_paid_amount'];
//            $payment['transaction_amount'] = $infoPayments['transaction_amount'];
//            $payment['status'] = $infoPayments['status'];
//        }
//    }
//
//    protected function _createInvoice($order, $message)
//    {
//        if (!$order->hasInvoices()) {
//            $invoice = $order->prepareInvoice();
//            $invoice->register();
//            $invoice->pay();
//            $this->_transactionFactory->create()
//                ->addObject($invoice)
//                ->addObject($invoice->getOrder())
//                ->save();
//
//            $this->_invoiceSender->send($invoice, true, $message);
//        }
//    }

    /**
     * @param $order        \Magento\Sales\Model\Order
     * @param $statusHelper \MercadoPago\Core\Helper\StatusUpdate
     * @param $status
     * @param $message
     * @param $statusDetail
     */
    protected function _updateStatus($order, $status, $message, $statusDetail)
    {
        if ($order->getState() !== \Magento\Sales\Model\Order::STATE_COMPLETE) {
            $statusOrder = $this->getStatusOrder($status);
//            $emailAlreadySent = false;
//            //get scope config
//            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
//            $scopeConfig = $objectManager->create('\Magento\Framework\App\Config\ScopeConfigInterface');
//            $emailOrderCreate = $scopeConfig->getValue('payment/mercadopago/email_order_create', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if($statusOrder == 'canceled'){
                $order->cancel();
            }else{
                $order->setState($this->_getAssignedState($statusOrder));
            }

            //add comment to history
            $order->addStatusToHistory($statusOrder, $message, true);

            //ckeck is active send email when create order
//            if($emailOrderCreate){
//                if (!$order->getEmailSent()){
//                    $this->_orderSender->send($order, true, $message);
//                    $emailAlreadySent = true;
//                }
//            }

            //if the email has not been sent check sent in status
//            if($emailAlreadySent === false){
//                // search the list of statuses that can send email
//                $statusEmail = $scopeConfig->getValue('payment/mercadopago/email_order_update', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//                $statusEmailList = explode(",", $statusEmail);
//
//                //check if the status is on the authorized list
//                if(in_array($status, $statusEmailList)){
//                    $orderCommentSender = $objectManager->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');
//                    $orderCommentSender->send($order, $notify = '1' , str_replace("<br/>", "", $message));
//                }
//            }
        }
    }
    /**
     * Set order and payment info
     *
     * @param $data
     */
    public function updateOrder($data, $order = null)
    {
//        $this->_dataHelper->log("Update Order", 'mercadopago-notification.log');
        if (!$this->isStatusUpdated()) {
            try {
                if (!$order) {
                    $order = $this->_coreHelper->_getOrder($data["external_reference"]);
                }

                //update payment info
                $paymentOrder = $order->getPayment();
                $paymentAdditionalInfo = $paymentOrder->getAdditionalInformation();

                $additionalFields = [
                    'status',
                    'status_detail',
                    'id',
                    'transaction_amount',
                    'cardholderName',
                    'installments',
                    'statement_descriptor',
                    'trunc_card',
                    'payer_identification_type',
                    'payer_identification_number'

                ];


                foreach ($additionalFields as $field) {
                    if (isset($data[$field]) && empty($paymentAdditionalInfo['second_card_token'])) {
                        $paymentOrder->setAdditionalInformation($field, $data[$field]);
                    }
                }

                if (isset($data['order_id'])) {
                    $paymentOrder->setAdditionalInformation('payment_id_detail', $data['order_id']);
                }

                if (isset($data['payer_identification_type']) & isset($data['payer_identification_number'])) {
                    $paymentOrder->setAdditionalInformation($data['payer_identification_type'], $data['payer_identification_number']);
                }

                if (isset($data['payment_method_id'])) {
                    $paymentOrder->setAdditionalInformation('payment_method', $data['payment_method_id']);
                }

                if (isset($data['merchant_order_id'])) {
                    $paymentOrder->setAdditionalInformation('merchant_order_id', $data['merchant_order_id']);
                }

                $paymentStatus = $paymentOrder->save();
//                $this->_dataHelper->log("Update Payment", 'mercadopago.log', $paymentStatus->getData());

                $statusSave = $order->save();
//                $this->_dataHelper->log("Update order", 'mercadopago.log', $statusSave->getData());
            } catch (\Exception $e) {
//                $this->_dataHelper->log("error in update order status: " . $e, 'mercadopago.log');
                $this->getResponse()->setBody($e);

                //if notification proccess returns error, mercadopago will resend the notification.
                $this->getResponse()->setHttpResponseCode(400);
            }
        }
    }

}