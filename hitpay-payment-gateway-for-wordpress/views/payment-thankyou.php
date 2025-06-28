<div id="wp-hitpay-payment-thankyou">
    <p>We have received your order and thank you for making the payment.</p>
    <p><strong>Customer Email:</strong> <?php echo $email?></p>
    <p><strong>Wordpress Payment ID:</strong> <?php echo $order_id?></p>
    <p><strong>HitPay Reference:</strong> <?php echo $reference?></p>
    <p><strong>Amount Paid:</strong> <?php echo $amount. ' '.$currency?></p>
    <p><strong>Payment Status:</strong> <?php echo ucwords($status)?></p>
</div>