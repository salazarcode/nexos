<?php

//$this->store_debug_data($meta_data);

class AffiliateWP_MLA_Affiliate extends AffiliateWP_MLA_Common {

  public function __construct($affiliate_id) {

    parent::__construct();

    $this->affiliate_id = $affiliate_id;

  }


  //////////// Network Parent functions
  public function set_direct_affiliate($affiliate_id = '', $direct_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    // Set the direct referrer
    if ( empty($direct_id) ) :
      $direct_id = affiliate_wp()->tracking->get_affiliate_id();
    endif;

    if ( !empty($direct_id) && $direct_id != $affiliate_id ) { // second added so affiliate's can't be linked to themself
      affwp_update_affiliate_meta($affiliate_id, 'mla_direct_referrer', $direct_id);
    }

  }

  // Set the parent ID. Complete. Tested
  public function set_parent_affiliate($affiliate_id = '', $parent_id = '', $advanced = TRUE, $generate_network_data = TRUE) {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    //$affiliate_settings = get_affiliate_matrix_settings($affiliate_id);

    // prevent cookies from setting the parent when the user is in the admin area and the registration method is auto register
    // allow if through ajax as some form builder use this (Ninja)
    if ( !is_admin() || wp_doing_ajax() ) :

      // Look for Woo checkout dropdown parent
      // This method may not be required now the late checkout linking is in place
      if ( empty($parent_id) ) $parent_id = $this->get_checkout_referrals_parent_id();

      // Check if an affiliate coupon used
      // This method is handled in the direct referral hook

      // Get the tracked cookie ID.
      if ( empty($parent_id) ) $parent_id = affiliate_wp()->tracking->get_affiliate_id();

      // If no cookie tracking, check if Lifetime Commissions has tracked the user
      if ( empty($parent_id) ) {

        $user_id = affwp_get_affiliate_user_id($affiliate_id);

        if ( !empty($user_id) ) $parent_id = get_user_meta($user_id, 'affwp_lc_affiliate_id', true);

      }

      $parent_id = apply_filters('mla_parent_affiliate_after_tracked', $parent_id, $affiliate_id);

    endif;

    // Default parent ID
    if ( empty($parent_id) ) {
      $settings = $this->get_affiliate_matrix_settings($affiliate_id);

      if ( isset($settings['matrix_setting_id']) && !empty($settings['matrix_setting_id']) ) :

        $settings_string = $settings['matrix_setting_id'];

        $default_parent_id = $this->matrix_setting('matrix_default_parent', $settings_string);

        if ( !empty($default_parent_id) ) :
          $parent_id = $default_parent_id;
          $parent_id = apply_filters('mla_parent_affiliate_default', $parent_id, $affiliate_id);
        endif;

      endif;
    }

    $parent_id = apply_filters('mla_parent_affiliate_after_default', $parent_id, $affiliate_id);

    //update_option( 'set_parent_debug', $affiliate_id.' - '.$parent_id );

    if ( !empty($parent_id) ) {

      // Set the connection type. May get changed if spillover is engaged
      $this->connection_type = 'direct';

      // Advanced Parent ID. Considers Max width and spillover.
      // Considers Groups
      // Returns nothing if the parent_id is ok to use.
      //$advanced_parent_id = '';
      $advanced = apply_filters('mla_parent_advanced', $advanced, $parent_id, $affiliate_id);
      if ( $advanced ) :

        $advanced_parent_id = $this->get_advanced_parent_id($parent_id);

        if ( !empty($advanced_parent_id) ) :
          $parent_id = $advanced_parent_id;
        endif;

      endif;

      $parent_id = apply_filters('mla_parent_affiliate', $parent_id, $this->connection_type, $affiliate_id);

      // Set the affiliate meta key
      if ( !empty($parent_id) && $parent_id != $affiliate_id ) { // second added so affiliate's can't be linked to themself
        affwp_update_affiliate_meta($affiliate_id, 'mla_level_1', $parent_id);
      }

      // Generate the network data (all levels) for the affiliate only
      if ( $generate_network_data ) :
        $this->generate_affiliate_network_data($affiliate_id);
      endif;

      // connection_type either 'direct' or 'spillover'
      do_action('mla_set_parent_affiliate', $affiliate_id, $parent_id, $this->connection_type);

      return $parent_id;

    } else {

      return '';

    }
  }

