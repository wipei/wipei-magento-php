<?php
namespace Wipei\WipeiPayment\Logger\Handler;

use Monolog\Logger;
/**
 * Wipei logger handler
 */
class System
    extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/wipei.log';

}