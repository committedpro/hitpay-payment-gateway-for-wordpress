<?php
  /**
   * Shortcode Class
   */

  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }

  if ( ! class_exists( 'WP_Hitpay_Shortcode' ) ) {

    class WP_Hitpay_Shortcode {

      /**
       * Class instance variable
       *
       * @var $instance
       */
      protected static $instance = null;

      function __construct() {
        add_shortcode( 'hitpay-paynow-button', array( $this, 'pay_button_shortcode' ) );
      }

      /**
       * Get the instance of this class
       *
       * @return object the single instance of this class
       */
      public static function get_instance() {

        if ( null == self::$instance ) {
          self::$instance = new self;
        }

        return self::$instance;

      }

      /**
       * Generates Pay Now button from shortcode
       *
       * @param  array $attr Array of attributes from the shortcode
       *
       * @return string      Pay Now button html content
       */
      public function pay_button_shortcode( $attr, $content="" ) {

        global $admin_settings;

        if ( ! $admin_settings->is_active() ) return;

        if ( ! $admin_settings->is_api_key_present() ) return;

        $btn_text = empty( $content ) ? $this->pay_button_text() : $content;
        $email = $this->use_current_user_email( $attr ) ? wp_get_current_user()->user_email : '';
        if (!empty($this->get_logo_url($attr))) {
          $attr['logo'] = $this->get_logo_url($attr);
        }

        $atts = shortcode_atts( array(
          'pageid'    => get_the_ID(),
          'amount'    => '',
          'email'     => $email,
          'currency'  => $admin_settings->get_option_value('currency')
        ), $attr );

        ob_start();
        $this->render_payment_form( $atts, $btn_text );
        $form = ob_get_contents();
        ob_end_clean();

        return $form;

      }

      public function render_payment_form( $atts, $btn_text ) {        

        $data_attr = '';
        foreach ($atts as $att_key => $att_value) {
          $data_attr .= ' data-' . $att_key . '="' . $att_value . '"';
        }

        include( HITPAY_WP_DIR_PATH . 'views/pay-now-form.php' );

      }

      /**
       * Get pay now button text
       *
       * @return string Button text
       */
      private function pay_button_text() {
        global $admin_settings;

        $text = $admin_settings->get_option_value( 'btn_text' );
        if ( empty( $text ) ) {
          $text = 'PAY NOW';
        }

        return $text;

      }
      

      /**
       * Checks if the loggedIn user email should be used
       *
       * @param  array $attr attributes from shortcode
       *
       * @return boolean
       */
      private function use_current_user_email( $attr ) {

        return isset( $attr['use_current_user_email'] ) && $attr['use_current_user_email'] === 'yes';

      }

      private function get_logo_url($attr) {

        global $admin_settings;

        $logo = $admin_settings->get_option_value( 'modal_logo' );
        if ( ! empty( $attr['logo'] ) ) {
          $logo = strpos( $attr['logo'], 'http' ) != false ? $attr['logo'] : wp_get_attachment_url( $attr['logo'] );
        }

        return $logo;

      }

    }

  }
?>