  // Update parent ID
  public function update_parent_affiliate($affiliate_id, $parent_id) {

    $this->set_parent_affiliate($affiliate_id, $parent_id, FALSE, FALSE);
    $this->generate_affiliate_network_data($affiliate_id, true, 'all'); // upward
    $this->restructure_affiliate_network_parents($affiliate_id); // downward

  }

  // Remove parent affiliate
  public function remove_parent_affiliate($affiliate_id) {

    $parent_id = $this->get_parent_affiliate_id();

    if ( !empty($parent_id) ) :
      //$this->remove_affiliate_meta($affiliate_id, 'mla_level_1');
      $this->remove_affiliate_meta($affiliate_id);
      $this->restructure_affiliate_network_parents($affiliate_id);
    endif;

  }

  // Check for checkout referral affiliate (Woo drop down)
  function get_checkout_referrals_parent_id() {

    $affiliate_id = '';

    // Get the 'Checkout Referrals' dropdown value
    if ( isset($_POST['woocommerce_affiliate']) && !empty($_POST['woocommerce_affiliate']) ) {

      $context = 'woocommerce';
      $posted_affiliate = $_POST[$context . '_affiliate'];
      $affiliate_selection = affiliate_wp()->settings->get('checkout_referrals_affiliate_selection');

      // Input field. Accepts either an affiliate ID or username
      if ( 'input' === $affiliate_selection ) {

        if ( isset($posted_affiliate) && $posted_affiliate ) {

          if ( absint($posted_affiliate) ) {

            // affiliate ID
            $affiliate_id = absint($posted_affiliate);

          } elseif ( !is_numeric($affiliate_id) ) {

            // get affiliate ID from username
            $user = get_user_by('login', sanitize_text_field(urldecode($posted_affiliate)));

            if ( $user ) {
              $affiliate_id = affwp_get_affiliate_id($user->ID);
            }

          }

        }

      } else {

        // select menu
        if ( isset($posted_affiliate) && $posted_affiliate ) {
          $affiliate_id = absint($posted_affiliate);
        }

      }

    }

    return $affiliate_id;

  }

  // Get the direct ID. Complete. Tested
  public function get_parent_affiliate_id($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $parent_id = '';

    $parent_id = affwp_get_affiliate_meta($affiliate_id, 'mla_level_1', TRUE);

    //$parent_id = $current_parent_id;

    if ( !empty($parent_id) ) {
      return $parent_id;
    }

    return '';

  }

  // Get the parent ID. Complete. Tested
  public function get_direct_affiliate_id($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $direct_id = '';

    $direct_id = affwp_get_affiliate_meta($affiliate_id, 'mla_direct_referrer', TRUE);

    //$parent_id = $current_parent_id;

    if ( !empty($direct_id) ) {
      return $direct_id;
    }

    return '';

  }

  // Update direct ID
  public function update_direct_affiliate($affiliate_id, $direct_id) {

    //affwp_update_affiliate_meta($affiliate_id, 'mla_direct_referrer', $direct_id);
    $this->set_direct_affiliate($affiliate_id, $direct_id);

  }

  // Update parent ID
  public function remove_direct_affiliate($affiliate_id) {

    $this->remove_affiliate_meta($affiliate_id, 'mla_direct_referrer');

  }

  // Get the advanced parent ID. Complete. Tested
  public function get_advanced_parent_id($parent_id) {

    // Get the affiliate's matrix settings
    $affiliate_settings = $this->get_affiliate_matrix_settings($parent_id);

    if ( empty($affiliate_settings) ) {
      return '';
    } else {

      $matrix_setting_id = $affiliate_settings['matrix_setting_id'];

      // Check for max width
      $matrix_width = $this->matrix_setting('matrix_width', $matrix_setting_id);

      // If no max width, return nothing. Original parent ID is used
      if ( empty($matrix_width) ) {

        return '';

      } else {

        // If spillover allowed, determine the correct parent ID. Returns nothing if the fist row isn't full
        if ( $this->matrix_setting('allow_spillover', $matrix_setting_id) == '1' ) {

          $this->connection_type = 'spillover';
          return $this->get_spillover_parent_affiliate($parent_id);

        }

      }

      return '';

    }

  }

