define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        if (window.checkoutConfig.payment['wipei_wipeipayment'] != undefined) {
            var checkout_type = window.checkoutConfig.payment['wipei_wipeipayment']['checkout_type'];
            if (checkout_type == 'modal') {
                rendererList.push(
                    {
                        type: 'wipei_wipeipayment',
                        component: 'Wipei_WipeiPayment/js/view/payment/method-renderer/wipeipayment-modal'
                    }
                );
            } else if (checkout_type == 'redirect') {
                rendererList.push(
                    {
                        type: 'wipei_wipeipayment',
                        component: 'Wipei_WipeiPayment/js/view/payment/method-renderer/wipeipayment-redirect'
                    }
                );
            }
        }

        /** Add view logic here if needed */
        return Component.extend({});
    });
