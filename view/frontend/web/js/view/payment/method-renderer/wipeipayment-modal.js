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
             * Places order in pending payment status.
             */
            placePendingPaymentOrder: function () {
                var self = this;
                this.afterPlaceOrder = function () {
                    self.paymentReady(true);
                };
                function bindEvent(element, eventName, eventHandler) {
                    if (element.addEventListener){
                        element.addEventListener(eventName, eventHandler, false);
                    } else if (element.attachEvent) {
                        element.attachEvent('on' + eventName, eventHandler);
                    }
                }
                if (this.placeOrder()) {
                    this.isInAction(true);
                    // capture all click events
                    document.addEventListener('click', iframe.stopEventPropagation, true);
                    // addEventListener support for IE8
                    bindEvent(window, 'message', self.modalClose);
                }
            },

            /**
             * receive the modal closing message.
             */
            modalClose: function (event) {
                function getSuccessUrl() {
                    if (window.checkoutConfig.payment['wipei_wipeipayment'] != undefined) {
                        return window.checkoutConfig.payment['wipei_wipeipayment']['successUrl'];
                    }
                    return '';
                }
                function getFailureUrl() {
                    if (window.checkoutConfig.payment['wipei_wipeipayment'] != undefined) {
                        return window.checkoutConfig.payment['wipei_wipeipayment']['failureUrl'];
                    }
                    return '';
                }
                if (event.data === 'success') window.location = getSuccessUrl()
                else if (event.data === 'close') window.location = getFailureUrl();
            },

            /**
             * Hide loader when iframe is fully loaded.
             * @returns {void}
             */
            iframeLoaded: function () {
                fullScreenLoader.stopLoader();
            }
        });
    }
);
