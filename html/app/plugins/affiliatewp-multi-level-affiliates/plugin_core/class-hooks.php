<?php

class AffiliateWP_MLA_Hooks extends AffiliateWP_MLA_Common {

  public $purchaser_user_id = '';
  public $purchaser_affiliate_id = '';
  public $purchaser_parent_affiliate_id = '';

  public function __construct() {

    parent::__construct();

    // get order details and set some conditionals such as purchasers email
    // Woocommerce
    add_filter('woocommerce_checkout_update_order_meta', array($this, 'get_order_details_woo'), 1);

    // EDD
    add_action('edd_insert_payment', array($this, 'get_order_details_edd'), 1, 2);

    // Admin affiliate processes

    // Fires immediately after an affiliate has been added to the database.
    // add_action( 'affwp_insert_affiliate', array( $this, 'process_insert_affiliate' ), 3, 1 );
    // Replaced with the following two. Can probably use this single hook if its in the functions file

    // Now in the main class with the delete affiliate function
    // Fires after affiliate registration form
    // add_action( 'affwp_register_user', array( $this, 'process_insert_affiliate' ), 1, 1 );

    // Now in the main class with the delete affiliate function
    // Fires immediately after a new user has been auto-registered as an affiliate
    // add_action( 'affwp_auto_register_user', array( $this, 'process_auto_register_affiliate' ), 1, 1 );

    // Referring affiliate/parent setting or priorities
    // Set a referred status even if no tracked referer exists (if logged in with parent)
    // This disables the checkout referrals selection form
    add_filter('affwp_was_referred', array($this, 'process_was_referred'), 11, 2);

    // Set the referring affiliate to the parent (if logged in with parent).
    // Has priority over checkout referrals add-on which uses a priority of 10 on the same hook
    // Also has a higher priority tha Lifetime add-on

    add_filter('affwp_get_referring_affiliate_id', array($this, 'process_set_referring_affiliate_id'), 999999, 3);

    // The 4 step MLA generation process
    // 1. Save the order total
    add_filter('affwp_calc_referral_amount', array($this, 'process_save_order_total'), 11, 5);
    // 2. Generate the matrix data and return the direct referral amount (optional)
    // Link orphan affiliates to the referrer. Also Handles late tracking techniques like coupons
    add_filter('affwp_insert_pending_referral', array($this, 'process_direct_referral'));
    // 3. Process the indirect referrals after the direct referral has been generated
    add_action('affwp_insert_referral', array($this, 'process_indirect_referrals'));
    // 4. Complete (mark as unpaid) referrals when the direct referral is completed
    add_action('affwp_complete_referral', array($this, 'process_complete_referrals'), 10, 3);

    // The 4 step MLA generation process for Recurring Referrals add-on
    if ( $this->plugin_setting('recurring_referrals_enable') == 1 ) :
      // 1. Save the payment total
      add_filter('affwp_recurring_calc_referral_amount', array($this, 'process_save_order_total_recurring'), 10, 3);
      // 2. Generate the matrix data and return the direct referral amount
      add_filter('affwp_insert_pending_recurring_referral', array($this, 'process_direct_referral_recurring'), 10, 2);
      // 3. Process the indirect referrals after the direct referral has been generated
      // Uses the same hook/function as the standard process (affwp_insert_referral)
      // 4. Complete (mark as unpaid) referrals when the direct referral is completed
      add_action('affwp_complete_recurring_referral', array($this, 'process_complete_referrals_recurring'), 10, 3);
    endif;


    // For rejected referrals etc. To do
    //add_action( 'affwp_post_update_referral', array( $this, 'process_status_changes1' ) ); // $data 'status' => 'rejected'
    add_action('affwp_set_referral_status', array($this, 'process_status_changes'), 10, 3); // $referral_id, $new_status, $old_status

    // Add default settings for new affiliates groups. Is also called from MLA settings check
    add_action('affiliate_groups_update_settings', array($this, 'affiliate_groups_update_settings'));

    // Front end hooks
    //if( $this->plugin_setting( 'dashboard_tab_enable' ) == '1' ) :
    add_filter('affwp_affiliate_area_tabs', array($this, 'affiliate_area_tabs_register'));
    //add_action( 'affwp_affiliate_dashboard_tabs', array( $this, 'affiliate_area_mla_tab' ), 10, 2 ); // Not required since 2.1.7
    add_action('affwp_affiliate_dashboard_bottom', array($this, 'affiliate_area_mla_tab_content'));
    //endif;

    // Output the MLA data on the parent referral (edit referral screen)
    add_action('affwp_edit_referral_bottom', array($this, 'affwp_edit_referral_bottom'));

    // Admin report tabs
    add_filter('affwp_reports_tabs', array($this, 'affwp_reports_tabs'));

    // Tabs content
    //add_action( 'affwp_reports_tab_mla_orders', array( $this, 'affwp_reports_tab_mla_content' ) );
    //add_action( 'affwp_reports_tab_mla_reports', array( $this, 'affwp_reports_tab_mla_content' ) );
    add_action('affwp_reports_tab_mla_charts', array($this, 'affwp_reports_tab_mla_content'));

    // Reports
    // Generate a report
    add_action('wp_loaded', array($this, 'generate_report'));

    // Regenerate Referrals Integration
    //add_action( 'affwp_rr_after_regenerate', array( $this, 'affwp_rr_after_regenerate' ), 10, 4 );

    // Scripts and styles
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

  }