  // Determines the parent affiliate when a spillover required. Complete. Tested
  public function get_spillover_parent_affiliate($parent_id) {

    // Get the affiliate's matrix settings
    $affiliate_settings = $this->get_affiliate_matrix_settings($parent_id);

    if ( empty($affiliate_settings) ) {
      return '';
    } else {

      $matrix_setting_id = $affiliate_settings['matrix_setting_id'];

      // Get the max depth
      $matrix_depth = $this->get_affiliates_network_depth($parent_id);

      // Get the max width
      $matrix_width = $this->matrix_setting('matrix_width', $matrix_setting_id);

      $level_affiliates = array();

      // Loop through all levels
      for ($x = 0; $x <= $matrix_depth; $x++) {

        // Get an array of affiliates on this level to analyse
        $level_affiliates[$x] = $this->get_level_affiliates($parent_id, $x + 1);

        //Check if an available spot is available on this level
        if ( (count($level_affiliates[$x])) < ($matrix_width * ($x + 1)) ) {

          if ( $x > 0 ) {
            $process_level = $this->get_level_affiliates($parent_id, $x);
            break;
          } else {
            return ''; // Return nothing (original ID will apply) if first level is not full
          }

        }

      }

      // If spot available. Returns the first child with an available spot
      if ( !empty($process_level) ) {
        // Loop through all affiliates and find the first one with an available spot
        foreach ($process_level as $affiliate) {

          $level_1_affiliates = $this->get_level_affiliates($affiliate->affiliate_id, '1');
          $count = count($level_1_affiliates);

          // If a spot is available, return the affiliate ID to use as the parent
          if ( count($level_1_affiliates) < $matrix_width ) {
            return $affiliate->affiliate_id;
          }

        }

      }

      return '';  // Return nothing (original ID will apply) by default

    } // end of check id no settings

  }



  //////////// Affiliate functions

  // Get affiliate settings. Returns the affilaites group matrix by default if set.
  public function get_affiliate_matrix_settings($affiliate_id = '', $allow_groups = true) {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $affiliate_matrix_settings = array();

    $mla_settings = new AffiliateWP_Multi_Level_Affiliates_Settings();
    $raw_settings = $mla_settings->matrix_settings_list('raw');

    // Determines if a group's settings should be used instead of the default.
    // Will return default if higher than best group or a group is configured to use the default settings
    // Now replaced with better logic in class-proups.php
    if ( $allow_groups && $this->groups_enabled() ) {

      $settings_string = $this->get_affilate_group_id();

    } else {

      $settings_string = 'default';

    }

    // New. Always return at least the default
    if ( empty($settings_string) ) {

      $settings_string = 'default';
    }

    // If no matrix available return nothing
    //if( empty($settings_string) ) {

    //return '';

    //} else {

    $affiliate_matrix_settings['matrix_setting_id'] = $settings_string;

    // Get the settings
    foreach ($raw_settings as $raw_setting_key => $data) {
      $affiliate_matrix_settings[$raw_setting_key] = $this->matrix_setting($raw_setting_key, $settings_string);
    }

    // If type is default, get the default
    if ( $affiliate_matrix_settings['rate_type'] == 'default' ) $affiliate_matrix_settings['rate_type'] = affiliate_wp()->settings->get('referral_rate_type');

    // Get the per level rates
    $levels = $this->matrix_setting('matrix_depth', $settings_string);

    for ($x = 1; $x <= $levels; $x++) {

      $level_rate_value = $this->matrix_setting('level_' . $x . '_rate', $settings_string);

      // Can be removed. Level rates now required
      if ( $level_rate_value == NULL ) {
        $affiliate_matrix_settings['per_level_rates'][$x] = $this->matrix_setting('rate_value', $settings_string);
      } else {
        $affiliate_matrix_settings['per_level_rates'][$x] = $level_rate_value;
      }

      /*if( isset($level_rate_value) && !empty($level_rate_value) ) {
        $affiliate_matrix_settings['per_level_rates'][$x] = $level_rate_value;
      } else {
        $affiliate_matrix_settings['per_level_rates'][$x] = $this->matrix_setting( 'rate_value', $settings_string);
      }*/

    }

    //$this->store_debug_data( array( 'string' => $settings_string, 'settings' => $affiliate_matrix_settings) );

    return $affiliate_matrix_settings;

    //}

  }

