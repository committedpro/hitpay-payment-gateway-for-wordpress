jQuery(document).ready(function(){
    if (isHitpayTransactionPage()) {
        jQuery('#titlediv').hide();
        jQuery('#postcustom').hide();
    } 

    jQuery('#wp_hitpay_refund').on("click", function (evt) {
        evt.preventDefault();

        var result = confirm("Are you sure you wish to process this refund? This action cannot be undone.");
        if (result) {
           const amount = jQuery("#refund_amount").val();
            if (Number(amount) <= 0) {
                alert('Amount should be greater than zero');
            } else {
                disableSubmitButton();
                var config = buildConfigObj(this);
                processCheckout(config);
            }
        }
    });
});

const buildConfigObj = function (form) {
    const amount = jQuery("#refund_amount").val();

    return {
        amount: Number(amount),
        order_id: wp_hitpay_options.post_id,
    };
};

const processCheckout = function (opts) {
    const args = {
        action: "process_refund",
    };

    const dataObj = {
        ...args,
        ...opts,
    };

    jQuery.post(wp_hitpay_options.cb_url, dataObj).done(function (data) {
        var response = JSON.parse(data);
        let message;
        if (response.status === "success") {
            alert(response.message);
            location.href=location.href;
        } else {
            alert(response.message);
            enableSubmitButton();
        }
    });
};

function isHitpayTransactionPage() {
    if (jQuery('#post-body-content').find('#wp-hitpay-transaction-data').length > 0) {
        return true;
    } else {
        return false;
    }
}

const disableSubmitButton = function () {
    jQuery("#wp_hitpay_refund").prop('disabled', true);
    jQuery("#refund_processing").show();
};

const enableSubmitButton = function () {
    jQuery("#wp_hitpay_refund").prop('disabled', false);
    jQuery("#refund_processing").hide();
};