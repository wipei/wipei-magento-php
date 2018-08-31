<?php
namespace Wipei\WipeiPayment\Logger;
/**
 * Wipei custom logger
 * Class Logger
 *
 * @package Wipei\WipeiPayment\Logger
 */
class Logger
    extends \Monolog\Logger
{

    /**
     * Set logger name
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

}