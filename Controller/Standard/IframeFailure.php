<?php
namespace Wipei\WipeiPayment\Controller\Standard;

/**
 * Class Failure
 *
 * @package Wipei\WipeiPayment\Controller\Standard
 */
class IframeFailure
    extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @param \Magento\Framework\App\Action\Context              $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct(
            $context
        );
    }

    /**
     * Execute Failure action
     */
    public function execute()
    {
        $this->_view->loadLayout(['default', 'wipei_iframe_failure']);
        $this->_view->renderLayout();
    }
}