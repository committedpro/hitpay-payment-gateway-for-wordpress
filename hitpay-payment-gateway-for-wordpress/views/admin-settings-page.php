<?php

  if ( ! defined( 'ABSPATH' ) ) { exit; }

?>
<?php global $admin_settings; ?>

  <div class="wrap">
    <h1>HitPay Payment Gateway Settings</h1>
    <form id="wp-hitpay" action="options.php" method="post" enctype="multipart/form-data">
      <?php settings_fields( 'wp-hitpay-settings-group' ); ?>
      <?php do_settings_sections( 'wp-hitpay-settings-group' ); ?>
      <?php
      $currency = $admin_settings->get_option_value( 'currency' );
      if (!$currency) {
        $currency = 'SGD';
      }
      ?>
      <table class="form-table">
        <tbody>

        <!-- Enable -->
          <tr valign="top">
            <th scope="row">
              <label for="wp_hitpay_options[active]"><?php _e( 'Active', 'wp-hitpay' ); ?></label>
            </th>
            <td class="forminp forminp-checkbox">
              <fieldset>
                <?php $active = esc_attr( $admin_settings->get_option_value( 'active' ) ); ?>
                <label>
                  <input type="checkbox" name="wp_hitpay_options[active]" <?php checked( $active, 'yes' ); ?> value="yes" />
                  <?php _e( '(Enable/Disable)', 'wp-hitpay' ); ?>
                </label>
              </fieldset>
            </td>
          </tr>

        <!-- Pay Button Text -->
          <tr valign="top">
            <th scope="row">
              <label for="wp_hitpay_options[btn_text]"><?php _e( 'Pay Button Text', 'wp-hitpay' ); ?></label>
            </th>
            <td class="forminp forminp-text">
              <input class="regular-text code" type="text" name="wp_hitpay_options[btn_text]" value="<?php echo esc_attr( $admin_settings->get_option_value( 'btn_text' ) ); ?>" />
              <p class="description">(Optional) default: PAY NOW</p>
            </td>
          </tr>

          <!-- Switch to Live -->
          <tr valign="top">
            <th scope="row">
              <label for="wp_hitpay_options[mode]"><?php _e( 'Live Mode', 'wp-hitpay' ); ?></label>
            </th>
            <td class="forminp forminp-checkbox">
              <fieldset>
                <?php $mode = esc_attr( $admin_settings->get_option_value( 'mode' ) ); ?>
                <label>
                  <input type="checkbox" name="wp_hitpay_options[mode]" <?php checked( $mode, 'yes' ); ?> value="yes" />
                  <?php _e( '(Enable payments in live mode)', 'wp-hitpay' ); ?>
                </label>
              </fieldset>
            </td>
          </tr>

          <!-- Api Key -->
          <tr valign="top">
            <th scope="row">
              <label for="wp_hitpay_options[api_key]"><?php _e( 'API Key', 'wp-hitpay' ); ?></label>
            </th>
            <td class="forminp forminp-text">
              <input class="regular-text code" type="text" name="wp_hitpay_options[api_key]" value="<?php echo esc_attr( $admin_settings->get_option_value( 'api_key' ) ); ?>" />
              <p class="description">(Copy/Paste values from HitPay Dashboard under Payment Gateway > API Keys)</p>
            </td>
          </tr>
          <!-- Salt -->
          <tr valign="top">
            <th scope="row">
              <label for="wp_hitpay_options[salt]"><?php _e( 'Salt', 'wp-hitpay' ); ?></label>
            </th>
            <td class="forminp forminp-text">
              <input class="regular-text code" type="text" name="wp_hitpay_options[salt]" value="<?php echo esc_attr( $admin_settings->get_option_value( 'salt' ) ); ?>" />
              <p class="description">(Copy/Paste values from HitPay Dashboard under Payment Gateway > API Keys)</p>
            </td>
          </tr>

          <!-- Currency Selector -->
          <tr valign="top">
            <th scope="row">
              <label for="wp_hitpay_options[currency]"><?php _e( 'Currency', 'wp-hitpay' ); ?></label>
            </th>
            <td class="forminp forminp-text">
              <?php include_once( HITPAY_WP_DIR_PATH . 'views/currency_selector.php' );?>
              <p class="description">(Default Currency - This currency will be assumed if not provided in the shortcode)</p>
            </td>
          </tr>

        </tbody>
      </table>
      <?php submit_button(); ?>
    </form>

  </div>
