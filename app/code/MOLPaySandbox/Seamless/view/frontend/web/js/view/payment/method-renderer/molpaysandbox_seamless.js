/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order'

    ],
    function ($, Component, additionalValidators, fullScreenLoader, quote, customer, placeOrderAction){

            var payloadmps;

            payloadmps = {
                cartId: quote.getQuoteId(),
                billingAddress: quote.billingAddress()
            };

            if (customer.isLoggedIn()) {
              //nothing todo  
            } else {
                payloadmps.email = quote.guestEmail;
            }

        return Component.extend({
            defaults: {
                template: 'MOLPaySandbox_Seamless/payment/form'
            },

            getCode: function() {
                return 'molpaysandbox_seamless';
            },

            getData: function() {
                console.log(this.item.method);
                return {
                    'method': this.item.method
                };
            },

            getCurrentCartId: function(){
                return payloadmps.cartId;
            },

            getCurrentCustomerEmail: function(){
                return payloadmps.email;
            },
            
            getActiveChannels: function(){
                return window.checkoutConfig.payment.molpaysandbox_seamless.channels_payment;            
                //return "123test";
            },

            placeOrder: function(){
                var myForm = $("#seamless");
                if (myForm[0].checkValidity()) {
                        myForm.trigger("submit");
                }
            }

        });
        
    }
);