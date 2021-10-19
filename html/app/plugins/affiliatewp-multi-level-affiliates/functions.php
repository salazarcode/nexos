<?php
////////// Affiliate Management Functions //////////

function affiliate_management_includes() {
  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-settings.php';
  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-common.php';
  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-affiliate.php';
  require_once plugin_dir_path(__FILE__) . 'plugin_core/class-team-leader.php';
}

function mla_get_existing_affiliate_id() {

  $existing_user_id = get_current_user_id();
  if ( !empty($existing_user_id) ) :

    $existing_affiliate_id = affwp_get_affiliate_id($existing_user_id);
    if ( !empty($existing_affiliate_id) ) :

      return $existing_affiliate_id;

    endif;

  endif;

  return false;

}

// Get affiliate parent
function mla_get_direct_id($affiliate_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
  return $affiliate->get_direct_affiliate_id();

}

// Get affiliate parent
function mla_get_parent_id($affiliate_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
  return $affiliate->get_parent_affiliate_id();

}

// Set affiliate parent
function mla_set_parent_id($affiliate_id = '', $parent_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  if ( !empty($affiliate_id) ) :

    $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
    return $affiliate->set_parent_affiliate($affiliate_id, $parent_id);

  endif;

  return '';

}

// Set affiliate's direct
function mla_set_direct_id($affiliate_id = '', $parent_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  if ( !empty($affiliate_id) ) :

    $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
    return $affiliate->set_direct_affiliate($affiliate_id, $parent_id);

  endif;

  return '';

}

// Get an affiliates children from specific level. Returns obj results from affiliates_meta table
function get_sub_affiliates_on_level($affiliate_id = '', $level) {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  if ( !empty($affiliate_id) && !empty($level) ) :

    $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
    return $affiliate->get_level_affiliates($affiliate_id, $level);

  endif;

  return '';

}

// Is affilate a team leader
function mla_is_affiliate_team_leader($affiliate_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  if ( !empty($affiliate_id) ) :

    $tl = new AffiliateWP_MLA_Team_Leader($affiliate_id);
    return $tl->is_a_team_leader($affiliate_id);

  endif;

  return (bool)false;

}

// Get the affiliates Team Leader
function mla_get_team_leader($affiliate_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  if ( !empty($affiliate_id) ) :

    $tl = new AffiliateWP_MLA_Team_Leader($affiliate_id);
    return $tl->get_affiliates_team_leader($affiliate_id);

  endif;

  return '';

}

// Is affilate a super team leader
function mla_is_affiliate_super_team_leader($affiliate_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  if ( !empty($affiliate_id) ) :

    $tl = new AffiliateWP_MLA_Team_Leader($affiliate_id);
    return $tl->is_a_super_team_leader($affiliate_id);

  endif;

  return (bool)false;

}

// Get the affiliates Super Team Leader
function mla_get_steam_leader($affiliate_id = '') {

  affiliate_management_includes();

  $affiliate_id = (!empty($affiliate_id)) ? $affiliate_id : mla_get_existing_affiliate_id();

  if ( !empty($affiliate_id) ) :

    $tl = new AffiliateWP_MLA_Team_Leader($affiliate_id);
    return $tl->get_affiliates_super_team_leader($affiliate_id);

  endif;

  return '';

}

//////////// Hooks for affiliate registration, update, delete. 
//////////// These are not functions intended to be used in custom development

// Set the parent and Direct ID
// Fires immediately after registration of an affiliate and/or user from affiliate registration form
add_action('affwp_register_user', 'process_register_affiliate', 1, 1);
function process_register_affiliate($affiliate_id) {

  affiliate_management_includes();

  $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
  $affiliate->set_parent_affiliate($affiliate_id);
  $affiliate->set_direct_affiliate($affiliate_id);

  // Registration emails hooks
  do_action('mla_after_registration', $affiliate_id);

}

// Set the parent and Direct ID
// Fires immediately after a new user has been auto-registered as an affiliate
add_action('affwp_auto_register_user', 'process_auto_register_affiliate', 1, 1);
function process_auto_register_affiliate($affiliate_id) {

  affiliate_management_includes();

  $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
  $affiliate->set_parent_affiliate($affiliate_id);
  $affiliate->set_direct_affiliate($affiliate_id);

  // Registration emails hooks
  do_action('mla_after_registration', $affiliate_id);

}

