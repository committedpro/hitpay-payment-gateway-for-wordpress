<?php
$order_id = $post->ID;
$amount = get_post_meta($order_id, '_wp_hitpay_payment_amount', true);
$currency = get_post_meta($order_id, '_wp_hitpay_payment_currency', true);
$email = get_post_meta($order_id, '_wp_hitpay_payment_email', true);
$status = get_post_meta($order_id, '_wp_hitpay_payment_status', true );
$firstname = get_post_meta($order_id, '_wp_hitpay_payment_firstname', true );
$lastname = get_post_meta($order_id, '_wp_hitpay_payment_lastname', true );

$payment_request_id = get_post_meta($order_id, 'HitPay_payment_request_id', true );
$transaction_id = get_post_meta($order_id, 'HitPay_transaction_id', true );
$lastname = get_post_meta($order_id, '_wp_hitpay_payment_lastname', true );
$lastname = get_post_meta($order_id, '_wp_hitpay_payment_lastname', true );

$payment_type = get_post_meta($order_id, 'HitPay_payment_method', true );
$hitpay_fee = get_post_meta($order_id, 'HitPay_fees', true );
$hitpay_currency = get_post_meta($order_id, 'HitPay_fees_currency', true );
$refunded = get_post_meta($order_id, 'HitPay_is_refunded', true );
$refund_id = get_post_meta($order_id, 'HitPay_refund_id', true );
$refunded_amount = get_post_meta($order_id, 'HitPay_refund_amount_refunded', true );
$refunded_date = get_post_meta($order_id, 'HitPay_refund_created_at', true );
$net_payment = get_post_meta($order_id, '_wp_hitpay_payment_net_amount', true );

?>
<div id="wp-hitpay-transaction-data" class="postbox">
    <div class="postbox-header">
        <h2 class="hndle ui-sortable-handle">HitPay Transaction Details # <?php echo $order_id?></h2>
    </div>
    <div class="inside">
        <table class="wp-hitpay-transaction-data-table" style="width: 100%">
            <colgroup>
                <col span="1" style="width: 25%;; text-align:right">
                <col span="1" style="width: 1%;">
                <col span="1" style="width: 74%;">
                </colgroup>
			<tbody>
                <tr>
                    <td class="label">Date Created:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $post->post_date;?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Payment Status:</td>
                    <td></td>
                    <td class="total">
                        <select name="_wp_hitpay_payment_status">
                        <?php foreach ($statuses  as $key => $label ) {?>
                            <option value="<?php echo $key?>" <?php if ($status == $key) {?>selected="selected"<?php } ?>><?php echo $label;?></option>
                        <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="label">Customer Email:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $email;?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Customer Name:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $firstname.' '.$lastname;?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Payment Request ID:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $payment_request_id;?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Transaction ID:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $transaction_id;?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Amount Paid:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $amount. ' '.$currency?>
                    </td>
                </tr>
                <?php if ($payment_type) {?>
                <tr>
                    <td class="label">HitPay Payment Type:</td>
                    <td></td>
                    <td class="total">
                        <?php echo ucwords($payment_type);?>
                    </td>
                </tr>
                <?php }?>
                <?php if ($hitpay_fee) {?>
                <tr>
                    <td class="label">HitPay Fee:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $hitpay_fee. ' '.strtoupper($hitpay_currency)?>
                    </td>
                </tr>
                <?php }?>
                <?php if ($refunded) {?>   
                <tr>
                    <td class="label">Refund ID:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $refund_id?>
                    </td>
                </tr> 
                <tr>
                    <td class="label">Refunded:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $refunded_amount. ' '.$currency?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Refunded Date:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $refunded_date?>
                    </td>
                </tr>
                <?php }?>
                <?php if ($net_payment) {?>    
                <tr>
                    <td class="label">Net Payment:</td>
                    <td></td>
                    <td class="total">
                        <?php echo $net_payment. ' '.$currency?>
                    </td>
                </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
    <?php if (!$refunded) {?>
    <div class="action-buttons">
        <p class="add-items">
           <input type="text" name="refund_amount" id="refund_amount" />
           <button type="button" class="button refund-items" id="wp_hitpay_refund">Refund</button>
           <span style="display:none" id="refund_processing">Processing...</span>
        </p>
    </div>
    <?php }?>
</div>

<?php
$notes = get_post_meta($order_id, 'wp_hitpay_payment_notes');
if ($notes && is_array($notes)) {
    krsort($notes);
}
?>
<div id="wp-hitpay-comments-data" class="postbox">
    <div class="postbox-header">
        <h2 class="hndle ui-sortable-handle">Transaction Notes</h2>
    </div>
    <div class="inside">
        <table class="wp-hitpay-comments-data-table" style="width: 100%">
            <colgroup>
                <col span="1" style="width: 96%;; text-align:left">
                <col span="1" style="width: 1%;">
            </colgroup>
			<tbody>
            <?php foreach ($notes as $note) {?>
                <tr>
                    <td class="label"><?php echo $note?></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="label"><hr/></td>
                    <td></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>