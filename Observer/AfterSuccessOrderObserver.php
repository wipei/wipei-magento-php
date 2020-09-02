<?php
namespace Wipei\WipeiPayment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AfterSuccessOrderObserver implements ObserverInterface
{

  protected $_logger;
  protected $_order;
  protected $_orderSender;


  public function __construct(\Wipei\WipeiPayment\Logger\Logger $logger,
  \Magento\Sales\Api\Data\OrderInterface $order,
  \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender)
  {
    $this->_logger = $logger;
    $this->_order = $order;
    $this->_orderSender = $orderSender;
  }

  public function execute(Observer $observer)
  {
    $this->_logger->info('Order pay successful');
    $orderids = $observer->getEvent()->getOrderIds();

    foreach($orderids as $orderid){
      $this->_logger->info($orderid);
      $order = $this->_order->load($orderid);
      $this->_orderSender->send($order);
    }

    return [$observer];
  }
}
