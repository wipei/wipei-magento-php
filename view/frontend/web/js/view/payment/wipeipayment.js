define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'wipei_wipeipayment',
                component: 'Wipei_WipeiPayment/js/view/payment/method-renderer/wipeipayment'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
