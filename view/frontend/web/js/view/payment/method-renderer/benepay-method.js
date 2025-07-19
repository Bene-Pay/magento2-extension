define([
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order'
], function (
        $,
        Component,
        fullScreenLoader,
        placeOrderAction
    ) {
    'use strict';

    return Component.extend({
        defaults: {
            code: 'benepay',
            template: 'Promantus_Benepay/payment/benepay',
            redirectAfterPlaceOrder: false
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();
            // Additional initialization if needed
        },

        initObservable: function () {
            this._super();
            // Initialize observables if needed
            return this;
        },

        /**
         * Get payment method code
         *
         * @returns {string}
         */
        getCode: function () {
            return this.code;
        },
        
        /**
         * Handle the redirect after placing the order
         *
         * @param {string} orderId
         */
        afterPlaceOrder: function (orderId) {
            var redirectBaseUrl = window.checkoutConfig.payment.benepay.redirectUrl;

            if (!orderId) {
                alert('Order ID missing, cannot redirect.');
                return;
            }

            var redirectUrl = redirectBaseUrl + 'order_id/' + orderId;

            window.location.replace(redirectUrl);
        },

        /**
         * Place order action
         * 
         * @returns {boolean}
         */
        beforePlaceOrder: function () {
            let self = this;

            fullScreenLoader.startLoader();
            self.isPlaceOrderActionAllowed(false);

            // Call placeOrderAction and get order ID
            placeOrderAction(this.getData(), this.messageContainer)
                .done(function (orderId) {
                    alert('Order placed successfully with ID: ' + orderId);
                    // Now pass the orderId to the redirect method
                    self.afterPlaceOrder(orderId);
                })
                .fail(function () {
                    fullScreenLoader.stopLoader();
                    self.isPlaceOrderActionAllowed(true);
                });

            return false;
        },
    });
});
