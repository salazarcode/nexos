<?php

class AffiliateWP_MLA_Shortcodes extends AffiliateWP_MLA_Common {

  public function __construct() {

    parent::__construct();

    // Affiliate Area Tab Content
    add_shortcode('mla_dashboard_tab', array($this, 'mla_dashboard'));

    // Affiliate Rates
    add_shortcode('mla_dashboard_rates', array($this, 'mla_dashboard_rates'));

    // Affiliate Earnings
    add_shortcode('mla_dashboard_earnings', array($this, 'mla_dashboard_earnings'));

    // Network Statistics
    add_shortcode('mla_dashboard_statistics', array($this, 'mla_dashboard_statistics'));

    // Network Chart
    add_shortcode('mla_dashboard_chart', array($this, 'mla_dashboard_chart'));

    // Affiliate Rates
    add_shortcode('mla_dashboard_reports', array($this, 'mla_dashboard_reports'));

    // Debug output
    add_shortcode('mla_output_debug_data', array('AffiliateWP_MLA_Common', 'mla_output_debug_data'));

    // Affiliate Rates
    add_shortcode('mla_affiliate_parent_details', array($this, 'mla_affiliate_parent_details'));

    // Conditional Content - TL
    add_shortcode('mla_team_leader_content', array($this, 'mla_team_leader_content'));

    // Conditional Content - STL
    add_shortcode('mla_super_team_leader_content', array($this, 'mla_super_team_leader_content'));

  }

  // The affiliate area tab content
  public function mla_dashboard($atts = array()) {

    // Shortcode tab
    if ( isset($atts['tab']) && !empty($atts['tab']) ) {
      $_GET['tab'] = 'mla-tab' . $atts['tab'];
    }

    // default tabs used in the AffiliateWP dashboard
    if ( (isset($_GET['tab']) && $_GET['tab'] == 'mla-tab1') ) {
      $dashboard_features = $this->plugin_setting('dashboard_features');
    }
    if ( isset($_GET['tab']) && $_GET['tab'] == 'mla-tab2' ) {
      $dashboard_features = $this->plugin_setting('dashboard_2_features');
    }
    if ( isset($_GET['tab']) && $_GET['tab'] == 'mla-tab3' ) {
      $dashboard_features = $this->plugin_setting('dashboard_3_features');
    }

    $dashboard_features = ($dashboard_features != NULL && is_array($dashboard_features)) ? $dashboard_features : array();

    $default_atts = array(
      'affiliate_id' => affwp_get_affiliate_id(get_current_user_id()),
      'display_rates' => (array_key_exists('rates', $dashboard_features)) ? (bool)TRUE : (bool)FALSE, /* TRUE, FALSE */
      'display_earnings' => (array_key_exists('earnings', $dashboard_features)) ? (bool)TRUE : (bool)FALSE, /* TRUE, FALSE */
      'display_network_statistics' => (array_key_exists('network_statistics', $dashboard_features)) ? (bool)TRUE : (bool)FALSE, /* TRUE, FALSE */
      'display_network_chart' => (array_key_exists('network_chart', $dashboard_features)) ? (bool)TRUE : (bool)FALSE, /* TRUE, FALSE */
      'display_reports' => (array_key_exists('reports', $dashboard_features)) ? (bool)TRUE : (bool)FALSE, /* TRUE, FALSE */
    );

    $data = array_merge($default_atts, $atts);

    return $this->get_shortcode_template('dashboard-tab', $data);

  }

  // The front end affiliates area
  public function mla_dashboard_rates($atts = '') {

    if ( empty($atts) ) $atts = array();

    $default_atts = array('affiliate_id' => affwp_get_affiliate_id(get_current_user_id()));

    $data = array_merge($atts, $default_atts);

    if ( !empty($data['affiliate_id']) ) {

      return $this->get_shortcode_template('dashboard-affiliate-rates', $data);

    } else {

      return '';
    }

  }

  // The front end affiliates area
  public function mla_dashboard_earnings($atts = '') {

    if ( empty($atts) ) $atts = array();

    $default_atts = array('affiliate_id' => affwp_get_affiliate_id(get_current_user_id()));

    $data = array_merge($default_atts, $atts);

    if ( !empty($data['affiliate_id']) ) {

      return $this->get_shortcode_template('dashboard-affiliate-earnings', $data);

    } else {

      return '';
    }

  }

