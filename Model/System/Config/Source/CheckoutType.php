<?php
namespace Wipei\WipeiPayment\Model\System\Config\Source;

/**
 * Class CheckoutType
 *
 * @package Wipei\WipeiPayment\Model\System\Config\Source
 */
class CheckoutType
    implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return available checkout types
     * @return array
     */
    public function toOptionArray()
    {
        $arr = [
            ["value" => "redirect", 'label' => __("Redirect")],
            ["value" => "modal", 'label' => __("Modal")]
        ];

        return $arr;
    }
}