  // Add the required scripts and styles
  public function enqueue_admin_scripts() {

    wp_enqueue_style('affwp-mla-styles', plugin_dir_url(__FILE__) . 'includes/css/affwp_mla.css');

    wp_enqueue_script('jquery');

    if ( affwp_is_admin_page() ) :
      wp_enqueue_script('affwp-mla-select2', plugin_dir_url(__FILE__) . 'includes/js/lib/select2/select2.min.js', array('jquery'), '3.5.2');
      wp_enqueue_script('affwp-mla-admin', plugin_dir_url(__FILE__) . 'includes/js/mla-admin.js', array('jquery', 'affwp-mla-select2'), '0.1.0');
      wp_enqueue_style('affwp-mla-select2', plugin_dir_url(__FILE__) . 'includes/js/lib/select2/select2.css', array(), '3.5.2');
    endif;
  }

  // Add the required scripts and styles - Admin
  public function enqueue_scripts() {

    wp_enqueue_script('jquery');

    wp_enqueue_style('affwp-mla-styles', plugin_dir_url(__FILE__) . 'includes/css/affwp_mla.css');

    // MLA settings page
    /*if( $this->is_mla_settings_page() ) :
        wp_enqueue_script( 'affwp-mla-jquery', '//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js' );
        wp_enqueue_script( 'affwp-mla-settings', plugin_dir_url(__FILE__) . 'includes/js/settings_page.js' );

        wp_enqueue_style( 'affwp-mla-css', plugin_dir_url(__FILE__) . 'includes/css/affwp_mla.css' );
    endif;*/

    /*if ( affwp_is_admin_page() ) :
        wp_enqueue_script( 'affwp-mla-jquery', '//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js' );

        wp_enqueue_script( 'affwp-mla-select2', plugin_dir_url(__FILE__) . 'includes/js/lib/select2/select2.min.js' );
        wp_enqueue_script( 'affwp-mla-admin', plugin_dir_url(__FILE__) . 'includes/js/admin.js' );

        wp_enqueue_style( 'affwp-mla-select2-css', plugin_dir_url(__FILE__) . 'includes/js/lib/select2/select2.css' );
    endif;*/

  }

  // Processing Actions

  // Fires immediately after a new user has been auto-registered as an affiliate. Complete. Tested
  /*public function process_auto_register_affiliate($affiliate_id) {

      $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
      $affiliate->set_parent_affiliate();

      // Registration emails hooks
      do_action( 'mla_after_registration', $affiliate_id );

  }*/

  // Woocommerce order conditionals that may be required to determine if an affiliate is ordering as a guest
  public function get_order_details_woo($order_id) {

    $user_id = '';

    $order = wc_get_order($order_id);
    //$user_id = $order->get_user_id();

    $email = $order->get_billing_email();

    if ( $email ) :
      $this->set_purchaser_email($email);
    endif;

  }

  public function get_order_details_edd($payment_id = 0, $payment_data = array()) {

    if ( $payment_id ) :

      // get customer email
      $email = edd_get_payment_user_email($payment_id);

      if ( $email ) :
        $this->set_purchaser_email($email);
      endif;

    endif;

  }

  public function set_purchaser_email($email) {

    $user = get_user_by('email', $email);

    if ( $user ) :

      $user_id = $user->ID;

    endif;

    if ( $user_id ) :

      $this->purchaser_user_id = $user_id;

    endif;

  }

