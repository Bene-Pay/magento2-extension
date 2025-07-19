define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'benepay',
                component: 'Promantus_Benepay/js/view/payment/method-renderer/benepay-method'
            }
        );
        return Component.extend({});
    }
);
