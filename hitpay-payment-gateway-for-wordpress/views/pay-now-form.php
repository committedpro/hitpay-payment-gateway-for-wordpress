<?php
  if ( ! defined( 'ABSPATH' ) ) { exit; }
  $form_id = WP_HitPay::gen_rand_string();
?>

<div>
  <form id="<?php echo $form_id ?>" class="wp-hitpay-now-form" <?php echo $data_attr; ?> >
    <div id="wp-hitpay-notice"></div>
    <?php if ( empty( $atts['email'] ) ) : ?>

      <label class="pay-now"><?php _e( 'Email', 'wp-hitpay' ) ?></label>
      <input class="wp-hitpay-form-input-text" id="wp-hitpay-customer-email" type="email" placeholder="<?php _e( 'Email', 'wp-hitpay' ) ?>" required /><br>

    <?php endif; ?>

    <?php if ( empty( $atts['firstname'] ) ) : ?>

      <label class="pay-now"><?php _e( 'First Name', 'wp-hitpay' ) ?> (Optional) </label>
      <input class="wp-hitpay-form-input-text" id="wp-hitpay-first-name" type="text" placeholder="<?php _e( 'First Name', 'wp-hitpay' ) ?>" /><br>

    <?php endif; ?>

    <?php if ( empty( $atts['lastname'] ) ) : ?>

      <label class="pay-now"><?php _e( 'Last Name', 'wp-hitpay' ) ?> (Optional) </label>
      <input class="wp-hitpay-form-input-text" id="wp-hitpay-last-name" type="text" placeholder="<?php _e( 'Last Name', 'wp-hitpay' ) ?>" /><br>

    <?php endif; ?>

    <?php if ( empty( $atts['amount'] ) ) : ?>
      <label class="pay-now"><?php _e( 'Amount', 'wp-hitpay' ); ?> (<?php echo $atts['currency']?>)</label>
      <input class="wp-hitpay-form-input-text" id="wp-hitpay-amount" type="text" placeholder="<?php _e( 'Amount', 'wp-hitpay' ); ?>" required /><br>
    <?php else: ?> 
      <p class="display-amount"><?php echo $atts['amount']?> <?php echo $atts['currency']?></p>
    <?php endif; ?>
    <br>

    <input id="wp-hitpay-currency" type="hidden" value="<?php echo $atts['currency']?>" />

    <?php wp_nonce_field( 'wp_hitpay-nonce', 'wp_hitpay_sec_code' ); ?>
    <button value="submit" class='wp-hitpay-pay-now-button btn-primary' href='#'><?php _e( $btn_text, 'wp-hitpay' ) ?></button>
  </form>
</div>