  // Set was referred to TRUE if logged in and a parent exists
  public function process_was_referred($bool, $tracking_obj) {

    $user_id = get_current_user_id();

    // if not logged in, use the order's user ID if available
    if ( !$user_id && !empty($this->purchaser_user_id) ) :
      $user_id = $this->purchaser_user_id;
    endif;

    if ( !empty($user_id) ) :

      $user_affiliate_id = affwp_get_affiliate_id($user_id);
      if ( !empty($user_affiliate_id) ) :

        $affiliate = new AffiliateWP_MLA_Affiliate($user_affiliate_id);
        $parent_id = $affiliate->get_parent_affiliate_id($user_affiliate_id);

        if ( !empty($parent_id) ) :

          $bool = (bool)true;

        endif;

      endif;

    endif;

    return apply_filters('mla_was_referred', $bool, $user_affiliate_id, $parent_id);

  }

  // Set the referrer ID
  /*public function process_set_referring_affiliate_id( $affiliate_id = '', $reference = '', $context = '' ) {

      $user_id = get_current_user_id();

      if( !empty($user_id) ) :

          $user_affiliate_id = affwp_get_affiliate_id( $user_id );
          if( !empty($user_affiliate_id) ) :

              $affiliate = new AffiliateWP_MLA_Affiliate( $user_affiliate_id );
              $parent_id = $affiliate->get_parent_affiliate_id( $user_affiliate_id );

              if( !empty($parent_id) ) :

                  $affiliate_id = $parent_id;

              endif;

          endif;

      endif;

      return $affiliate_id;

  }*/

  // Set the referrer ID
  public function process_set_referring_affiliate_id($affiliate_id = '', $reference = '', $context = '') {

    $user_id = get_current_user_id();

    // if not logged in, use the order's user ID if available
    if ( !$user_id && !empty($this->purchaser_user_id) ) :
      $user_id = $this->purchaser_user_id;
    endif;

    if ( !empty($user_id) ) :

      $user_affiliate_id = affwp_get_affiliate_id($user_id);
      if ( !empty($user_affiliate_id) ) :

        $affiliate = new AffiliateWP_MLA_Affiliate($user_affiliate_id);
        // Just gets the default (global) matrix settings
        $matrix_aff_settings = $affiliate->get_affiliate_matrix_settings('', false);

        // Old, replaced with logic below
        $parent_id = $affiliate->get_parent_affiliate_id($user_affiliate_id);

        if ( $this->matrix_setting('level_one_affiliate', $matrix_aff_settings['matrix_setting_id']) == 'parent' ) :

          $parent_id = $affiliate->get_parent_affiliate_id($user_affiliate_id);

        elseif ( $this->matrix_setting('level_one_affiliate', $matrix_aff_settings['matrix_setting_id']) == 'purchaser' ):

          $parent_id = $user_affiliate_id;

          // Allow own referrals
          add_filter('affwp_is_customer_email_affiliate_email', array($this, 'affwp_is_customer_email_affiliate_email'));
          add_filter('affwp_tracking_is_valid_affiliate', array($this, 'affwp_tracking_is_valid_affiliate'));


        endif;

        if ( !empty($parent_id) ) :

          $affiliate_id = $parent_id;

        endif;

      endif;

    endif;

    // Level 1
    //$this->matrix_affiliate_id = $affiliate_id;

    return $affiliate_id;

  }

  // Allow own referrals flags when level 1 is the purchaser
  public function affwp_is_customer_email_affiliate_email(){
    return false;
  }
  public function affwp_tracking_is_valid_affiliate() {
    return true;
  }

  // Save the order total. Complete. Tested
  public function process_save_order_total($referral_amount, $affiliate_id = '', $amount, $reference = '', $product_id = '') {

    if ( (isset($this->process_products) && !in_array($product_id, $this->process_products)) || !isset($this->process_products) || empty($product_id) ) :

      $this->order_total = (isset($this->order_total)) ? $this->order_total += $amount : $amount;

    endif;

    if ( isset($this->process_products) && !empty($product_id) ) {

      array_push($this->process_products, $product_id);

    } elseif ( !isset($this->process_products) && !empty($product_id) ) {

      $this->process_products = array($product_id);

    }

    set_transient('mla_referral_order_total', $this->order_total);

    // Return the original referral amount
    return $referral_amount;

  }

