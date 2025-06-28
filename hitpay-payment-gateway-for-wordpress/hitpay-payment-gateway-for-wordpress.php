<?php
/*
Plugin Name: HitPay Payment Gateway for Wordpress
Description: HitPay Payment Gateway Plugin allows HitPay merchants to accept PayNow QR, Cards, Apple Pay, Google Pay, WeChatPay, AliPay and GrabPay Payments. You will need a HitPay account, contact support@hitpay.zendesk.com.
Version: 1.0.0
Requires at least: 4.0
Tested up to: 6.8.1
Requires PHP: 5.5
Author: <a href="https://www.hitpayapp.com>HitPay Payment Solutions Pte Ltd</a>   
Author URI: https://www.hitpayapp.com
License: MIT
*/

  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }

  if ( ! defined( 'HITPAY_WP_PLUGIN_FILE' ) ) {
    define( 'HITPAY_WP_PLUGIN_FILE', __FILE__ );
  }

  // Plugin folder path
  if ( ! defined( 'HITPAY_WP_DIR_PATH' ) ) {
    define( 'HITPAY_WP_DIR_PATH', plugin_dir_path( __FILE__ ) );
  }

  //Plugin folder path
  if ( ! defined( 'HITPAY_WP_DIR_URL' ) ) {
    define( 'HITPAY_WP_DIR_URL', plugin_dir_url( __FILE__ ) );
  }

   if ( ! defined( 'HITPAY_WP_VERSION' ) ) {
    define( 'HITPAY_WP_VERSION', '1.0.0' );
  }

  require_once( HITPAY_WP_DIR_PATH . 'includes/wp-hitpay.php' );

  global $wp_hitpay;

  $wp_hitpay = WP_HitPay::get_instance();

?>
