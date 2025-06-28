"use strict";

var form = jQuery(".wp-hitpay-now-form"),
    redirectUrl;

if (form) {
    if (wp_hitpay_options.payment_canceled) {
        var canceled_message = "Payment canceled by customer. ";
        jQuery(form)
            .find("#wp-hitpay-notice")
            .text(canceled_message)
            .removeClass(function () {
                return jQuery(form).find("#wp-hitpay-notice").attr("class");
            })
            .addClass('failed');
        
        jQuery('#wp-hitpay-notice').show();
    }

    form.on("submit", function (evt) {
        evt.preventDefault();
        disableSubmitButton();
        var config = buildConfigObj(this);
        processCheckout(config);
    });
}

const buildConfigObj = function (form) {
    const formData = jQuery(form).data();
    const amount = formData.amount || jQuery(form).find("#wp-hitpay-amount").val();
    const email = formData.email || jQuery(form).find("#wp-hitpay-customer-email").val();
    const firstname = formData.firstname || jQuery(form).find("#wp-hitpay-first-name").val();
    const lastname = formData.lastname || jQuery(form).find("#wp-hitpay-last-name").val();
    const currency = formData.currency || jQuery(form).find("#wp-hitpay-currency").val();
    const pageid = formData.pageid;

    return {
        amount: Number(amount),
        customer_email: email,
        customer_firstname: firstname,
        customer_lastname: lastname,
        currency: currency,
        pageid: pageid,
    };
};

const processCheckout = function (opts) {
    jQuery('#wp-hitpay-notice').hide();
    const args = {
        action: "process_payment",
        wp_hitpay_sec_code: jQuery(form).find("#wp_hitpay_sec_code").val(),
    };

    const dataObj = {
        ...args,
        ...opts,
    };

    jQuery.post(wp_hitpay_options.cb_url, dataObj).done(function (data) {
        var response = JSON.parse(data);
        redirectUrl = response.redirect;
        let message;
        if (response.status === "success") {
            message = "Redirecting to the gateway...";

            jQuery(form)
                .find("#wp-hitpay-notice")
                .text(message)
                .removeClass(function () {
                    return jQuery(form).find("#wp-hitpay-notice").attr("class");
                })
                .addClass(response.status);

            jQuery('#wp-hitpay-notice').show();

            setTimeout(redirectTo, 1000, redirectUrl);
        } else {
            if (!response.message) {
                message = "Payment Failed, Please try again or contact the site administrator";
            } else {
                message = response.message;
            }
            
            jQuery(form)
                .find("#wp-hitpay-notice")
                .text(message)
                .removeClass(function () {
                    return jQuery(form).find("#wp-hitpay-notice").attr("class");
                })
                .addClass(response.status);
            
            jQuery('#wp-hitpay-notice').show();
            enableSubmitButton();
        }
    });
};

const redirectTo = function (url) {
    if (url) {
        location.href = url;
    }
};

const disableSubmitButton = function () {
    jQuery(form).find(".wp-hitpay-pay-now-button").prop('disabled', true);
};

const enableSubmitButton = function () {
    jQuery(form).find(".wp-hitpay-pay-now-button").prop('disabled', false);
};
