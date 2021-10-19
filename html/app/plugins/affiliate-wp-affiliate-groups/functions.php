<?php

///////// Group Functions /////////


// Get all active Affiliate Groups
function get_active_affiliate_groups() {

  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-plugin-base.php';
  return AffiliateWP_Affiliate_Groups_Base::get_all_active_affiliate_groups();

}

// Get a group's settings
function get_affiliate_group_setting($setting_key, $group_id) {

  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-settings.php';
  return AffiliateWP_Affiliate_Groups_Settings::get_affiliate_groups_setting($setting_key, $group_id);
}

// Update a group's setting
/*function update_affiliate_group_setting($setting_key, $value, $group_id) {
	
	update_affiliate_groups_setting($setting_key, $value, $group_id);
}*/

///////// Affiliate Funtions /////////

/*
$affiliate = affwp_get_affiliate( absint( $affiliate_id ) );
$user_info = get_userdata( $affiliate->user_id );
$rate_type = ! empty( $affiliate->rate_type ) ? $affiliate->rate_type : '';
$rate      = ! empty( $affiliate->rate ) ? $affiliate->rate : '';
$email     = ! empty( $affiliate->payment_email ) ? $affiliate->payment_email : '';
*/

// Get an Affiliate's active groups. Returns array( 'group_id' => 'Group name' )
function get_affiliates_active_groups($affiliate_id) {

  return AffiliateWP_Affiliate_Groups_Base::affiliates_active_groups($affiliate_id);

}

// Get the affiliate's group (single / first in the array)
function get_affiliate_group($affiliate_id, $return = '') {

  $groups = get_affiliates_active_groups($affiliate_id);

  if ( !empty($groups) ) :

    if ( empty($return) ) :

      return $groups;

    elseif ( $return == 'id' ):

      return key($groups);

    elseif ( $return == 'name' ):

      return $groups[key($groups)];

    endif;

  endif;

}

// Reset an affiliate's groups
//$new_groups = array( 'affiliate_group_id_1', 'affiliate_group_id_1' );
function reset_affiliate_groups($affiliate_id, $new_groups) {

  //$user_id = affwp_get_affiliate_user_id($affiliate_id);

  $current_groups = get_affiliates_active_groups($affiliate_id);

  foreach ($current_groups as $key => $name) :

    remove_affiliate_from_group($affiliate_id, $key);

  endforeach;

  if ( !empty($new_groups) ) :

    foreach ($new_groups as $group_id) :

      add_affiliate_to_group($affiliate_id, $group_id);

    endforeach;

  endif;

}

// Remove an affiliate from a group
function remove_affiliate_from_group($affiliate_id, $group_id) {

  $user_id = affwp_get_affiliate_user_id($affiliate_id);

  AffiliateWP_Affiliate_Groups_Base::remove_affiliate_group_static($user_id, $group_id);

}

// Add an affiliate to a group
function add_affiliate_to_group($affiliate_id, $group_id) {

  $user_id = affwp_get_affiliate_user_id($affiliate_id);

  AffiliateWP_Affiliate_Groups_Base::add_affiliate_group_static($user_id, $group_id);

}

// Insert affiliate from admin
add_action('affwp_post_insert_affiliate', 'affwp_g_process_new_affiliate_form', 10);
function affwp_g_process_new_affiliate_form($add) {

  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-plugin-base.php';
  $g_base = new AffiliateWP_Affiliate_Groups_Base();
  $g_base->process_new_affiliate_form($add);

}

// Update affiliate from admin
add_action('affwp_post_update_affiliate', 'affwp_g_process_edit_affiliate_form', 10, 0);
function affwp_g_process_edit_affiliate_form() {

  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-plugin-base.php';
  $g_base = new AffiliateWP_Affiliate_Groups_Base();
  $g_base->process_edit_affiliate_form();

}

// Auto grouping. 
// Fires immediately after an affiliate has been added to the database. 
// All registration methods covered here as they all call the function where this hook resides
// This preceeds any other after registration related hooks in other Click Studio add-ons
add_action('affwp_insert_affiliate', 'affwp_g_affiliate_auto_grouping_registration', 10, 1);
function affwp_g_affiliate_auto_grouping_registration($affiliate_id) {

  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-plugin-base.php';
  $g_base = new AffiliateWP_Affiliate_Groups_Base();
  $g_base->affiliate_auto_grouping_registration($affiliate_id);

  do_action('affwp_g_after_auto_grouping', $affiliate_id);

}

///// Shortcodes /////

// Display the affiliate's group (a single group)
add_shortcode('affwp_group_name', 'sc_affwp_group_name');
function sc_affwp_group_name($atts = '') {

  if ( empty($atts) ) $atts = array();

  $user_id = get_current_user_id();
  if ( $user_id ) :

    $user_id;

    $affiliate_id = affwp_get_affiliate_id($user_id);

    if ( $affiliate_id ) :

      $group_name = get_affiliate_group($affiliate_id, 'name');

      if ( $group_name ) :

        return $group_name;

      endif;

    endif;

  endif;

  return '';

}
?>