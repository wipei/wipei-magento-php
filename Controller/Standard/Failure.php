<?php
namespace Wipei\WipeiPayment\Controller\Standard;

/**
 * Class Notify
 *
 * @package Wipei\WipeiPayment\Controller\Standard
 */
class Failure
    extends \Magento\Framework\App\Action\Action

{
    /**
     * @var \Wipei\WipeiPayment\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Wipei\WipeiPayment\Model\WipeiPayment
     */
    protected $paymentModel;


    /**
     * @var \Wipei\WipeiPayment\Helper\Status
     */
    protected $_statusHelper;
    protected $_order;

    const LOG_NAME = 'wipei.log';

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * Standard constructor.
     *
     * @param \Magento\Framework\App\Action\Context           $context
     * @param \Wipei\WipeiPayment\Helper\Data                 $dataHelper
     * @param \Wipei\WipeiPayment\Model\WipeiPayment          $paymentModel
     * @param \Magento\Framework\UrlInterface                 $urlBuilder
     * @param \Wipei\WipeiPayment\Helper\Status               $statusHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Wipei\WipeiPayment\Helper\Data $dataHelper,
        \Wipei\WipeiPayment\Helper\Status $statusHelper,
        \Wipei\WipeiPayment\Model\WipeiPayment $paymentModel,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->dataHelper = $dataHelper;
        $this->paymentModel = $paymentModel;
        $this->_statusHelper = $statusHelper;
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context);
    }

    /**
     * Controller Action
     */
    public function execute()
    {
        $request = $this->getRequest();
        //notification received
        $this->dataHelper->log("Failed order ", self::LOG_NAME, $request->getParams());

        $data['external_reference'] = $this->paymentModel->getLastOrderId();
        $data['status'] = 'cancelled';
        $data['status_detail'] = 'Order canceled due to wrong Wipei payment';
        $data['order_id'] = 'none';

        $this->_order = $this->paymentModel->_getOrder($data['external_reference']);

        if (!$this->_orderExists() || $this->_order->getStatus() == 'canceled') {
            return;
        }

        $this->dataHelper->log("Update Order: cancel", self::LOG_NAME);
        $this->_statusHelper->setStatusUpdated($data, $this->_order);
        $this->_statusHelper->updateOrder($data, $this->_order);

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($this->paymentModel->getFailureUrl());

        $data['status_final'] = $data['status'];
        $this->_statusHelper->setStatusOrder($data);

        return $resultRedirect;
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