  // Get the affilates levels and rates they are entitled to or the global set if $allow_groups set to false.
  // Used in matrix class ato determine global upward commissions and in charts/stats to display an affilate's rates and fallback display levels  etc.
  // Also used in generate network data but needs to be modified there. Complete. Tested
  public function get_matrix_level_vars($allow_groups = true) {

    $level_vars = array();

    $affiliate_matrix_settings = $this->get_affiliate_matrix_settings('', $allow_groups);

    if ( empty($affiliate_matrix_settings) ) {
      return $level_vars;
    } else {

      $level_vars['type'] = $affiliate_matrix_settings['rate_type'];
      $level_vars['total_levels'] = 0;

      foreach ($affiliate_matrix_settings['per_level_rates'] as $level => $rate) {
        $level_vars['level_rates'][$level] = $rate;
        $level_vars['total_levels']++;
      }

      return $level_vars;

    }

  }

  // Get the affilates indirect level affilate id's working upward.
  // Only returns the default matrix by default. No need for groups when working upward.
  // Only used in matrix class. Complete. Tested
  // This should no longer set level 1
  public function get_all_level_affiliates($allow_groups = false) {

    $level_affilates = array();

    $affiliate_matrix_settings = $this->get_affiliate_matrix_settings('', $allow_groups);

    if ( !empty($affiliate_matrix_settings) ) {

      $total_levels = $affiliate_matrix_settings['matrix_depth']; // payment levels

      $affiliate_id = $this->affiliate_id;

      for ($x = 1; $x <= $total_levels; $x++) {

        if ( !empty($affiliate_id) ) {
          $level_affilates[$x] = $affiliate_id;
          $affiliate_id = $this->get_parent_affiliate_id($affiliate_id);
        }
      }

    }

    return $level_affilates;

  }

  // Get the per level rates. Complete. Tested. Not currently used
  /*
  public function get_matrix_level_rates() {

    $level_rates = array();

    $affiliate_matrix_settings = $this->get_affiliate_matrix_settings();

    if( !empty($affiliate_matrix_settings) ) {

      $level_rates = $affiliate_matrix_settings['per_level_rates'];

    }

    return $level_rates;
  }
  */

  // Process after an affiliate is deleted. Complete. Tested
  public function delete_affiliate($affiliate_id = '', $user_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;
    if ( empty($user_id) ) $user_id = affwp_get_affiliate_user_id($affiliate_id);

    // Get the current parent
    $parent_id = $this->get_parent_affiliate_id($affiliate_id);

    // Move all direct children to the deleted affiliate's parent
    $direct_children = $this->get_level_affiliates($affiliate_id, '1');
    // Loop all and update parent. Regenerate is true
    if ( !empty($direct_children) ) {
      foreach ($direct_children as $affiliate) :
        // Restructure the affiliate's parent data
        $this->restructure_affiliate_parents($affiliate->affiliate_id, $parent_id);
        // Restrcuture all affiliate's in their network to reflect the new parents
        $this->restructure_affiliate_network_parents($affiliate->affiliate_id);
      endforeach;
    }

    // Remove all affiliate meta for the deleted affiliate
    $this->remove_affiliate_meta($affiliate_id);

  }



  //////////// Network functions

  // Generate affiliate network data. Generates the upward direction. Complete. Tested.
  // needs better logic to determine the max levels.  get_matrix_level_vars() no longer the best method
  public function generate_affiliate_network_data($affiliate_id = '', $save = TRUE, $regenerate = 'all') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    if ( $regenerate == 'all' || $regenerate == 'payment' ) :