// Set the parent ID 
// Fires immediately after an affiliate has been added from the admin area
add_action('affwp_post_insert_affiliate', 'process_parent_id_new_affiliate_form', 11);
function process_parent_id_new_affiliate_form($add) {

  affiliate_management_includes();

  if ( current_user_can('edit_users') ) :

    if ( !empty($add) ) :

      global $_REQUEST;

      if ( isset($_REQUEST['parent_affiliate_id']) ) : // This is here to check if the form has been submitted. A better option exists

        $user_id = affwp_get_affiliate_user_id($add);
        $affiliate_id = affwp_get_affiliate_id($user_id);

        if ( !empty($affiliate_id) ) :
          $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
        endif;

        // add the direct affiliate
        $direct_id = $_REQUEST['direct_affiliate_id'];
        if ( !empty($affiliate_id) && !empty($direct_id) ) :
          //$affiliate->update_direct_affiliate($affiliate_id, $direct_id);
          $affiliate->set_direct_affiliate($affiliate_id, $direct_id);
        endif;

        //$affiliate_id = $add;
        $parent_id = $_REQUEST['parent_affiliate_id'];
        if ( !empty($affiliate_id) && !empty($parent_id) ) :
          //$affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
          $affiliate->set_parent_affiliate('', $parent_id);
        endif;

        // Team Leader
        $tl_affiliate = (!empty($_REQUEST['tl_affiliate'])) ? $_REQUEST['tl_affiliate'] : '';
        $tl_rate = (!empty($_REQUEST['tl_rate'])) ? $_REQUEST['tl_rate'] : '';
        $tl_title = (!empty($_REQUEST['tl_title'])) ? $_REQUEST['tl_title'] : '';
        update_user_meta($user_id, 'tl_affiliate', $tl_affiliate);
        update_user_meta($user_id, 'tl_rate', $tl_rate);
        update_user_meta($user_id, 'tl_title', $tl_title);

        // Super Team Leader
        $stl_affiliate = (!empty($_REQUEST['stl_affiliate'])) ? $_REQUEST['stl_affiliate'] : '';
        $stl_rate = (!empty($_REQUEST['tl_rate'])) ? $_REQUEST['stl_rate'] : '';
        $stl_title = (!empty($_REQUEST['stl_title'])) ? $_REQUEST['stl_title'] : '';
        update_user_meta($user_id, 'stl_affiliate', $stl_affiliate);
        update_user_meta($user_id, 'stl_rate', $stl_rate);
        update_user_meta($user_id, 'stl_title', $stl_title);


        // Registration emails hooks
        do_action('mla_after_registration', $affiliate_id);

      endif;

    endif;

  endif;

}

// Update affiliate from admin
add_action('affwp_post_update_affiliate', 'process_parent_id_edit_affiliate_form', 10, 0);
function process_parent_id_edit_affiliate_form() {

  affiliate_management_includes();

  if ( current_user_can('edit_users') ) :

    global $_REQUEST;

    if ( isset($_REQUEST['parent_affiliate_id']) ) : // This is here to check if the form has been submitted. A better option exists

      $affiliate_id = $_REQUEST['affiliate_id'];
      $user_id = affwp_get_affiliate_user_id($affiliate_id);
      $direct_id = $_REQUEST['direct_affiliate_id'];
      $parent_id = $_REQUEST['parent_affiliate_id'];

      if ( !empty($affiliate_id) ) :
        $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
        //endif;

        // update the direct affiliate
        //$direct_id = $_REQUEST['direct_affiliate_id'];
        if ( !empty($direct_id) ) {
          //$affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
          $affiliate->update_direct_affiliate($affiliate_id, $direct_id);
        } else {
          $affiliate->remove_direct_affiliate($affiliate_id);
        }

        // Parent Affiliate
        if ( !empty($parent_id) ) {
          //$affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
          $current_parent_id = $affiliate->get_parent_affiliate_id();

          if ( $current_parent_id != $parent_id ) :

            //$affiliate->restructure_affiliate_parents( '', $parent_id, '' );

            //$affiliate->set_parent_affiliate( $affiliate_id, $parent_id, FALSE, FALSE );
            //$affiliate->generate_affiliate_network_data( $affiliate_id, true, 'all'); // upward
            //$affiliate->restructure_affiliate_network_parents( $affiliate_id  ); // downward
            $affiliate->update_parent_affiliate($affiliate_id, $parent_id);

          endif;


        } else {

          //$affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
          $current_parent_id = $affiliate->get_parent_affiliate_id();

          if ( !empty($current_parent_id) ) :
            //$affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
            $affiliate->remove_parent_affiliate($affiliate_id);
          endif;

        }

      endif;

      // Team Leader
      $tl_affiliate = (!empty($_REQUEST['tl_affiliate'])) ? $_REQUEST['tl_affiliate'] : '';
      $tl_rate = (!empty($_REQUEST['tl_rate'])) ? $_REQUEST['tl_rate'] : '';
      $tl_title = (!empty($_REQUEST['tl_title'])) ? $_REQUEST['tl_title'] : '';
      update_user_meta($user_id, 'tl_affiliate', $tl_affiliate);
      update_user_meta($user_id, 'tl_rate', $tl_rate);
      update_user_meta($user_id, 'tl_title', $tl_title);

      // Super Team Leader
      $stl_affiliate = (!empty($_REQUEST['stl_affiliate'])) ? $_REQUEST['stl_affiliate'] : '';
      $stl_rate = (!empty($_REQUEST['tl_rate'])) ? $_REQUEST['stl_rate'] : '';
      $stl_title = (!empty($_REQUEST['stl_title'])) ? $_REQUEST['stl_title'] : '';
      update_user_meta($user_id, 'stl_affiliate', $stl_affiliate);
      update_user_meta($user_id, 'stl_rate', $stl_rate);
      update_user_meta($user_id, 'stl_title', $stl_title);

    endif;

  endif;

}

// Delete affiliate from admin
add_action('affwp_affiliate_deleted', 'process_delete_affiliate', 1, 3);
function process_delete_affiliate($affiliate_id, $delete_data, $affiliate) {

  affiliate_management_includes();

  //$user_id = affwp_get_affiliate_user_id( $affiliate->ID );
  $user_id = $affiliate->user_id;

  $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
  $affiliate->delete_affiliate($affiliate_id, $user_id);

}

?>