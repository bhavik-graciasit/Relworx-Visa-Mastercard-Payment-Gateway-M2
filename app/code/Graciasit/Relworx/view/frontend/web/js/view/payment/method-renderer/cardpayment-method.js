/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Graciasit_Relworx/js/action/set-payment-method-action',
        'Magento_Customer/js/customer-data'
    ],
    function (ko, $, Component, setPaymentMethodAction, customerData) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Graciasit_Relworx/payment/cardpayment'
            },

            /** Redirect to relworx */
            continueToRelworx: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            }
           
        });
    }
);
