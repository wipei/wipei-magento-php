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
        \Wipei\WipeiPayment\Helper\Data $dataHelper,
        \Wipei\WipeiPayment\Model\WipeiPayment $coreHelper,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_orderFactory = $orderFactory;
        $this->_statusFactory = $statusFactory;
        $this->_dataHelper = $dataHelper;
        $this->_coreHelper = $coreHelper;
        $this->_transactionFactory = $transactionFactory;
        $this->_invoiceService = $invoiceService;
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

    /**
     * Set flag status updated
     *
     * @param $notificationData
     * @param $order
     */
    public function setStatusUpdated($notificationData, $order)
    {
        $status = $notificationData['status'];

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
     * @param $payment
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getMessage($payment)
    {
        $rawMessage = __('ok');
        $rawMessage .= __('<br/> Payment id: %1', $payment['order_id']);
        $rawMessage .= __('<br/> Status: %1', $payment['status']);
        $rawMessage .= __('<br/> Status Detail: %1', $payment['status_detail']);

        return $rawMessage;
    }

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
      $this->_dataHelper->log("Format Array", $logName);
      
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

        $message = $this->getMessage($payment);
        if ($this->isStatusUpdated()) {
            return ['text' => $message, 'code' => 200];
        }

        // if state is not complete, updates according to setting
        $this->_updateStatus($order, $status, $message, $statusDetail);

        $statusSave = $order->save();
        $this->_dataHelper->log("Update order", 'wipei.log', $statusSave->getData());
        $this->_dataHelper->log($message, 'wipei.log');

        return ['text' => $message, 'code' => 200];
//        } catch (\Exception $e) {
//            $this->_dataHelper->log("Error in set order status: " . $e, 'wipei.log');
//
//            return ['text' => $e, 'code' => 400];
//        }
    }

    /**
     * @param $order        \Magento\Sales\Model\Order
     * @param $status \Wipei\WipeiPayment\Helper\Status
     * @param $message
     * @param $statusDetail
     */
    protected function _updateStatus($order, $status, $message, $statusDetail)
    {
        if ($order->getState() !== \Magento\Sales\Model\Order::STATE_COMPLETE) {
            $statusOrder = $this->getStatusOrder($status);

            if($statusOrder == 'canceled'){
                $order->cancel();
            }else{
                $magentoState = $this->_getAssignedState($statusOrder);
                $order->setState($magentoState);
                if ($status === "approved"){
                    $this->_dataHelper->log("Order Approved - Creating invoice", 'wipei.log');
                    $this->createInvoice($order);
                }
                // if ($magentoState === \Magento\Sales\Model\Order::STATE_COMPLETE){
                //     $this->createInvoice($order);
                // }

            }

            //add comment to history
            $order->addStatusToHistory($statusOrder, $message, true);
        }
    }


    /**
     * Create Invoice for order
     * @param $order        \Magento\Sales\Model\Order
     */
    public function createInvoice($order)
    {
        try {
            if (!$order->canInvoice()) {
                return null;
            }

            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();

            $transaction = $this->_transactionFactory->create()
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transaction->save();
        } catch (\Exception $e) {
            $order->addStatusHistoryComment('Exception message: ' . $e->getMessage(), false);
            $order->save();
            return null;
        }
    }


    /**
     * Set order and payment info
     *
     * @param $data
     */
    public function updateOrder($data, $order = null)
    {
        $this->_dataHelper->log("Update Order", 'wipei.log');
        if (!$this->isStatusUpdated()) {
            try {
                if (!$order) {
                    $order = $this->_coreHelper->_getOrder($data["external_reference"]);
                }

                //update payment info
                $paymentOrder = $order->getPayment();


                if (isset($data['order_id'])) {
                    $paymentOrder->setAdditionalInformation('payment_id_detail', $data['order_id']);
                }

                $paymentStatus = $paymentOrder->save();
                $this->_dataHelper->log("Update Payment", 'wipei.log', $paymentStatus->getData());

                $statusSave = $order->save();
                $this->_dataHelper->log("Update order", 'wipei.log', $statusSave->getData());
            } catch (\Exception $e) {
                $this->_dataHelper->log("Error in order status update: " . $e, 'wipei.log');
                $this->getResponse()->setBody($e);

                $this->getResponse()->setHttpResponseCode(400);
            }
        }
    }

}