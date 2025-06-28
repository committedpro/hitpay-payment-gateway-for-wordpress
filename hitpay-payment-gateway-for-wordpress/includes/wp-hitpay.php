<?php

  if ( ! defined( 'ABSPATH' ) ) {
    exit;
  }

  use HitPay\Client;
  use HitPay\Request\CreatePayment;
  use HitPay\Response\PaymentStatus;

  if ( ! class_exists( 'WP_HitPay' ) ) {

    /**
     * Main Plugin Class
     */
    class WP_HitPay {

      /**
       * Instance variable
       * @var $instance
       */
      protected static $instance = null;

      protected $salt;
      protected $api_key;
      protected $mode;

      protected $checkout_type;
      protected $debug;

      /**
       * Class constructor
       */
      function __construct() {

        $this->_include_files();
        $this->_init();

        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        add_action( 'wp_ajax_process_payment', array( $this, 'process_payment' ) );
        add_action( 'wp_ajax_nopriv_process_payment', array( $this, 'process_payment' ) );

        add_action('init', array( $this, 'check_ipn_response' ));

        add_action('wp_enqueue_scripts',  array( $this, 'hitpay_load_front_assets') );

        add_action('admin_enqueue_scripts',  array( $this, 'hitpay_load_admin_assets') );

        add_filter( 'the_content', array( $this, 'custom_page') );
        add_filter( 'the_title', array( $this, 'custom_title'), 10, 2 );

        add_action( 'edit_form_after_title', array( $this, 'callback_edit_form_after_title') );

        add_action( 'wp_ajax_process_refund', array( $this, 'process_refund' ) );
        add_action( 'wp_ajax_process_refund', array( $this, 'process_refund' ) );

        add_action( 'save_post', array( $this, 'save_post' ));

      }

      function save_post( $post_id ) {
        if (isset($_REQUEST['_wp_hitpay_payment_status'])) {
          $new_status = sanitize_text_field(wp_unslash($_REQUEST['_wp_hitpay_payment_status']));
          $this->update_status($post_id, $new_status);
        }
      }

      public function getOrderStatuses()
      {
          $statuses = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'failed' => 'Failed',
            'on-hold' => 'On-Hold',
          ];
          return $statuses;
      }

      public function callback_edit_form_after_title( $post ) {
        if ($post->post_type == 'payment_list') {
          $order_id = $post->ID;

          $payment_request_id = get_post_meta($order_id, 'HitPay_payment_request_id', true );
          $payment_method = '';
          if (!empty($payment_request_id)) {
            $payment_method = $this->getOrderMetaData($order_id, 'HitPay_payment_method', true );
            $fees = $this->getOrderMetaData($order_id, 'HitPay_fees', true );
            $fees_currency = $this->getOrderMetaData($order_id, 'HitPay_fees_currency', true );
            if (empty($payment_method) || empty($fees) || empty($fees_currency)) {
                try {
                    $hitpay_client = new Client(
                        $this->api_key,
                        $this->getMode()
                    );

                    $paymentStatus = $hitpay_client->getPaymentStatus($payment_request_id);
                    if ($paymentStatus) {
                        $payments = $paymentStatus->payments;
                        if (isset($payments[0])) {
                            $payment = $payments[0];
                            $payment_method = $payment->payment_type;
                            $this->updateMetaData($order_id,'HitPay_payment_method', $payment_method);
                            $fees = $payment->fees;
                            $this->updateMetaData($order_id,'HitPay_fees', $fees);
                            $fees_currency = $payment->fees_currency;
                            $this->updateMetaData($order_id,'HitPay_fees_currency', $fees_currency);
                        }
                    }
                } catch (\Exception $e) {
                    $payment_method = $e->getMessage();
                }
            }
          }

          $statuses = $this->getOrderStatuses();

          include( HITPAY_WP_DIR_PATH . 'views/admin-payment-content.php' );
        }
      }

      public function custom_title($title, $id) {
        global $wp_query;
        if ($this->is_wp_hitpay_return_page() && !is_null( $wp_query ) && !is_admin() && is_main_query() && in_the_loop()) {
            $status = sanitize_text_field(wp_unslash($_GET['status']));
            if ($status == 'canceled') {
              $title = 'Hitpay Payment Canceled';
            } else if ($status == 'completed') {
              $title = "Thank you for the payment";
            } 
            
            remove_filter( 'the_title', 'custom_title' );
        }
        return $title;
      }

      public function custom_page($content) {
          $original = $content;
          if ($this->is_wp_hitpay_return_page()) {
            $status = sanitize_text_field(wp_unslash($_GET['status']));
            if ($status == 'canceled') {
              $content = $this->canceled_content();
            } else if ($status == 'completed') {
              $content = $this->thankyou_content();
            } 
          }
          return $content;
      }

      public function canceled_content() {
        $order_id = (int)sanitize_text_field(wp_unslash($_GET['wp_hitpay_order_id']));

        $reference = 'NONEXIST';
        if (isset($_GET['reference'])) {
            $reference = sanitize_text_field(wp_unslash($_GET['reference']));
        }

        ob_start();
        include( HITPAY_WP_DIR_PATH . 'views/payment-canceled.php' );
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
      }

      public function thankyou_content() {
        $order_id = (int)sanitize_text_field(wp_unslash($_GET['wp_hitpay_order_id']));
        
        $reference = 'NONEXIST';
        if (isset($_GET['reference'])) {
            $reference = sanitize_text_field(wp_unslash($_GET['reference']));
        }

        $status = sanitize_text_field(wp_unslash($_GET['status']));

        $amount = get_post_meta($order_id, '_wp_hitpay_payment_amount', true);
        $currency = get_post_meta($order_id, '_wp_hitpay_payment_currency', true);
        $email = get_post_meta($order_id, '_wp_hitpay_payment_email', true);

        ob_start();
        include( HITPAY_WP_DIR_PATH . 'views/payment-thankyou.php' );
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
      }

      public function is_wp_hitpay_return_page() {
        $flag = false;
        if (isset($_GET['wp_hitpay_order_id']) && isset($_GET['status']) && isset($_GET['reference']) && isset($_GET['wp_hitpay_callback'])) {
          $flag = true;
        }
        return $flag;
      }

      public function hitpay_load_admin_assets()
      {
        wp_enqueue_style( 'wp-hitpay-admin-css', HITPAY_WP_DIR_URL.'/assets/css/admin.css', array(), HITPAY_WP_VERSION.'2', 'all' );
        wp_enqueue_script(
          'wp_hitpay_admin_js', 
          HITPAY_WP_DIR_URL.'/assets/js/admin.js', 
          array(), 
          HITPAY_WP_VERSION.'2', 
          array('in_footer'  => 'true',)
        );

        if (isset($_GET['post'])) {
          $post_id = (int)sanitize_text_field(wp_unslash($_GET['post']));

          $args = array(
            'cb_url'    => admin_url( 'admin-ajax.php' ),
            'post_id' => $post_id
          );

          wp_localize_script( 'wp_hitpay_admin_js', 'wp_hitpay_options', $args );
        }
      }

      public function hitpay_load_front_assets()
      {
          wp_enqueue_style( 'wp-hitpay-css', HITPAY_WP_DIR_URL.'/assets/css/hitpay.css', array(), HITPAY_WP_VERSION, 'all' );

          $dropin_js = 'https://sandbox.hit-pay.com/hitpay.js';
          if ($this->mode == 'yes') {
              $dropin_js = 'https://hit-pay.com/hitpay.js';
          }
          wp_enqueue_script(
            'wp_hitpay_remote_js', 
            $dropin_js, 
            array(), 
            HITPAY_WP_VERSION,
            array(
              'in_footer'  => 'true',
            )
          );

          wp_enqueue_script(
            'wp_hitpay_js', 
             HITPAY_WP_DIR_URL.'/assets/js/hitpay.js', 
             array(), 
            HITPAY_WP_VERSION, 
            array('in_footer'  => 'true',)
          );

          $args = array(
            'cb_url'    => admin_url( 'admin-ajax.php' ),
            'payment_canceled' => false
          );

          if (isset($_GET['cancelled'])) {
            $args['payment_canceled'] = true;
          }

          wp_localize_script( 'wp_hitpay_js', 'wp_hitpay_options', $args );
      }

      /**
       * Includes all required files
       *
       * @return void
       */
      private function _include_files()
      {
        require_once( HITPAY_WP_DIR_PATH . 'includes/hitpay-shortcode.php' );
        require_once( HITPAY_WP_DIR_PATH . 'includes/hitpay-admin-settings.php' );
        require_once( HITPAY_WP_DIR_PATH . 'includes/hitpay-payment-list-class.php' );

        require_once HITPAY_WP_DIR_PATH . 'vendor/softbuild/hitpay-sdk/src/CurlEmulator.php';

        if (!class_exists('\HitPay\Client')) {
          require_once HITPAY_WP_DIR_PATH . 'vendor/autoload.php';
        }
      }

      /** 
       * Initialize all the included classe
       *
       * @return void
       */
      private function _init() {

        global $admin_settings;
        global $payment_list;

        new WP_Hitpay_Shortcode;

        $admin_settings = WP_Hitpay_Admin_Settings::get_instance();

        $this->mode = $admin_settings->get_option_value('mode');
        $this->api_key = $admin_settings->get_option_value('api_key');
        $this->salt = $admin_settings->get_option_value('salt');
        $this->debug = 'yes';//$admin_settings->get_option_value('debug');
        $this->checkout_type = 'dropin';//$admin_settings->get_option_value('checkout_type');

        $payment_list   = WP_Hitpay_Payment_List::get_instance();
      }

      /**
       * Adds admin settings page to the dashboard
       *
       * @return void
       */
      public function admin_notices() {
        $page = '';
        if (isset($_GET['page'])) {
          $page = $_GET['page'];
        }

        if ($page != 'hitpay-payment-gateway-for-wordpress') {
          $options = get_option( 'wp_hitpay_options' );
          $no_api_key = (bool) (! array_key_exists('api_key', $options ) || empty( $options['api_key'] ));
          $no_salt = (bool) (! array_key_exists('salt', $options ) || empty( $options['salt'] ));

          if ( $no_api_key || $no_salt ) {
            echo '<div class="updated"><p>';
            echo  __( 'HitPay Payment Gateway for WordPress plugin is installed.  ', 'wp-hitpay' );
            echo "<a href=" . esc_url( add_query_arg( 'page', $this->plugin_name, admin_url( 'admin.php' ) ) ) . " class='button-primary'>" . __( 'Enter your HitPay API credentials to start accepting payments', 'wp-hitpay' ) . "</a>";
            echo '</p></div>';
          }
        } else {
          if (isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated']) {
            echo '<div id="message" class="updated inline"><p><strong>Your settings have been saved.</strong></p></div>';
          }
        }
      }

      public function get_order_number() {
        $hitpay_reference = '';
        if (isset($_POST['hitpay_reference'])) {
            $hitpay_reference = sanitize_text_field(wp_unslash($_POST['hitpay_reference']));
        }
        
        if (empty($hitpay_reference)) {
          $hitpay_reference = self::gen_rand_string(10);
        }

        return $hitpay_reference;
      }

      public function getMode()
      {
          $mode = false;
          if ($this->mode == 'yes') {
              $mode = true;
          }
          return $mode;
      }

      public function getSiteName()
      {   global $blog_id;

          if (is_multisite()) {
              $path = get_blog_option($blog_id, 'blogname');
          } else{
            $path = get_option('blogname');
          }
          return $path;
      }

      public function updateMetaData($id, $key, $value)
      {
        if (!add_post_meta($id, $key, $value, true) ) {
          update_post_meta ($id, $key, $value);
        }
      }

      /**
       * Processes payment record information
       *
       * @return void
       */
      public function process_payment() {
        global $admin_settings;

        check_ajax_referer( 'wp_hitpay-nonce', 'wp_hitpay_sec_code' );

        $status = 'init';
        $response = [];

        $reference = $this->get_order_number();
        $payment_status = 'pending_payment';

        $amount = '';
        if (isset($_POST['amount'])) {
            $amount = sanitize_text_field(wp_unslash($_POST['amount']));
        }

        $customer_email = '';
        if (isset($_POST['customer_email'])) {
            $customer_email = sanitize_text_field(wp_unslash($_POST['customer_email']));
        }

        $customer_firstname = 'NoName';
        if (isset($_POST['customer_firstname'])) {
            $customer_firstname = sanitize_text_field(wp_unslash($_POST['customer_firstname']));
        }

        $customer_lastname = 'NoName';
        if (isset($_POST['customer_lastname'])) {
            $customer_lastname = sanitize_text_field(wp_unslash($_POST['customer_lastname']));
        }

        $currency = '';
        if (isset($_POST['currency'])) {
            $currency = sanitize_text_field(wp_unslash($_POST['currency']));
        }

        $pageid = '';
        if (isset($_POST['pageid'])) {
            $pageid = sanitize_text_field(wp_unslash($_POST['pageid']));
        }

        $amount = (float)wp_strip_all_tags(trim($amount));
        $amountValue = number_format($amount, 2, '.', '');

        $args   =  array(
          'post_type'   => 'payment_list',
          'post_status' => 'publish',
          'post_title'  => $reference,
        );

        $payment_record_id = wp_insert_post( $args, true );

        if ( ! is_wp_error( $payment_record_id )) {
          $post_meta = array(
            '_wp_hitpay_payment_amount'   => $amountValue,
            '_wp_hitpay_payment_firstname' => $customer_firstname,
            '_wp_hitpay_payment_lastname' => $customer_lastname,
            '_wp_hitpay_payment_email' => $customer_email,
            '_wp_hitpay_payment_status'   => $payment_status,
            '_wp_hitpay_payment_reference'   => $reference,
            '_wp_hitpay_payment_currency'   => $currency,
            '_wp_hitpay_payment_pageid'   => $pageid,
          );
          $this->_add_post_meta( $payment_record_id, $post_meta );

          $order_id = $payment_record_id;

          try {
            $hitpay_client = new Client(
                $this->api_key,
                $this->getMode()
            );

            $wp_pay_page = '';
            if ($pageid > 0) {
              $form_url = get_permalink($pageid);
              $wp_pay_page = base64_encode($form_url);
            }

            $redirect_url = site_url().'/?wp_hitpay_callback=return&wp_hitpay_order_id='.$order_id.'&wp_pay_page='.$wp_pay_page;
            $webhook = site_url().'/?wp_hitpay_callback=webhook&wp_hitpay_order_id='.$order_id.'&wp_pay_page='.$wp_pay_page;

            $create_payment_request = new CreatePayment();
            $create_payment_request->setAmount($amountValue)
                ->setCurrency($currency)
                ->setReferenceNumber($order_id)
                ->setWebhook($webhook)
                ->setRedirectUrl($redirect_url)
                ->setChannel('api_woocomm');

            $create_payment_request->setName($customer_firstname . ' ' . $customer_lastname);
            $create_payment_request->setEmail($customer_email);

            $create_payment_request->setPurpose($this->getSiteName());

            $this->log('Create Payment Request:');
            $this->log('Payment_request_amount: '.$create_payment_request->getAmount());
            $this->log('Payment_request_currency: '.$create_payment_request->getCurrency());
            $this->log('Payment_request reference_number: '.$create_payment_request->getReferenceNumber());

            $result = $hitpay_client->createPayment($create_payment_request);

            $this->log('Create Payment Response:');
            $this->log('Create Payment_Request_id: '.$result->getId());
            $this->log('Create Payment_status: '.$result->getStatus());

            $this->updateMetaData($order_id, 'HitPay_payment_request_id', $result->getId());

            $hitpayDomain = 'sandbox.hit-pay.com';
            if ($this->mode == 'yes') {
                $hitpayDomain = 'hit-pay.com';
            }

            if ($result->getStatus() == 'pending') {
                $response = array(
                    'status' => 'success',
                    'redirect' => $result->getUrl(),
                    'domain' => $hitpayDomain,
                    'apiDomain' => $hitpayDomain,
                    'payment_request_id' => $result->getId(),
                    'redirect_url' => $redirect_url,
                    'checkout_type' => $this->checkout_type
                );
            } else {
                throw new Exception(sprintf('HitPay: received status is %s', $result->getStatus()));
             }
          } catch (\Exception $e) {
            $this->log('Create Payment Failed:');
            $log_message = $e->getMessage();
            $this->log($log_message);

            $status_message = __('HitPay payment request is failed. ', 'wp-hitpay');
            $status_message .= $log_message;

            $response =  array(
                'status' => 'failed',
                'message' => $status_message
            );
          }
        } else {
          $status_message = __('Payment request is failed. Unable to create payment record on the WordPress', 'wp-hitpay');
          $response =  array(
              'status' => 'failed',
              'message' => $status_message
          );
        }

        echo json_encode($response);
        die();
      }

      public function process_refund()
      {
        global $admin_settings;

        $status = 'init';
        $response = [];

        $amount = '';
        if (isset($_POST['amount'])) {
            $amount = sanitize_text_field(wp_unslash($_POST['amount']));
        }

        $order_id = '';
        if (isset($_POST['order_id'])) {
            $order_id = (int)sanitize_text_field(wp_unslash($_POST['order_id']));
        }

        $amount = (float)wp_strip_all_tags(trim($amount));
        $amountValue = number_format($amount, 2, '.', '');

        try {
            if ($order_id <= 0) {
                throw new Exception(__('ID is missing, contact admin.',  'wp-hitpay'));
            }

            $HitPay_transaction_id = $this->getOrderMetaData($order_id, 'HitPay_transaction_id', true );
            $HitPay_is_refunded = $this->getOrderMetaData($order_id, 'HitPay_is_refunded', true );
            if ($HitPay_is_refunded == 1) {
                throw new Exception(__('Only one refund allowed per transaction by HitPay Gateway.',  'wp-hitpay'));
            }

            $order_total_paid =  get_post_meta($order_id, '_wp_hitpay_payment_amount', true);

            if ($amountValue <=0 ) {
                throw new Exception(__('Refund amount shoule be greater than 0.',  'wp-hitpay'));
            }

            if ($amountValue > $order_total_paid) {
                throw new Exception(__('Refund amount shoule be less than or equal to paid total.',  'wp-hitpay'));
            }

            $hitpayClient = new Client(
                $this->api_key,
                $this->getMode()
            );

            $result = $hitpayClient->refund($HitPay_transaction_id, $amountValue);

            $this->updateMetaData($order_id, 'HitPay_is_refunded', 1);
            $this->updateMetaData($order_id, 'HitPay_refund_id', $result->getId());
            $this->updateMetaData($order_id, 'HitPay_refund_amount_refunded', $result->getAmountRefunded());
            $this->updateMetaData($order_id, 'HitPay_refund_created_at', $result->getCreatedAt());

            $message = __('Refund is successful. Refund Reference Id: ', 'wp-hitpay');
            $message .= $result->getId().', ';
            $message .= __('Payment Id: ', 'wp-hitpay');
            $message .= $HitPay_transaction_id.', ';
            $message .= __('Amount Refunded: ', 'wp-hitpay');
            $message .= $result->getAmountRefunded().', ';
            $message .= __('Payment Method: ', 'wp-hitpay');
            $message .= $result->getPaymentMethod().', ';
            $message .= __('Created At: ', 'wp-hitpay');
            $message .= $result->getCreatedAt();

            $total_refunded = $result->getAmountRefunded();
            $this->update_status($order_id, 'refunded', $message);

            $response = array(
                'status' => 'success',
                'message' => $message,
            );
        } catch (\Exception $e) {
          $response = array(
              'status' => 'failed',
              'message' => $e->getMessage(),
          );
        }
        echo json_encode($response);
        die();
      }

      public static function gen_rand_string( $len = 4 )
      {
        if ( version_compare( PHP_VERSION, '5.3.0' ) <= 0 ) {
            return substr( md5( rand() ), 0, $len );
        }
        return bin2hex( openssl_random_pseudo_bytes( $len/2 ) );
      }

      public function check_ipn_response()
      {
        if(isset($_GET['wp_hitpay_callback']) ) {
          $action = sanitize_text_field(wp_unslash($_GET['wp_hitpay_callback']));
          if ($action == 'webhook') {
            $this->web_hook_handler();
          } else if ($action == 'return') {
            $this->return_from_hitpay();
          }
        }
      }

      public function return_from_hitpay() {
        $order_id = (int)sanitize_text_field(wp_unslash($_GET['wp_hitpay_order_id']));
        if (isset($_GET['status'])) {
          $status = sanitize_text_field(wp_unslash($_GET['status']));

          $reference = 'NONEXIST';
          if (isset($_GET['reference'])) {
              $reference = sanitize_text_field(wp_unslash($_GET['reference']));
          }

          if ($status == 'canceled') {
            $return_url = false;
            if (isset($_GET['wp_pay_page'])) {
                $return_url = sanitize_text_field(wp_unslash($_GET['wp_pay_page']));
                if (!empty($return_url)) {
                  $return_url = base64_decode($return_url);
                }
            }

            $status_message = __('Payment cancelled by HitPay.', 'wp-hitpay').($reference ? ' Reference: '.$reference:'');
            
            $this->update_status($order_id, 'cancelled', $status_message);
            $this->updateMetaData($order_id, 'HitPay_reference', $reference);

            $hitpaynonce = wp_create_nonce( 'wp-hitpay-payment-fields' );

            if ($return_url) {
              wp_redirect( 
                add_query_arg(
                  array(
                    'cancelled'=>'true',
                    'hitpaynonce'=>$hitpaynonce,
                  ),
                  $return_url
                )
              );
              exit;
            }
          } elseif ($status == 'completed') {

          }
        }
      }

      public function web_hook_handler()
      {
        $this->log('Webhook Triggered');
    
        if (isset($_GET['wphitpaynonce']) && !wp_verify_nonce(sanitize_key($_GET['wphitpaynonce']), 'wp-hitpay-web_hook_handler')) {
          exit;
        }

        if (!isset($_GET['wp_hitpay_order_id']) || !isset($_POST['hmac'])) {
            $this->log('order_id + hmac check failed');
            exit;
        }

        $post_payment_id = '';
        if (isset($_POST['payment_id'])) {
            $post_payment_id = sanitize_text_field(wp_unslash($_POST['payment_id']));
        }
        $post_status = '';
        if (isset($_POST['status'])) {
            $post_status = sanitize_text_field(wp_unslash($_POST['status']));
        }
        $post_reference_number = '';
        if (isset($_POST['reference_number'])) {
            $post_reference_number = sanitize_text_field(wp_unslash($_POST['reference_number']));
        }
        
        $this->log('Payment_id: '.$post_payment_id);
        $this->log('Payment_status: '.$post_status);
        $this->log('Payment_reference_number: '.$post_reference_number);

        $order_id = (int)sanitize_text_field(wp_unslash($_GET['wp_hitpay_order_id']));
        
        $order = $this->getOrder($order_id);

        if ($order_id > 0) {
            $HitPay_webhook_triggered = (int)$this->getOrderMetaData($order_id, 'HitPay_webhook_triggered', true);
            if ($HitPay_webhook_triggered == 1) {
                exit;
            }
        }

        $this->updateMetaData($order_id, 'HitPay_webhook_triggered', 1);

        try {
          $data = $_POST;
          unset($data['hmac']);

          $salt = $this->salt;

          $post_hmac = '';
          if (isset($_POST['hmac'])) {
              $post_hmac = sanitize_text_field(wp_unslash($_POST['hmac']));
          }

          $post_amount = '';
          if (isset($_POST['amount'])) {
              $post_amount = sanitize_text_field(wp_unslash($_POST['amount']));
          }

          $post_currency = '';
          if (isset($_POST['currency'])) {
              $post_currency = sanitize_text_field(wp_unslash($_POST['currency']));
          }

          $post_payment_request_id = '';
          if (isset($_POST['payment_request_id'])) {
              $post_payment_request_id = sanitize_text_field(wp_unslash($_POST['payment_request_id']));
          }

          if (Client::generateSignatureArray($salt, $data) == $post_hmac) {
            $this->log('hmac check passed');

            $HitPay_payment_request_id = $this->getOrderMetaData($order_id, 'HitPay_payment_request_id', true );

            if (!$HitPay_payment_request_id || empty($HitPay_payment_request_id)) {
                $this->log('saved payment not valid');
            }

            $HitPay_is_paid = $this->getOrderMetaData($order_id, 'HitPay_is_paid', true );

            if (!$HitPay_is_paid) {
              $status = sanitize_text_field(wp_unslash($_POST['status']));

              if ($status == 'completed') {
                  $payment_id = sanitize_text_field(wp_unslash($_POST['payment_id']));
                  $payment_request_id = sanitize_text_field(wp_unslash($_POST['payment_request_id']));
                  $hitpay_currency = sanitize_text_field(wp_unslash($_POST['currency']));
                  $hitpay_amount = sanitize_text_field(wp_unslash($_POST['amount']));

                  $status_message = __('Payment is successful. Transaction Id: ', 'wp-hitpay').$payment_id;
                  $this->update_status($order_id, 'completed', $status_message);

                  $this->updateMetaData($order_id,'HitPay_transaction_id', $payment_id);
                  $this->updateMetaData($order_id,'HitPay_payment_request_id', $payment_request_id);
                  $this->updateMetaData($order_id,'HitPay_is_paid', 1);
                  $this->updateMetaData($order_id,'HitPay_currency', $hitpay_currency);
                  $this->updateMetaData($order_id,'HitPay_amount', $hitpay_amount);
                  $this->updateMetaData($order_id,'HitPay_WHS', $status);

              } elseif ($status == 'failed') {
                  $payment_id = sanitize_text_field(wp_unslash($_POST['payment_id']));
                  $hitpay_currency = sanitize_text_field(wp_unslash($_POST['currency']));
                  $hitpay_amount = sanitize_text_field(wp_unslash($_POST['amount']));

                  $status_message = __('Payment Failed. Transaction Id: ', 'wp-hitpay-').$payment_id;
                  $this->update_status($order_id, 'failed', $status_message);

                  $this->updateMetaData($order_id,'HitPay_transaction_id', $payment_id);
                  $this->updateMetaData($order_id,'HitPay_is_paid', 0);
                  $this->updateMetaData($order_id,'HitPay_currency', $hitpay_currency);
                  $this->updateMetaData($order_id,'HitPay_amount', $hitpay_amount);
                  $this->updateMetaData($order_id,'HitPay_WHS', $status);

              } elseif ($status == 'pending') {
                  $payment_id = sanitize_text_field(wp_unslash($_POST['payment_id']));
                  $hitpay_currency = sanitize_text_field(wp_unslash($_POST['currency']));
                  $hitpay_amount = sanitize_text_field(wp_unslash($_POST['amount']));
      
                  $status_message = __('Payment is pending. Transaction Id: ', 'wp-hitpay').$payment_id;
                  $this->update_status($order_id, 'pending', $status_message);

                  $this->updateMetaData($order_id,'HitPay_transaction_id', $payment_id);
                  $this->updateMetaData($order_id,'HitPay_is_paid', 0);
                  $this->updateMetaData($order_id,'HitPay_currency', $hitpay_currency);
                  $this->updateMetaData($order_id,'HitPay_amount', $hitpay_amount);
                  $this->updateMetaData($order_id,'HitPay_WHS', $status);

              } else {
                  $payment_id = sanitize_text_field(wp_unslash($_POST['payment_id']));
                  $hitpay_currency = sanitize_text_field(wp_unslash($_POST['currency']));
                  $hitpay_amount = sanitize_text_field(wp_unslash($_POST['amount']));
      
                  $status_message = __('Payment returned unknown status. Transaction Id: ', 'wp-hitpay').$payment_id;
                  $this->update_status($order_id, 'failed', $status_message);

                  $this->updateMetaData($order_id,'HitPay_transaction_id', $payment_id);
                  $this->updateMetaData($order_id,'HitPay_is_paid', 0);
                  $this->updateMetaData($order_id,'HitPay_currency', $hitpay_currency);
                  $this->updateMetaData($order_id,'HitPay_amount', $hitpay_amount);
                  $this->updateMetaData($order_id,'HitPay_WHS', $status);

              }
            }
          } else {
              throw new \Exception('HitPay: hmac is not the same like generated');
          }
        } catch (\Exception $e) {
            $this->log('Webhook Catch');
            $this->log('Exception:'.$e->getMessage());
            $this->update_status($order_id, 'failed', 'Error :'.$e->getMessage());
            $this->updateMetaData($order_id,'HitPay_WHS', 'failed');
        }
        exit;
      }

      /**
       * Adds metadata to payment list post type
       *
       * @param [int]   $post_id  The ID of the post to add metadata to
       * @param [array] $data     Collection of the data to be added to the post
       */
      private function _add_post_meta( $post_id, $data )
      {
        foreach ($data as $meta_key => $meta_value) {
          update_post_meta( $post_id, $meta_key, $meta_value );
        }
      }

      public function add_payment_note($id,$value) {
        $key = 'wp_hitpay_payment_notes';
        add_post_meta($id,$key,$value);
      }

      public function option_exists($option_name) 
      {
        $value = get_option($option_name);
        return $value;
      }

      public function getOrder($order_id) {
          $order = get_post( $order_id );
          return $order;
      }
      
      public function getOrderMetaData($order_id, $key, $single) {
        return get_post_meta( $order_id, $key, $single );
      }

      public function getStatusLabel($status) {
        return ucwords($status);
      }

      public function update_status($order_id, $new_status, $message=false) {
        $key = '_wp_hitpay_payment_status';
        $status = get_post_meta($order_id, $key, true );
        if ($status != $new_status) {
          update_post_meta( $order_id, $key, $new_status );
          $status_change_message = 'Payment status changed from '.$this->getStatusLabel($status).' to '.$this->getStatusLabel($new_status).'.';
          $this->add_payment_note($order_id,$status_change_message);
        }
        if ($message) {
          $this->add_payment_note($order_id,$message);
        }
      }

      public function log($content)
      {
          $debug = $this->debug;
          if ($debug == 'yes') {
            if (!$this->option_exists("wp_hitpay_logfile_prefix")) {
              $logfile_prefix = md5(uniqid(wp_rand(), true));
              update_option('wp_hitpay_logfile_prefix', $logfile_prefix);
            } else {
              $logfile_prefix = get_option('wp_hitpay_logfile_prefix');
              if (empty($logfile_prefix)) {
                $logfile_prefix = md5(uniqid(wp_rand(), true));
                update_option('wp_hitpay_logfile_prefix', $logfile_prefix);
              }
            }
        
            $filename = $logfile_prefix.'_wp_hitpay_debug.log';
            $file = ABSPATH .'wp-content/uploads/wc-logs/'.$filename;
          
            try {
            /*
            if (!defined( 'FS_CHMOD_FILE' ) ) {
              define('FS_CHMOD_FILE', 0644);
            }				
            
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            
            $filesystem = new WP_Filesystem_Direct( false );
            $filesystem->put_contents("\n".gmdate("Y-m-d H:i:s").": ".print_r($content, true));
            */
            
            // @codingStandardsIgnoreStart
            /*
            We tried to use WP_Filesystem methods, look at the above commented out code block.
            But this put_contents method just writing the code not appending to the file.
            So we have only the last written content in the file.
            Because in the below method fopen initiated with 'wb' mode instead of 'a' or 'a+', otherwise this core method must be modified to able to pass the file open mode from the caller.
            public function put_contents( $file, $contents, $mode = false ) {
            $fp = @fopen( $file, 'wb' );
            */
            $fp = fopen($file, 'a+');
            if ($fp) {
                fwrite($fp, "\n".gmdate("Y-m-d H:i:s").": ".print_r($content, true));
                fclose($fp);
            }
            // @codingStandardsIgnoreEnd
            } catch (\Exception $e) {}
          }
      }

      /**
       * Gets the instance of this class
       *
       * @return object the single instance of this class
       */
      public static function get_instance()
      {
        if ( null == self::$instance ) {
          self::$instance = new self;
        }
        return self::$instance;
      }
    }
  }
