define([
        'jquery',
        'Magento_Checkout/js/view/payment/default'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Wipei_WipeiPayment/payment/wipeipayment'
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'wipei_wipeipayment';
            },

            isActive: function() {
                return true;
            },

            captureWipeiPayment: function() {
                console.log(window.checkoutConfig);
                if(window.confirm("Hola, vas a pagar?")) {
                    this.placeOrder();
                };
                return false;
            },

            redirectAfterPlaceOrder: false,
            /*
            initObservable: function () {
                this._super()
                    .observe('paymentReady');

                return this;
            },
            */
            isPaymentReady: function () {
                return this.paymentReady();
            },

            /**
             * Get action url for payment method.
             * @returns {String}
             */
            getActionUrl: function () {
                if (window.checkoutConfig.payment['wipei_wipeipayment'] != undefined) {
                    return window.checkoutConfig.payment['wipei_wipeipayment']['actionUrl'];
                }
                return '';
            },

            /**
             * Places order in pending payment status.
             */
            afterPlaceOrder: function () {
                window.location = this.getActionUrl();
            }
        });
    }
);