  // The front end affiliates area
  public function mla_dashboard_statistics($atts = '') {

    if ( empty($atts) ) $atts = array();

    $default_atts = array('affiliate_id' => affwp_get_affiliate_id(get_current_user_id()));

    $data = array_merge($default_atts, $atts);

    if ( !empty($data['affiliate_id']) ) {

      return $this->get_shortcode_template('dashboard-network-statistics', $data);

    } else {

      return '';
    }

  }

  // The front end affiliates area
  public function mla_dashboard_chart($atts = '') {

    if ( empty($atts) ) $atts = array();

    $default_atts = array(
      'affiliate_id' => affwp_get_affiliate_id(get_current_user_id()),
      //'chart_size' => 'medium', /* small, medium, large */
      //'show_preloader' => (bool) TRUE, /* TRUE, FALSE */
    );

    $data = array_merge($default_atts, $atts);

    if ( !empty($data['affiliate_id']) ) {

      return $this->get_shortcode_template('dashboard-charts', $data);

    } else {

      return '';
    }

  }

  // The front end affiliates area
  public function mla_dashboard_reports($atts = '') {

    if ( empty($atts) ) $atts = array();

    $default_atts = array('affiliate_id' => affwp_get_affiliate_id(get_current_user_id()));

    $data = array_merge($atts, $default_atts);


    if ( !empty($data['affiliate_id']) ) {

      return $this->get_shortcode_template('dashboard-affiliate-reports', $data);

    } else {

      return '';
    }

  }

  // The front end affiliates area
  /*public function mla_dashboard_reports_referrals( $atts = '' ) {

    if(empty($atts)) $atts = array();

    $default_atts = array( 'affiliate_id' => affwp_get_affiliate_id( get_current_user_id() ) );

      $data = array_merge( $atts, $default_atts );

    return $this->get_shortcode_template( 'dashboard-affiliate-reports-referrals', $data );

  }*/

  // Return a template as an object
  public function get_shortcode_template($template_part, $data) {

    $template_loader = new AffiliateWP_MLA_Template_Loader();
    $template_loader->set_template_data($data);

    return $template_loader->get_template_object($template_part);

  }

  // Affiliate Parent Details
  public function mla_affiliate_parent_details($atts = array()) {

    $default_user_id = get_current_user_id();
    $default_affiliate_id = affwp_get_affiliate_id($default_user_id);

    $atts = shortcode_atts(array(
      'affiliate_id' => $default_affiliate_id,
      'display' => 'default'
    ), $atts);

    $parent_id = '';
    if ( !empty($atts['affiliate_id']) ) :

      if ( !empty($atts['affiliate_id']) ) :

        $parent_id = mla_get_parent_id($atts['affiliate_id']);

      endif;

    endif;

    if ( !empty($parent_id) ) :

      $parent_user_id = affwp_get_affiliate_user_id($parent_id);

      $parent_affiliate_name = affwp_get_affiliate_name($parent_id);

      $parent_user_info = get_userdata($parent_user_id);
      $parent_username = $parent_user_info->user_login;
      $parent_first_name = $parent_user_info->first_name;
      $parent_last_name = $parent_user_info->last_name;
      $parent_email = $parent_user_info->user_email;
      $parent_display_name = $parent_user_info->display_name;

      switch ($atts['display']) {

        case "user_login":
          $output = $parent_username;
          break;

        case "first_name":
          $output = $parent_first_name;
          break;

        case "last_name":
          $output = $parent_last_name;
          break;

        case "user_email":
          $output = $parent_email;
          break;

        case "display_name":
          $output = $parent_display_name;
          break;

        default:
          $output = $parent_affiliate_name;

      }

      return apply_filters('mla_sc_parent_info', $output, $affiliate_id, $parent_user_info);

    endif;

    return '';

  }

  // Conditional content - Team Leader
  function mla_team_leader_content($atts = array(), $content = null) {

    $user_id = get_current_user_id();
    $affiliate_id = affwp_get_affiliate_id($user_id);

    if ( mla_is_affiliate_team_leader($affiliate_id) ) :

      return do_shortcode($content);

    endif;

    return '';
  }

  // Conditional content - Super Team Leader
  function mla_super_team_leader_content($atts = array(), $content = null) {

    $user_id = get_current_user_id();
    $affiliate_id = affwp_get_affiliate_id($user_id);

    if ( mla_is_affiliate_super_team_leader($affiliate_id) ) :

      return do_shortcode($content);

    endif;

    return '';
  }

}

?>