  // Filter the direct referral amount, generate the referrals array and save the matrix data in the custom column. Complete. Tested
  public function process_direct_referral($args) {

    // Set parent here for existing affiliates who don't have a parent
    // Useful for coupon tracking method where the affiliate was registered prior to the coupon code being recognized by AffiliateWP
    $existing_user_id = get_current_user_id();
    if ( !empty($existing_user_id) ) :

      $existing_affiliate_id = affwp_get_affiliate_id($existing_user_id);
      if ( !empty($existing_affiliate_id) && !empty($args['affiliate_id']) ) :

        $existing_parent_id = mla_get_parent_id($existing_affiliate_id);
        if ( empty($existing_parent_id) ) :

          mla_set_parent_id($existing_affiliate_id, $args['affiliate_id']);
          mla_set_direct_id($existing_affiliate_id, $args['affiliate_id']);

        endif;

      endif;

    endif;

    //endif;

    // Recurring Referrals - re-set the level 1 affiliate ID in case the parent has changed since the initial order
    if ( isset($args['parent_id']) ) :
      $parent_referral_id = $args['parent_id'];
      //$parent_referral_object = affwp_get_referral($parent_referral_id);
      //$customer_id = $parent_referral_object->customer_id;
      //$customer_object = affwp_get_customer($customer_id);
      //$user_id = $customer_object->user_id;
      //$affiliate_id = affwp_get_affiliate_id($user_id);
      $user_id = $this->get_purchaser_from_parent_referral($parent_referral_id);

      if ( !empty($user_id) ) :

        $affiliate_id = affwp_get_affiliate_id($user_id);

        $affiliate = new AffiliateWP_MLA_Affiliate($user_id);
        $matrix_aff_settings = $affiliate->get_affiliate_matrix_settings('', false);

        if ( $this->matrix_setting('level_one_affiliate', $matrix_aff_settings['matrix_setting_id']) == 'parent' ) :

          $parent_id = $affiliate->get_parent_affiliate_id($affiliate_id);

        elseif ( $this->matrix_setting('level_one_affiliate', $matrix_aff_settings['matrix_setting_id']) == 'purchaser' ):

          $parent_id = $affiliate_id;

        endif;

        $args['affiliate_id'] = $parent_id;

      endif;

    endif;

    // Get the matrix data
    $matrix = new AffiliateWP_MLA_Matrix($args);

    $affiliate_matrix_data = $matrix->prepare_matrix_data();

    if ( !empty($affiliate_matrix_data) ) {

      $matrix_data = array('mla' => $affiliate_matrix_data);

      if ( !empty($args['custom']) ) :
        $passed_custom = maybe_unserialize($args['custom']); //
      endif;

      // custom data already exists
      //if ( $args['custom'] ) {
      if ( !empty($passed_custom) && is_array($passed_custom) ) {

        //$args['custom'] = maybe_unserialize( $args['custom'] );
        //if( !is_array($args['custom']) ) $args['custom'] = array($args['custom']);
        $args['custom'] = array_merge($matrix_data, $passed_custom);

      } else {

        $args['custom'] = $matrix_data;

      }

      //$args['custom'] = maybe_serialize( $args['custom'] );

      $level_1_referral_total = $matrix_data['mla']['referrals']['1']['referral_total'];
      //if( !empty($level_1_referral_total) ) $args['amount'] = $level_1_referral_total;
      $args['amount'] = $level_1_referral_total;

      // needs re-thinking as the level 1 is required to store values for higher levels
      /*if ( $args['amount'] == 0 && affiliate_wp()->settings->get('ignore_zero_referrals') ) :

        $args['affiliate_id'] = '';

      endif;*/

    }

    if ( $args['custom']['mla']['args']['context'] == 'wpeasycart' ) :
      unset($args['custom']['mla']['args']['products']);
      unset($args['custom']['mla']['products']);
    endif;

    update_option('mla_debug_66', $args);

    return $args;

  }

  // Process indirect referrals. Complete. Tested
  public function process_indirect_referrals($referral_id) {

    $referral = new AffiliateWP_MLA_Referral($referral_id);
    $referral->process_indirect_referrals();

    // Remove the order total transient as no longer required
    delete_transient('mla_referral_order_total');

  }

