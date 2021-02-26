/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList )
    {
        'use strict';
        rendererList.push(
            {
                type: 'cardpayment',
                component: 'Graciasit_Relworx/js/view/payment/method-renderer/cardpayment-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);