<?php
namespace Wipei\WipeiPayment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class MailObserver implements ObserverInterface
{

  protected $_logger;

  public function __construct(\Wipei\WipeiPayment\Logger\Logger $logger)
  {

    $this->_logger = $logger;
  }

  public function execute(Observer $observer)
  {

    $order = $observer->getEvent()->getOrder();
    $payment = $order->getPayment()->getMethod();
    // $this->_logger->info($payment);

    if ($payment == 'wipei_wipeipayment') {
      $this->_logger->info('wipei_wipeipayment - Disabling Email Send');
      $order->setCanSendNewEmailFlag(false);
    }


    return [$observer];
  }
}
