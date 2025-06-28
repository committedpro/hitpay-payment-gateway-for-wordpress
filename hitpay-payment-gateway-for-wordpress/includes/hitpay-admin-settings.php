<?php
  /**
   * Adming Settings Page Class
   */

  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }

  if ( ! class_exists( 'WP_Hitpay_Admin_Settings' ) ) {

    /**
    * Admin Settings class
    */
    class WP_Hitpay_Admin_Settings {

      /**
       * Class instance
       * @var $instance
       */
      public static $instance = null;

      /**
       * Admin options array
       *
       * @var array
       */
      protected $options;

      /**
       * Class constructor
       */
      private function __construct() {

        
        add_action( 'admin_menu', array( $this, 'wp_hitpay_add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'wp_hitpay_register_settings' ) );
        $this->init_settings();

      }

      /**
       * Registers admin setting
       *
       * @return void
       */
      public function wp_hitpay_register_settings() {

        register_setting( 'wp-hitpay-settings-group', 'wp_hitpay_options' );

      }

      private function init_settings() {

        if ( false == get_option( 'wp_hitpay_options' ) ) {
          update_option( 'wp_hitpay_options', array() );
        }

      }

      /**
       * Fetches admin option settings from the db
       *
       * @param  string $setting The option to fetch
       *
       * @return mixed           The value of the option fetched
       */
      public function get_option_value( $attr ) {

        $options = get_option( 'wp_hitpay_options' );

        if ( array_key_exists($attr, $options) ) {

          return $options[$attr];

        }

        return '';
      }

      /**
       * Checks if it is active
       *
       * @return boolean
       */
      public function is_active() {

        $options = get_option( 'wp_hitpay_options' );

        if ( false == $options ) return false;

        return array_key_exists( 'active', $options ) && ($options['active']=='yes');

      }

      /**
       * Checks if api key has been set
       *
       * @return boolean
       */
      public function is_api_key_present() {

        $options = get_option( 'wp_hitpay_options' );

        if ( false == $options ) return false;

        return array_key_exists( 'api_key', $options ) && ! empty( $options['api_key'] );

      }

      /**
       * Get the instance of the class
       *
       * @return object   An instance of this class
       */
      public static function get_instance() {

        if ( null == self::$instance ) {

          self::$instance = new self;

        }

        return self::$instance;

      }

      /**
       * Add admin menu
       * @return void
       */
      public function wp_hitpay_add_admin_menu() {

        add_menu_page(
          __( 'HitPay Payment Gateway Settings', 'wp-hitpay' ),
          'HitPay Payment Gateway',
          'manage_options',
          'hitpay-payment-gateway-for-wordpress',
          array( $this, 'wp_hitpay_admin_setting_page' ),
          HITPAY_WP_DIR_URL . 'assets/images/menu_icon.png',
          58
        );

        add_submenu_page(
          'hitpay-payment-gateway-for-wordpress',
          __( 'HitPay Payment Gateway Settings', 'wp-hitpay' ),
          __( 'Settings', 'wp-hitpay' ),
          'manage_options',
          'hitpay-payment-gateway-for-wordpress',
          array( $this, 'wp_hitpay_admin_setting_page' )
        );

      }

      /**
       * Admin page content
       * @return void
       */
      public function wp_hitpay_admin_setting_page() {

        include_once( HITPAY_WP_DIR_PATH . 'views/admin-settings-page.php' );

      }
    }

  }

?>