  // Process - Complete referrals (mark as unpaid). Complete. Tested
  public function process_complete_referrals($referral_id, $referral, $reference) {

    $referral = new AffiliateWP_MLA_Referral($referral_id);
    $referral->process_complete_referrals();

  }


  //  Save the order total - Recurring Referrals
  public function process_save_order_total_recurring($referral_amount, $affiliate_id, $amount) {

    set_transient('mla_referral_order_total', $amount);

    return $referral_amount;

  }

  // Filter Direct Referral - Recurring Referrals
  public function process_direct_referral_recurring($args, $base_obj) {

    // Only $args is used by the following function

    $args = $this->process_direct_referral($args);

    return $args;

  }

  // Process - Complete referrals (mark as unpaid) - Recurring Referrals
  public function process_complete_referrals_recurring($referral_id) {

    $referral = new AffiliateWP_MLA_Referral($referral_id);
    $referral->process_complete_referrals();

  }

  // Referral Status change. Requires AffiliateWP 1.8. Complete. Tested
  public function process_status_changes($referral_id, $new_status, $old_status) {

    $referral = new AffiliateWP_MLA_Referral($referral_id);
    $referral->process_rejected_referral($new_status, $old_status);

  }


  // Process after updating an affiliate. Not required currently
  public function process_update_affiliate($data) {
  }

  // Add default Matrix settings for new affiliate group. Is also called from MLA settings check. Complete. Tested
  public function affiliate_groups_update_settings($active_groups) {

    $settings = new AffiliateWP_Multi_Level_Affiliates_Settings();
    $settings->set_affiliate_group_settings($active_groups);

  }

  //////////// Admin //////////

  // Add the tab
  public function affwp_reports_tabs($tabs) {

    //$tabs['mla_reports'] = __( 'MLA Reports', 'affiliate-wp' );
    $tabs['mla_charts'] = __('MLA Charts', 'affiliatewp-multi-level-affiliates');

    return $tabs;

  }

  public function affwp_edit_referral_bottom($referral_obj) {

    if ( apply_filters('mla_display_edit_referral_data', false) || (isset($_GET['mla_debug_output']) && $_GET['mla_debug_output'] == true) ) :

      $referral = new AffiliateWP_MLA_Referral($referral_obj->referral_id);
      //$referral->display_mla_data('referrals');
      $referral->display_mla_data();

    endif;

  }

  // Add the content
  public function affwp_reports_tab_mla_content() {

    $template_loader = new AffiliateWP_MLA_Template_Loader(array('sub_directory' => 'admin'));
    //$template_loader->set_template_data( $data );
    $tab = (!empty($_GET['tab'])) ? $_GET['tab'] : '';
    if ( $tab == 'mla_reports' ) {
      echo $template_loader->get_template_object('admin-reports');
    }
    if ( $tab == 'mla_charts' ) {
      echo $template_loader->get_template_object('admin-charts');
    }

  }

  ////////// Front-end ////////////

  // Register the dashboard tab
  public function affiliate_area_tabs_register($tabs) {

    $tab_1_title = $this->plugin_setting('dashboard_tab_title');
    $tab_1_title = (!empty($tab_1_title)) ? $tab_1_title : 'MLA Tab 1';

    $tab_2_title = $this->plugin_setting('dashboard_tab_2_title');
    $tab_2_title = (!empty($tab_2_title)) ? $tab_2_title : 'MLA Tab 2';

    $tab_3_title = $this->plugin_setting('dashboard_tab_3_title');
    $tab_3_title = (!empty($tab_3_title)) ? $tab_3_title : 'MLA Tab 3';

    $tab_headings = array(
      'tab1' => $tab_1_title,
      'tab2' => $tab_2_title,
      'tab3' => $tab_3_title,
    );

    $mla_tab_headings = apply_filters('mla_tab_headings', $tab_headings);

    $new_tabs = array();

    if ( $this->plugin_setting('dashboard_tab_enable') == '1' ) :
      $new_tabs['mla-tab1'] = $mla_tab_headings['tab1'];
    endif;
    if ( $this->plugin_setting('dashboard_tab_2_enable') == '1' ) :
      $new_tabs['mla-tab2'] = $mla_tab_headings['tab2'];
    endif;
    if ( $this->plugin_setting('dashboard_tab_3_enable') == '1' ) :
      $new_tabs['mla-tab3'] = $mla_tab_headings['tab3'];
    endif;

    return array_merge($tabs, $new_tabs);

  }