      // Get all the network level data
      $matrix_level_vars = $this->get_matrix_level_vars($affiliate_id);
      // This needs to be the absolute maximum available. Not based on the registering affiliate.
      $matrix_total_levels = $matrix_level_vars['total_levels'];

      $meta_data = array();
      $parent_affiliate = $this->get_parent_affiliate_id($affiliate_id);
      $total_network_levels = 0;
      for ($x = 1; $x <= $matrix_total_levels; $x++) {

        if ( !empty($parent_affiliate) ) {

          $meta_data['mla_level_' . $x] = $parent_affiliate;

          if ( $save ) affwp_update_affiliate_meta($affiliate_id, 'mla_level_' . $x, $parent_affiliate);

          $parent_affiliate = $this->get_parent_affiliate_id($parent_affiliate);

          $total_network_levels++;

        } else {

          affwp_delete_affiliate_meta($affiliate_id, 'mla_level_' . $x);
        }

      }

      // Remove all redundent network affiliate meta data.
      for ($x = $total_network_levels + 1; $x <= 99999999999; $x++) {

        $level_check = affwp_get_affiliate_meta($affiliate_id, 'mla_level_' . $x);

        // If key exists, remove it
        if ( !empty($level_check) ) {
          affwp_delete_affiliate_meta($affiliate_id, 'mla_level_' . $x);
        } else {
          break;
        }
      }

    endif;

    if ( $regenerate == 'all' || $regenerate == 'charts' ) :

      // Set all chart level data
      $chart_total_levels = $this->plugin_setting('dashboard_chart_levels');
      //$chart_total_levels = 100;
      $chart_total_levels = (!empty($chart_total_levels)) ? $chart_total_levels : $matrix_total_levels;
      $meta_data = array();
      $parent_affiliate = $this->get_parent_affiliate_id($affiliate_id);
      //$original_parent = $parent_affiliate; // temp for bug fix
      $total_network_levels2 = 0;
      for ($x = 1; $x <= $chart_total_levels; $x++) {

        if ( !empty($parent_affiliate) ) {

          $meta_data['mla_charts_level_' . $x] = $parent_affiliate;

          //if( isset($original_parent) && $original_parent != $parent_affiliate ) :  // temp for bug fix
          if ( $save ) affwp_update_affiliate_meta($affiliate_id, 'mla_charts_level_' . $x, $parent_affiliate);
          //endif;

          $parent_affiliate = $this->get_parent_affiliate_id($parent_affiliate);

          $total_network_levels2++;

        } else {

          affwp_delete_affiliate_meta($affiliate_id, 'mla_chart_level_' . $x);

        }

      }

      // Remove all redundent chart affiliate meta data.
      $check_chart_level = ($total_network_levels2 + 1);
      for ($x = $check_chart_level; $x <= 99999999999; $x++) {

        $level_check2 = affwp_get_affiliate_meta($affiliate_id, 'mla_chart_level_' . $x);

        // If key exists, remove it
        if ( !empty($level_check2) ) {
          affwp_delete_affiliate_meta($affiliate_id, 'mla_chart_level_' . $x);
        } else {
          break;
        }
      }

    endif;

