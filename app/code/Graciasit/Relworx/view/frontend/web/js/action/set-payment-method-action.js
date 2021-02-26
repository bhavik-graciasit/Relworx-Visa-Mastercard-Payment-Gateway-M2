/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/url',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, url, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        return function (messageContainer) {
			jQuery('.action.primary.checkout').attr('disabled','disabled');
            jQuery(function ($) {
                $.ajax({
                    url: url.build('relworx/checkout/start'),
                    type: 'get',
                    dataType: 'json',
                    cache: false,
                    processData: false, // Don't process the files
                    contentType: false, // Set content type to false as jQuery will tell the server its a query string request
                    success: function (data) {
                        if(data['paymentUrl'])
                        {
                            $.mage.redirect(data['paymentUrl']);
                        }
                        else
                        {
                            $.mage.redirect(url.build(data['errorUrl']));
                        }
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });
            });


        };
    }
);