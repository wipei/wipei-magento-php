define([
        'Magento_Checkout/js/view/payment/default',
        'ko',
        'Wipei_WipeiPayment/js/model/iframe',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function (Component, ko, iframe, fullScreenLoader) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Wipei_WipeiPayment/payment/wipeipayment-modal',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,
            isInAction: iframe.isInAction,
            initObservable: function () {
                this._super()
                    .observe('paymentReady');

                return this;
            },
            isPaymentReady: function () {
                return this.paymentReady();
            },
            /**
             * Get action url for payment method
             * @returns {String}
             */
            getActionUrl: function () {
                if (window.checkoutConfig.payment['wipei_wipeipayment'] != undefined) {
                    return window.checkoutConfig.payment['wipei_wipeipayment']['actionUrl'];
                }
                return '';
            },

            /**
             * Get url to logo
             * @returns {String}
             */
            getLogoUrl: function () {
                if (window.checkoutConfig.payment['wipei_wipeipayment'] != undefined) {
                    return window.checkoutConfig.payment['wipei_wipeipayment']['logoUrl'];
                }
                return '';
            },

            /**
             * Get height iframne configured
             * @returns {String}
             */
            getConfigHeight: function () {
                return 710;
            },

            /**
             * Places order in pending payment status.
             */
            placePendingPaymentOrder: function () {
                var self = this;
                this.afterPlaceOrder = function () {
                    self.paymentReady(true);
                };
                if (this.placeOrder()) {
                    this.isInAction(true);
                    // capture all click events
                    document.addEventListener('click', iframe.stopEventPropagation, true);
                }
            },

            /**
             * Hide loader when iframe is fully loaded.
             * @returns {void}
             */
            iframeLoaded: function () {
                fullScreenLoader.stopLoader();
            },
        });
    }
);