    if ( !empty($meta_data) ) return $meta_data;

  }

  // Restructure an affiliate's entire network data. Generates in the downward direction Complete. Tested
  public function restructure_affiliate_network_parents($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    // Regenerate the entire network payment levels
    $affiliates = $this->get_entire_network($affiliate_id);

    if ( !empty($affiliates) ) :

      foreach ($affiliates as $network_affiliate_id => $args) :

        $this->restructure_affiliate_parents($network_affiliate_id, '', 'payment');

      endforeach;

    endif;

    // Regenerate the entire network chart levels. Untested
    $chart_affiliates = $this->get_entire_network($affiliate_id, 'chart_levels');

    if ( !empty($chart_affiliates) ) :

      foreach ($chart_affiliates as $network_affiliate_id => $args) :

        $this->restructure_affiliate_parents($network_affiliate_id, '', 'charts');

      endforeach;

    endif;


  }

  // Restructure an affiliate's parents. This can probably be removed. set parent is no longer required here as the update  parent now handles it
  // All others that call it simply want to regenerate the network data.
  public function restructure_affiliate_parents($affiliate_id = '', $parent_id = '', $regenerate = 'all') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    // Set the parent ID if required
    if ( !empty($parent_id) ) {
      // Set the parent ID and generate the network data
      $this->set_parent_affiliate($affiliate_id, $parent_id, FALSE);
    } else {
      // Just regenerate the network data
      $this->generate_affiliate_network_data($affiliate_id, true, $regenerate);
    }

  }

  // Restructure entire network. Regenerates data for all affiliates. Called after level settings chnage in 'class-settings.php'
  public function restructure_entire_mla_network() {

    $all_affiliates = affiliate_wp()->affiliates->get_affiliates(array('number' => 0));

    foreach ($all_affiliates as $a) :

      $this->restructure_affiliate_parents($a->affiliate_id);

    endforeach;
  }

  // Determins the full depth of the affiliate's network. Complete. Tested
  public function get_affiliates_network_depth($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $return_level = 0;

    for ($x = 1; $x <= 999999999; $x++) {

      $per_level_affilates = $this->get_level_affiliates($affiliate_id, $x);
      $count = count($per_level_affilates);

      if ( $count > 0 ) {
        $return_level = $x;
      } else {
        break;
      }

    }

    return $return_level;

  }

  //////////// Meta data queries

  // Affiliate Meta Queries
  public function do_affiliate_meta_query($atts) {

    global $wpdb;

    // Get the table name
    //define( 'AFFILIATE_WP_NETWORK_WIDE', true );
    if ( defined('AFFILIATE_WP_NETWORK_WIDE') && AFFILIATE_WP_NETWORK_WIDE ) {
      // Allows a single affiliate table for the whole network
      $table_name = 'affiliate_wp_affiliatemeta';
    } else {
      $table_name = $wpdb->prefix . 'affiliate_wp_affiliatemeta';
    }

    // Construct the where statement
    $where = 'WHERE ';
    $where_and = '';

    if ( is_array($atts['where']) && !empty($atts['where']) ) {
      foreach ($atts['where'] as $column => $args) :

        if ( $args['operator'] == 'IN' ) {

          $where .= $where_and . ' ' . $column . ' ' . $args['operator'] . ' ' . $args['value'] . ' ';

        } else {

          $where .= $where_and . ' ' . $column . ' ' . $args['operator'] . ' \'' . $args['value'] . '\' ';

        }

        $where_and = 'AND';
      endforeach;
    }

    // Order By

    if ( !empty($table_name) && !empty($where) ) {
      $query = $wpdb->get_results("SELECT * FROM $table_name $where");
      //$wpdb->show_errors(); $wpdb->print_error();
      //print_r($query);
      return $query;
    } else {
      return array();
    }


  }

  // Get all affililiates on a specfic level. Complete. Tested
  public function get_level_affiliates($affiliate_id = '', $level) {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $atts = array(

      'where' => array(
        'meta_key' => array('operator' => '=', 'value' => 'mla_level_' . $level),
        'meta_value' => array('operator' => '=', 'value' => $affiliate_id),
      ),

    );

    $results = $this->do_affiliate_meta_query($atts);

    if ( !empty($results) ) {
      return $results;
    } else {
      return array();
    }

  }

  // Get all affiliates on a specific chart level. Complete. Tested
  public function get_chart_level_affiliates($affiliate_id = '', $level) {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;
    $atts = array(

      'where' => array(
        'meta_key' => array('operator' => '=', 'value' => 'mla_charts_level_' . $level),
        'meta_value' => array('operator' => '=', 'value' => $affiliate_id),
      ),

    );

    $results = $this->do_affiliate_meta_query($atts);
    //echo '<pre>'; print_r($results); echo '</pre>';

    if ( !empty($results) ) {
      return $results;
    } else {
      return array();
    }

  }

  /*public function get_chart_level_affiliates( $level_affiliates) {

    //if(empty($affiliate_id)) $affiliate_id = $this->affiliate_id;

    $atts = array(

      'where' => array(
        'meta_key' => array( 'operator' => '=', 'value' => 'mla_level_1' ),
        'meta_value' => array( 'operator' => 'IN', 'value' => '('.implode(',', array_map('intval', $level_affiliates)).')' ),
      ),

    );

    $results = $this->do_affiliate_meta_query( $atts );

    if( !empty($results) ) {
      return $results;
    } else {
      return array();
    }

  }*/

  // Get an array of all affiliates on all levels. Complete. Tested
  public function get_entire_network($affiliate_id = '', $type = 'payment_levels') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    if ( $type == 'chart_levels' ) {

      // chart levels
      $meta_value = 'mla_charts_level_';

    } else {

      // payment levels
      $meta_value = 'mla_level_';

    }

    $atts = array(

      'where' => array(
        'meta_key' => array('operator' => 'REGEXP', 'value' => $meta_value),
        'meta_value' => array('operator' => '=', 'value' => $affiliate_id),
      ),

    );

    $results = $this->do_affiliate_meta_query($atts);

    if ( !empty($results) ) {

      $affiliates = array();

      foreach ($results as $affiliate)  :

        $affiliate_obj = affwp_get_affiliate($affiliate->affiliate_id);
        $user_info = get_userdata($affiliate_obj->user_id);

        $parent_affiliate_id = $this->get_parent_affiliate_id($affiliate->affiliate_id);
        $parent_user_info = get_userdata(affwp_get_affiliate_user_id($parent_affiliate_id));

        $affiliates[$affiliate->affiliate_id] = array(
          /*'affiliate_object' => $affiliate_obj,*/
          'user_info' => $user_info,
          'parent_user_info' => $parent_user_info,

        );

      endforeach;

    }

    if ( isset($affiliates) && !empty($affiliates) ) {
      return $affiliates;
    } else {
      array();
    }

  }

  // Delete affiliate meta. Complete. Tested
  public function remove_affiliate_meta($affiliate_id = '', $meta_key = '', $meta_value = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    global $wpdb;

    // Get the table name
    if ( defined('AFFILIATE_WP_NETWORK_WIDE') && AFFILIATE_WP_NETWORK_WIDE ) {
      // Allows a single affiliate table for the whole network
      $table_name = 'affiliate_wp_affiliates';
    } else {
      $table_name = $wpdb->prefix . 'affiliate_wp_affiliatemeta';
    }

    $where = array(
      'affiliate_id' => $affiliate_id,
    );

    if ( !empty($meta_key) ) {
      $where['meta_key'] = $meta_key;
    }

    if ( !empty($meta_value) ) {
      $where['meta_value'] = $meta_value;
    }

    $wpdb->delete($table_name, $where);

  }


  //////////// Affiliate Groups Functions

  // New function will be as folows to get the best setting for specific purpose instead of using order total
  public function get_best_matrix_setting($affiliate_id, $key) {
    // Get the default
    // Filter using groups class
  }

  // Return the relevent group ID. Complete. Tested
  public function get_affilate_group_id() {

    $groups = $this->get_available_group_matrix_rates('', '');

    if ( count($groups) == 1 ) {

      return key($groups);

    } elseif ( count($groups) > 1 ) {

      return $this->get_best_affiliate_group($groups);

    } else {

      return '';

    }

    //}

  }

  // Find the best affiliate group if the affiliate is a member of more than one. Complete. Tested
  // In future, only use this to find the best group when calculating commissions, not network sructure settings
  public function get_best_affiliate_group($active_groups, $order_total = '') {

    // Try and replace this. Last remaining depenpency on affiliates class
    if ( empty($active_groups) ) $active_groups = $this->get_available_group_matrix_rates('', '');

    $groups = array();

    $order_total = (!empty($order_total)) ? $order_total : $this->get_order_total();

    // if no order total, do something different
    if ( !empty($order_total) ) {

      foreach ($active_groups as $key => $data) {
        $rate_type = $data['rate_type'];
        $rate = $data['rate'];

        if ( $rate_type == 'percentage' ) {
          $groups[$key] = ((($rate / 100) * $order_total));
        } elseif ( $rate_type == 'flat' ) {
          $groups[$key] = $rate;
        }

      }

      arsort($groups);
      return key($groups);

    } else {

      // If no order total, return the first group
      return key($active_groups);

    }

  }

  // Get all possible group matrix rates for a level + default rates.
  public function get_available_group_matrix_rates($affiliate_id = '', $level = '1') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $affwp_default_rate_type = affiliate_wp()->settings->get('referral_rate_type');
    if ( empty($affwp_default_rate_type) ) $affwp_default_rate_type = 'percentage';

    $default_matrix_enabled = (bool)TRUE;
    $groups = array();

    if ( $this->groups_enabled() ) :

      // Unset the default unless required by groups settings
      $default_matrix_enabled = (bool)FALSE;

      $active_groups = get_affiliates_active_groups($affiliate_id);

      // If affiliate is not part of any group, just add the default
      if ( count($active_groups) < 1 ) {

        $default_matrix_enabled = (bool)TRUE;

      } else {

        foreach ($active_groups as $group_id => $args) :

          $mla_mode = get_affiliate_group_setting('mla_mode', $group_id);
          unset($group_string);

          if ( $mla_mode == 'enabled' ) {
            //$group_string = 'default';
            $default_matrix_enabled = (bool)TRUE;
          } elseif ( $mla_mode == 'enabled_extended' ) {
            $group_string = $group_id;
          } elseif ( $mla_mode == 'disabled' ) {

          }

          if ( !empty($group_string) ) {

            $groups[$group_id] = array();

            $groups[$group_id]['rate_type'] = $this->matrix_setting('rate_type', $group_string);
            if ( $groups[$group_id]['rate_type'] == 'default' ) $groups[$group_id]['rate_type'] = $affwp_default_rate_type;

            // If direct referrals mode is disabled, get the affiliate rate and type
            $direct_mode = $this->matrix_setting('direct_referral_mode', $group_string);
            if ( $direct_mode == 'disabled' && $level == 1 ) {

              $groups[$group_id]['rate'] = ($groups[$group_id]['rate_type'] == 'percentage') ? affwp_get_affiliate_rate($affiliate_id) * 100 : affwp_get_affiliate_rate($affiliate_id);

            } else {

              $group_rate = $this->matrix_setting('level_' . $level . '_rate', $group_string);
              // Condition no longer required as the level rates are required
              $groups[$group_id]['rate'] = (!empty($group_rate)) ? $group_rate : $this->matrix_setting('rate_value', $group_string);

            }

          }

        endforeach;

      }

    endif;

    // Add the default rates if not set already. Only used in stats ? No longer Required ????
    if ( $default_matrix_enabled ) :
      $groups['default'] = array();

      $groups['default']['rate_type'] = $this->matrix_setting('rate_type', 'default');
      // If type is default, get the default
      if ( $groups['default']['rate_type'] == 'default' ) $groups['default']['rate_type'] = $affwp_default_rate_type;

      // If direct referrls mode is disabled, get the affiliateWP rate and type
      $direct_mode = $this->matrix_setting('direct_referral_mode', 'default');
      if ( $direct_mode == 'disabled' && $level == 1 ) {

        //$groups['default']['rate_type'] = affwp_get_affiliate_rate_type( $affiliate_id );
        $groups['default']['rate'] = ($groups['default']['rate_type'] == 'percentage') ? affwp_get_affiliate_rate($affiliate_id) * 100 : affwp_get_affiliate_rate($affiliate_id);

      } else {

        $group_rate = $this->matrix_setting('level_' . $level . '_rate', 'default');
        // Condition no longer required as the level rates are required
        $groups['default']['rate'] = (!empty($group_rate)) ? $group_rate : $this->matrix_setting('rate_value', 'default');

      }

    endif;

    return $groups;

  }


}

?>