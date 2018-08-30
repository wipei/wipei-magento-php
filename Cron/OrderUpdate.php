<?php

namespace Wipei\WipeiPayment\Cron;

class OrderUpdate
{

    /**
     * @var \Wipei\WipeiPayment\Helper\Status
     */
    protected $_statusHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Wipei\WipeiPayment\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Wipei\WipeiPayment\Model\WipeiPayment
     */
    protected $paymentModel;

    const LOG_FILE = 'wipei-order-synchronized.log';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Wipei\WipeiPayment\Helper\Status $statusUpdate,
        \Wipei\WipeiPayment\Helper\Data $helper,
        \Wipei\WipeiPayment\Model\WipeiPayment $core,
        array $data = []
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_statusHelper = $statusUpdate;
        $this->_helper = $helper;
        $this->paymentModel = $core;
    }

    public function execute(){
//       $hours = $this->_scopeConfig->getValue('payment/wipei_wipeipayment/hours_number');
        $hours = 12;

        // filter to date:
        $fromDate = date('Y-m-d H:i:s', strtotime('-'.$hours. ' hours'));
        $toDate = date('Y-m-d H:i:s', strtotime("now"));

        $collection = $this->_orderCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->join(
                ['payment' => 'sales_order_payment'],
                'main_table.entity_id=payment.parent_id',
                ['payment_method' => 'payment.method']
            )
            ->addFieldToFilter('status' ,["eq" => 'pending'])
            ->addFieldToFilter('created_at', ['from'=>$fromDate, 'to'=>$toDate])
        ;

        foreach($collection as $orderByPayment){
            $order = $orderByPayment;
            $paymentOrder = $order->getPayment();

            if ($paymentOrder->getMethod() == "wipei_wipeipayment"){
                $orderId = $order->getIncrementId();

                if (isset($orderId)) {
                    $paymentData = $this->_getFormattedPaymentData($orderId);
                    $statusOrder = $this->_statusHelper->getStatusOrder($paymentData['status']);

                    if (isset($statusOrder) && ($order->getStatus() !== $statusOrder)) {
//                      $this->_helper->log("OrderUpdate merchant_order:", self::LOG_FILE, $merchantOrderData);
                        $this->_updateOrder($order, $statusOrder);
                    }
//                    } else{
//                        $this->_helper->log('Error updating status order using cron whit the merchantOrder num: '. $merchantOrderId .'mercadopago.log');
//                    }
                }
            }
        }
    }

    /**
     * @param $order
     * @param $statusOrder
     * @throws \Exception
     */
    protected function _updateOrder($order, $statusOrder){

        if($statusOrder == 'canceled'){
            $order->cancel();
        }else{
            $order->setState($this->_statusHelper->_getAssignedState($statusOrder));
        }

        $order->addStatusToHistory($statusOrder, $this->_statusHelper->getMessage($statusOrder, $statusOrder), true);
        $order->save();
    }

    /**
     * @throws \Exception
     */
    protected function _getFormattedPaymentData($orderId, $data = [])
    {
        $response = $this->paymentModel->getPaymentOrder($orderId);
        $payment = $response['response'];

        return  $this->_statusHelper->formatArrayPayment($data, $payment, "cron");
    }

}