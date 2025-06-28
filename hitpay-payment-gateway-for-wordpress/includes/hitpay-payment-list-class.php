<?php
  /**
   * Hitpay Payment List
   */
  if ( ! defined( 'ABSPATH' ) ) { exit; }

  // require_once( ABSPATH . 'wp-admin/includes/screen.php');
  require_once( ABSPATH . 'wp-admin/includes/template.php');
  require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php');
  if( ! class_exists( 'Hitpay_WP_List_Table' ) ) {
    require_once( HITPAY_WP_DIR_PATH . 'includes/wp-classes/class-wp-list-table.php' );
  }

  if ( ! class_exists( 'WP_Hitpay_Payment_List' ) ) {

    /**
     * Payment List Class to add list payments made
     * via the payment buttons
     */
    class WP_Hitpay_Payment_List extends Hitpay_WP_List_Table {

      /**
       * Class Instance
       * @var null
       */
      protected static $instance = null;

      /**
       * Class construct
       */
      public function __construct() {

        parent::__construct( array(
          'singular' => __( 'Payment List', 'wp-hitpay' ),
          'plural'   => __( 'Payment Lists', 'wp-hitpay' ),
          'ajax'     => false
        ) );

        add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );
        add_action( 'init', array( $this, 'add_payment_list_post_type' ) );
        add_action( 'admin_menu', array( $this, 'add_to_menu' ) );

      }

      /**
       * The text to display when no payment is made
       *
       * @return void
       *
       */
      public function no_items() {
        _e( 'No payments have been made yet.', 'wp-hitpay' );
      }

      private function renderPaymentListID($val) {
        return '#'.$val;
      }

      /**
       * Method for name column
       *
       * @param array $item an array of DB data
       *
       * @return string
       */
      public function column_tx_ref( $item ) {

        $title = '<strong>' . $this->renderPaymentListID($item->ID);
        $title .= ' <u></u><span style="color:darkblue">'.get_post_meta( $item->ID, 'HitPay_payment_request_id', true ) . '</span></strong>';

        $actions = array(
          'edit' => sprintf( '<a href="%s">View</a>', get_edit_post_link( absint( $item->ID ) ) ),
          //'delete' => sprintf( '<a href="%s">Delete</a>', get_delete_post_link( absint( $item->ID ) ) )
        );

        return $title . $this->row_actions( $actions );
      }

      public function column_customer( $item ) {
        $title = get_post_meta( $item->ID, '_wp_hitpay_payment_email', true );

        $firstname = get_post_meta( $item->ID, '_wp_hitpay_payment_firstname', true );
        $lastname = get_post_meta( $item->ID, '_wp_hitpay_payment_lastname', true );

        if (!empty($firstname) || !empty($lastname)) {
          $title .= ' (';
          if (!empty($firstname)) {
            $title .= $firstname;
            if (!empty($lastname)) {
              $title .= ' '.$lastname;
            }
          } else  {
            $title .= $lastname;
          }
          $title .= ')';
        }

        return $title;
      }

      public function column_amount( $item ) {
        $amount = get_post_meta( $item->ID, '_wp_hitpay_payment_amount', true );
        if ($amount > 0) {
          $amount = number_format( $amount, 2 );
          $amount .= ' '.get_post_meta( $item->ID, '_wp_hitpay_payment_currency', true );
        }
        return $amount;
      }

      public function column_status( $item ) {
        $status = get_post_meta( $item->ID, '_wp_hitpay_payment_status', true );
        return ucwords($status);
      }

      /**
       * Renders a column when no column specific method exists.
       *
       * @param array $item
       * @param string $column_name
       *
       * @return mixed
       */
      public function column_default( $item, $column_name ) {

        switch ( $column_name ) {
          case 'customer':
          case 'fullname':
          case 'status':
          case 'date':
            return $item->post_date;
          default:
            return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }

      }

      /**
       *  Associative array of columns
       *
       * @return array
       */
      function get_columns() {

        global $admin_settings;

        $columns = array(
          'cb'      => '<input type="checkbox" />',
          'tx_ref'  => __('Transaction Reference', 'wp-hitpay' ),
          'customer' => __('Customer', 'wp-hitpay' ),
          'amount'  => __( 'Total', 'wp-hitpay' ),
          'status'  => __( 'Status', 'wp-hitpay' ),
          'date'    => __( 'Date', 'wp-hitpay' ),
        );

        return $columns;

      }

      function get_sortable_columns() {
          $sortable_columns = array(
            'tx_ref'  => array('tx_ref',false),
            'customer' => array('customer',false),
            'amount'   => array('amount',false),
            'status'   => array('status',false),
            'date'   => array('date',false)
          );
          return $sortable_columns;
      }

      function get_bulk_actions() {
        $actions = array(
          'delete'    => 'Delete'
        );
        return $actions;
      }

      /**
       * Render the bulk edit checkbox
       *
       * @param array $item
       *
       * @return string
       */
      public function column_cb( $item ) {

        return sprintf(
          '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->ID
        );

      }

      /**
       * Handles data query and filter, sorting, and pagination.
       */
      public function prepare_items() {

        // $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        // $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'payments_per_page' );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( array(
          'total_items' => $total_items,
          'per_page'    => $per_page
        ) );


        $this->items = self::get_payments( $per_page, $current_page );

      }

      public function set_screen( $status, $option, $value ) {

        return $value;

      }


      public function add_to_menu() {

        $hook = add_submenu_page(
          'hitpay-payment-gateway-for-wordpress',
          __( 'Hitpay Transaction List', 'wp-hitpay' ),
          __( 'Transactions', 'wp-hitpay' ),
          'manage_options',
          'wp_hitpay_payment_list',
          array( $this, 'payment_list_table')
        );

        // add_action( "load-$hook", array( $this, 'screen_option' ) );

      }

      public function payment_list_table() {

        require_once( HITPAY_WP_DIR_PATH . 'views/payment-list-table.php' );

      }

      /**
       * Fetches the payments
       *
       * @param  integer $post_per_page No of posts to show
       * @param  integer $page_number   The current page number
       *
       * @return mixed                  The list of all the payment records
       *
       */
      public static function get_payments( $post_per_page = 20, $page_number = 1 ) {

        $args = array(
          'posts_per_page'   => $post_per_page,
          'offset'           => ( $page_number - 1 ) * $post_per_page,
          'orderby'          => ! empty( $_REQUEST['orderby'] ) ? $_REQUEST['orderby']  : 'date',
          'order'            => ! empty( $_REQUEST['order'] )   ? $_REQUEST['order']    : 'DESC',
          'post_type'        => 'payment_list',
          'post_status'      => 'publish',
          'suppress_filters' => true,
          'meta_query' => array(
            array(
              'key'   => '_wp_hitpay_payment_status',
              'value' => 'pending_payment',
              'compare' => '!=',
            )
          ),
        );

        $payment_list = get_posts( $args );

        return $payment_list;

      }

      /**
       * Deletes a payment
       *
       * @param  int $payment_id The id of the payment to delete
       *
       * @return void
       *
       */
      public static function delete_payment( $payment_id ) {

        wp_delete_post( $payment_id );

      }

      /**
       * Gets the total payments made through hitpay
       *
       * @return int The total number of payments
       *
       */
      public static function record_count() {

        $total_records = wp_count_posts( 'payment_list' );

        return $total_records->publish;

      }

      /**
       * Add post types for payment lists
       */
      public function add_payment_list_post_type() {

        $args = array(
          'label'               => __( 'Payment Lists', 'wp-hitpay' ),
          'description'         => __( 'Hitpay payment lists', 'wp-hitpay' ),
          'supports'            => array( 'title', 'author', 'custom-fields', ),
          'hierarchical'        => false,
          'public'              => false,
          'show_ui'             => true,
          'show_in_menu'        => false,
          'show_in_nav_menus'   => false,
          'show_in_admin_bar'   => false,
          'exclude_from_search' => true,
          'capability_type'     => 'post',
        );

        register_post_type( 'payment_list', $args );

      }

      /**
       * Returns the singleton instance of this class
       *
       * @return object - the instance of the class
       */
      public static function get_instance() {

        if ( self::$instance == null ) {

          self::$instance = new self;

        }

        return self::$instance;

      }

    }

  }
?>