  // Add the dashboard tab link. Not required since AffiliateWP v2.1.7
  /*
  public function affiliate_area_mla_tab( $affiliate_id, $active_tab ) {

      $tab_1_title = $this->plugin_setting( 'dashboard_tab_title' );
      $tab_1_title = ( !empty($tab_1_title) ) ? $tab_1_title : 'MLA Tab 1' ;

      $tab_2_title = $this->plugin_setting( 'dashboard_tab_2_title' );
      $tab_2_title = ( !empty($tab_2_title) ) ? $tab_2_title : 'MLA Tab 2' ;

      $tab_3_title = $this->plugin_setting( 'dashboard_tab_3_title' );
      $tab_3_title = ( !empty($tab_3_title) ) ? $tab_3_title : 'MLA Tab 3' ;

      $tab_headings = array(
          'tab1'		=>	$tab_1_title,
          'tab2'		=>	$tab_2_title,
          'tab3'		=>	$tab_3_title,
      );

      $mla_tab_headings = apply_filters( 'mla_tab_headings', $tab_headings );

      ?>

      <?php if( $this->plugin_setting( 'dashboard_tab_enable' ) == '1' ) : ?>
      <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'mla-tab1' ? ' active' : ''; ?>">
      <a href="<?php echo esc_url( add_query_arg( 'tab', 'mla-tab1' ) ); ?>"><?php echo $mla_tab_headings['tab1'] ;?></a>
      </li>
      <?php endif; ?>

      <?php if( $this->plugin_setting( 'dashboard_tab_2_enable' ) == '1' ) : ?>
      <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'mla-tab2' ? ' active' : ''; ?>">
      <a href="<?php echo esc_url( add_query_arg( 'tab', 'mla-tab2' ) ); ?>"><?php echo $mla_tab_headings['tab2'] ;?></a>
      </li>
      <?php endif; ?>

      <?php if( $this->plugin_setting( 'dashboard_tab_3_enable' ) == '1' ) : ?>
      <li class="affwp-affiliate-dashboard-tab<?php echo $active_tab == 'mla-tab3' ? ' active' : ''; ?>">
      <a href="<?php echo esc_url( add_query_arg( 'tab', 'mla-tab3' ) ); ?>"><?php echo $mla_tab_headings['tab3'] ;?></a>
      </li>
      <?php endif; ?>

      <?php

  }
  */

  // Add the dashboard content
  public function affiliate_area_mla_tab_content($affiliate_id) {

    if ( isset($_GET['tab']) && $_GET['tab'] == 'mla-tab1' ) {
      $shortcodes = new AffiliateWP_MLA_Shortcodes();
      echo $shortcodes->mla_dashboard();
    }

    if ( isset($_GET['tab']) && $_GET['tab'] == 'mla-tab2' ) {
      $shortcodes = new AffiliateWP_MLA_Shortcodes();
      echo $shortcodes->mla_dashboard();
    }

    if ( isset($_GET['tab']) && $_GET['tab'] == 'mla-tab3' ) {
      $shortcodes = new AffiliateWP_MLA_Shortcodes();
      echo $shortcodes->mla_dashboard();
    }

  }

  // Generate a report
  public function generate_report() {

    if ( isset($_REQUEST['mla_report_action']) && !empty($_REQUEST['mla_report_action']) && isset($_REQUEST['report']) && !empty($_REQUEST['report']) ) :

      $vars['report'] = $_REQUEST['report'];

      /*if( isset($_REQUEST['mla_report_action']) ) :
          $vars['output'] = $_REQUEST['mla_report_action'];
      endif;

      if( isset($_REQUEST['mla_stream_type']) ) :
          $vars['stream_type'] = $_REQUEST['mla_stream_type'];
      endif;*/

      $reports = new AffiliateWP_MLA_Reports();
      $reports->generate_report($vars);

    endif;

  }

  // Regenerate Referrals Integration
  /*public function affwp_rr_after_regenerate( $reference, $context, $referral ='', $amount ='', $order_total='' ) {

  $referral = affiliate_wp()->referrals->get_by( 'reference', $reference, $context );
  $referral_id = $referral->referral_id;

  if( $context == 'woocommerce' ) :
      $referral = new AffiliateWP_MLA_Referral($referral->referral_id);
      $referral->regenerate_referrals_woocommerce($reference);
  endif;

  }*/


}  // End of class

?>