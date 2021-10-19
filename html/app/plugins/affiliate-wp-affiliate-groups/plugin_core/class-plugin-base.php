<?php

class AffiliateWP_Affiliate_Groups_Base {

  //protected $plugin_settings;
  //protected $plugin_config;

  public function __construct() {

    //if (defined('AFFWP_AG_PLUGIN_CONFIG')) $this->plugin_config = unserialize(AFFWP_AG_PLUGIN_CONFIG);
    //if (defined('AFFWP_AG_PLUGIN_SETTINGS')) $this->plugin_settings = unserialize(AFFWP_AG_PLUGIN_SETTINGS);
    $affwp_groups = affiliate_wp_groups();
    $this->plugin_config = $affwp_groups->plugin_config;
    $this->plugin_settings = $affwp_groups->plugin_settings;

    $lps = get_site_option($this->plugin_config['plugin_prefix'] . '_' . 'lps', '');
    if ( !empty($lps) && $lps != '2' ) :
      $this->load_textdomain();
      $this->setup_hooks();
    endif;
  }

  public function load_textdomain() {

    // Set filter for plugin's languages directory
    $lang_dir = $this->plugin_config['plugin_lang_dir'];

    global $wp_version;
    $get_locale = get_locale();
    if ( $wp_version >= 4.7 ) {
      $get_locale = get_user_locale();
    }

    $locale = apply_filters('plugin_locale', $get_locale, 'affiliate-wp-affiliate-groups');
    $mofile = sprintf('%1$s-%2$s.mo', 'affiliate-wp-affiliate-groups', $locale);

    $mofile_global = WP_LANG_DIR . '/affiliate-wp-affiliate-groups/' . $mofile;

    if ( file_exists($mofile_global) ) {
      // Look in global /wp-content/languages/ folder
      load_textdomain('affiliate-wp-affiliate-groups', $mofile_global);
    } else {
      // Load the default language files from plugin
      load_plugin_textdomain('affiliate-wp-affiliate-groups', false, $lang_dir);
    }
  }

  // Setup
  public function setup_hooks() {

    // Add group options to add new affiliate form
    add_action('affwp_new_affiliate_end', array($this, 'add_groups_to_new_affiliate_form'));

    // Process new affiliate data. Now in functions
    //add_action( 'affwp_post_insert_affiliate', array( $this, 'process_new_affiliate_form' ), 10);

    // Add fields and data to the edit affiliate page
    add_action('affwp_edit_affiliate_end', array($this, 'add_groups_to_affiliate_page'), 97);

    // Process the edit affiliate page. Now in functions
    //add_action( 'affwp_post_update_affiliate', array( $this, 'process_edit_affiliate_form' ) );

    // Auto grouping after affiliate registration
    //if( ($this->plugin_settings[$this->plugin_config['plugin_prefix'].'_auto_grouping_enable']) =='1') {
    // Generic
    //add_action('affwp_insert_affiliate', array( $this, 'affiliate_auto_grouping_registration' ), 10, 1);

    // Specific actions
    //add_action( 'affwp_auto_register_user', array( $this, 'affiliate_auto_grouping_registration' ), 10, 1 );

    //}

    // Remove groups from the roles checkbox group on the edit user page
    add_filter('editable_roles', array($this, 'remove_groups_from_edit_user_profile_roles'));

    // Removes groups from the users page (views)
    add_filter('views_users', array($this, 'remove_groups_from_user_views'));

    // Reset groups
    //add_action('affiliate_groups_reset', array($this, 'reset_all_wordpress_roles'));

    // Set affiliate group names
    add_filter('change_group_name', array($this, 'change_group_name'), 10, 2);

    // Affiliate rate hooks
    add_filter('affwp_get_affiliate_rate_type', array($this, 'get_affiliate_group_rate_type'), 99, 2);

    add_filter('affwp_get_affiliate_rate', array($this, 'get_affiliate_group_rate'), 99, 4);

    // Affiliates table rate
    add_filter('affwp_affiliate_table_rate', array($this, 'get_affiliate_table_rate'), 99, 2);

    // Calculate the referral amount
    // Affiliate Product Rates add-on hooked with priorty 10 here. Ensure this priority is less than 10
    add_filter('affwp_calc_referral_amount', array($this, 'calc_referral_amount'), 9, 5);

    // Test function
    add_shortcode('aff_groups_plugin_test_function', array($this, 'plugin_test_function'));

    // Add groups column to the affiliates admin list
    add_filter('affwp_affiliate_table_columns', array($this, 'affiliate_table_columns'), 10, 2);
    add_filter('affwp_affiliate_table_group', array($this, 'affwp_affiliate_table_group'), 10, 2);

    // Add group to the MLA admin chart
    add_filter('mla_chart_node_info', array($this, 'mla_custom_chart_info'), 10, 3);

    // Add group to the MLA admin chart
    add_filter('mla_chart_node_attributes', array($this, 'mla_chart_node_attributes'), 10, 3);

  }

  ////////////// Group Functions //////////////

  // Get all groups(roles)
  public static function get_all_groups() {

    $affiliate_groups = array();
    $affiliate_groups = get_site_option('AFFWP_AG_groups', '');

    if ( is_array($affiliate_groups) && count($affiliate_groups) > 0 ) {

      foreach ($affiliate_groups as $key => $data) {
        if ( $data['status'] != '1' ) unset($affiliate_groups[$key]);
      }

    }

    return $affiliate_groups;

  }

  // Get all active groups. Public
  public function get_all_active_groups() {

    $affiliate_groups = $this->get_all_groups();

    if ( is_array($affiliate_groups) && count($affiliate_groups) > 0 ) {

      foreach ($affiliate_groups as $key => $atts) {

        if ( $this->get_group_settings($key, 'enable') != '1' ) {
          unset($affiliate_groups[$key]);
        }

      }

    }

    return $affiliate_groups;
  }

  // Get all active groups. Public Static
  public static function get_all_active_affiliate_groups() {

    $affiliate_groups = AffiliateWP_Affiliate_Groups_Base::get_all_groups();

    if ( is_array($affiliate_groups) && count($affiliate_groups) > 0 ) {

      foreach ($affiliate_groups as $key => $atts) {

        $setting_key = 'AFFWP_AG_' . $key . '_enable';
        $setting = affiliate_wp()->settings->get($setting_key);

        if ( !($setting == '1') ) {
          unset($affiliate_groups[$key]);
        }
      }

    }

    return $affiliate_groups;
  }


  //////////////// WordPres Roles /////////////

  // Reset all roles
  /*public function reset_all_wordpress_roles($arg) {

    $this->remove_affiliate_groups();

    if ( isset($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_roles']) ) :

      if ( ($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_roles']) == '1' ) {
        $this->add_affilate_groups();
      }

    endif;
  }*/

  // Add all active groups(roles) to WordPress
  /*public function add_affilate_groups() {

    $affiliate_groups = $this->get_all_groups();

    if ( is_array($affiliate_groups) && count($affiliate_groups) > 0 ) {

      foreach ($affiliate_groups as $key => $attributes) {

        if ( ($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_' . $key . '_enable']) == '1' ) {

          add_role($key, $attributes['name'], $attributes['capabilities']);

        }
      }

    }

  }*/

  // Remove all groups(roles) from WordPress
  /*public function remove_affiliate_groups() {

    $affiliate_groups = $this->get_all_groups();

    if ( is_array($affiliate_groups) && count($affiliate_groups) > 0 ) {

      foreach ($affiliate_groups as $key => $attributes) {
        remove_role($key);
      }

    }

  }*/

  // Get all user roles. Not used
  /*public function get_user_roles() {
    global $wp_roles;
    if ( !isset($wp_roles) )
      $wp_roles = new WP_Roles();

    return $wp_roles->get_names();
  }*/

  // Get a specific users roles. Not used
  /* public static function get_users_roles($user_id) {
     $user = get_userdata($user_id);
     $user_roles = $user->roles;

     return $user_roles;

   }*/

  // Check if user has a role. Not used
  /*public function check_user_role($user_id, $role) {
    $user = get_userdata($user_id);
    $user_roles = $user->roles;
    foreach ($user_roles as $key => $value) {
      if ( $role == $value ) return TRUE;
    }

    return FALSE;
  }*/

  // Check if logged in user has specific role. Not used
  /*private function current_user_has_role($role) {
    $user = wp_get_current_user();
    if ( in_array($role, (array)$user->roles) ) {
      return TRUE;
    } else {
      return FALSE;
    }
  }*/


  // Optionally used
  /*public function sync_groups_user_roles($affiliate_id) {

    $user_id = affwp_get_affiliate_user_id($affiliate_id);
    $user = get_user_by('id', $user_id);

    $all_groups = $this->get_all_groups();
    foreach ($all_groups as $group_id => $data) :
      $user->remove_role($group_id);
    endforeach;


    $groups = $this->get_affiliates_active_groups($affiliate_id);
    foreach ($groups as $group_id => $name) :
      $user->add_role($group_id);
    endforeach;


  }*/

  // Same as above but static
  /*public static function sync_groups_user_roles_static($affiliate_id) {

    $user_id = affwp_get_affiliate_user_id($affiliate_id);
    $user = get_user_by('id', $user_id);

    $all_groups = AffiliateWP_Affiliate_Groups_Base::get_all_groups();
    foreach ($all_groups as $group_id => $data) :
      $user->remove_role($group_id);
    endforeach;


    $groups = AffiliateWP_Affiliate_Groups_Base::affiliates_active_groups($affiliate_id);
    foreach ($groups as $group_id => $name) :
      $user->add_role($group_id);
    endforeach;


  }*/

  // Group name filter function
  public function change_group_name($name, $group_id) {

    $groups = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_auto_grouping_options'];

    if ( !empty($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_affiliate_group_id_' . $group_id . '_name']) ) {
      return $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_affiliate_group_id_' . $group_id . '_name'];
    } else {
      return $name;
    }

  }

  ///////////// Group Settings function //////////

  // Get a groups settings
  public function get_group_settings($group_id, $key = '') {

    $group_settings = array();
    $group_settings['group_id'] = $group_id;
    $group_settings['enable'] = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_' . $group_id . '_enable'];
    $group_settings['name'] = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_' . $group_id . '_name'];
    $group_settings['rate_type'] = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_' . $group_id . '_rate_type'];
    $group_settings['rate'] = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_' . $group_id . '_rate'];

    if ( isset($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_' . $group_id . '_mla_mode']) ) :
      $group_settings['mla_mode'] = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_' . $group_id . '_mla_mode'];
    endif;

    if ( empty($key) ) {
      return $group_settings;
    } else {
      return $group_settings[$key];
    }
  }

  // Get all groups settings
  public function get_all_groups_settings() {

    $groups = $this->get_all_groups();
    $group_settings = array();

    foreach ($groups as $key => $atts) {
      $group_settings[$key] = $this->get_group_settings($key);
    }

    return $group_settings;
  }

  // Get a users active groups
  /*public function get_users_active_groups($user_id) {

    $groups = array();

    if(!empty($user_id)) {

      $users_roles = $this->get_users_roles($user_id);
      $all_active_groups = $this->get_all_active_groups();
      foreach($users_roles as $key => $role_id) {
        if (array_key_exists($role_id, $all_active_groups)) {
          $groups[$role_id] = $all_active_groups[$role_id]['name'];
        }
      }

    }

    return $groups;
  }*/

  // Get a users active groups. Public Static. Used only in upgrade script
  public static function get_users_active_affiliate_groups($user_id) {

    $groups = array();

    if ( !empty($user_id) ) {

      $users_roles = AffiliateWP_Affiliate_Groups_Base::get_users_roles($user_id);
      $all_active_groups = AffiliateWP_Affiliate_Groups_Base::get_all_active_affiliate_groups();

      foreach ($users_roles as $key => $role_id) {
        if ( array_key_exists($role_id, $all_active_groups) ) {
          $groups[$role_id] = $all_active_groups[$role_id]['name'];
        }
      }

    }

    return $groups;
  }



  //////////////// New Affiliate methods ///////////////

  // Get affiliate's groups (inactive and active)
  public function get_affiliates_groups($affiliate_id) {

    $groups = affwp_get_affiliate_meta($affiliate_id, 'affiliate_groups', TRUE);

    return $groups;

  }

  // Get a users active groups
  public function get_affiliates_active_groups($affiliate_id) {

    $groups = array();

    $current_groups = affwp_get_affiliate_meta($affiliate_id, 'affiliate_groups', TRUE);
    $all_active_groups = $this->get_all_active_groups();

    if ( !empty($current_groups) ) :
      foreach ($current_groups as $key => $group_id) :

        if ( array_key_exists($group_id, $all_active_groups) ) {
          $groups[$group_id] = $all_active_groups[$group_id]['name'];
        }

      endforeach;
    endif;

    ksort($groups);
    return $groups;

  }

  // Get a users active groups. Public Static
  public static function affiliates_active_groups($affiliate_id) {


    $groups = array();

    $current_groups = affwp_get_affiliate_meta($affiliate_id, 'affiliate_groups', TRUE);
    $all_active_groups = AffiliateWP_Affiliate_Groups_Base::get_all_active_affiliate_groups();

    if ( !empty($current_groups) ) :
      foreach ($current_groups as $key => $group_id) :

        if ( array_key_exists($group_id, $all_active_groups) ) {
          $groups[$group_id] = $all_active_groups[$group_id]['name'];
        }

      endforeach;
    endif;

    return $groups;
  }


  // Check if affiliate in group
  public function is_affiliate_in_group($affiliate_id, $group_id) {

    $aff_groups = get_affiliates_active_groups($affiliate_id);

    if ( array_key_exists($group_id, $aff_groups) ) {
      return TRUE;
    } else {
      return FALSE;
    }

  }

  //////////////////////////////////////////////////////


  // Add a group to a user. Used in functions
  public function add_affiliate_group($user_id, $group_id) {

    $user = get_user_by('id', $user_id);
    $affiliate_id = affwp_get_affiliate_id($user_id);
    $groups = affwp_get_affiliate_meta($affiliate_id, 'affiliate_groups', TRUE);

    if ( !empty($groups) && count($groups) >= 1 ) {

      if ( !in_array($group_id, $groups) ) {
        array_push($groups, $group_id);
      }

    } else {

      $groups = array($group_id);

    }

    affwp_update_affiliate_meta($affiliate_id, 'affiliate_groups', $groups);

    $filter_args = array(
      'group_id' => $group_id
    );
    do_action('affiliate_groups_add_group_to_affiliate', $affiliate_id, $filter_args);


    /*if ( $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_roles'] ) :
      if ( ($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_roles']) == '1' ) {
        $this->sync_groups_user_roles($affiliate_id);
      }
    endif;*/


  }

  public static function add_affiliate_group_static($user_id, $group_id) {

    $user = get_user_by('id', $user_id);
    $affiliate_id = affwp_get_affiliate_id($user_id);
    $groups = affwp_get_affiliate_meta($affiliate_id, 'affiliate_groups', TRUE);

    if ( !empty($groups) && count($groups) >= 1 ) {

      if ( !in_array($group_id, $groups) ) {
        array_push($groups, $group_id);
      }

    } else {

      $groups = array($group_id);

    }

    affwp_update_affiliate_meta($affiliate_id, 'affiliate_groups', $groups);

    $filter_args = array(
      'group_id' => $group_id
    );
    do_action('affiliate_groups_add_group_to_affiliate', $affiliate_id, $filter_args);

    //$setting_key = 'AFFWP_AG_enable_user_roles';
    //$setting = affiliate_wp()->settings->get($setting_key);

    //if ( $setting == '1' ) {
    //AffiliateWP_Affiliate_Groups_Base::sync_groups_user_roles_static($affiliate_id);
    //}

  }

  // Remove a group(role) from a user. Used in functions
  public function remove_affiliate_group($user_id, $group_id) {

    $user = get_user_by('id', $user_id);
    $affiliate_id = affwp_get_affiliate_id($user_id);
    $groups = affwp_get_affiliate_meta($affiliate_id, 'affiliate_groups', TRUE);

    if ( !empty($groups) && count($groups) >= 1 ) {

      if ( in_array($group_id, $groups) ) {
        unset($groups[array_search($group_id, $groups)]);
      }

    }

    affwp_update_affiliate_meta($affiliate_id, 'affiliate_groups', $groups);

    /*if ( $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_roles'] ) :
      if ( ($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_roles']) == '1' ) {
        $this->sync_groups_user_roles($affiliate_id);
      }
    endif;*/

  }

  public function remove_affiliate_group_static($user_id, $group_id) {

    $user = get_user_by('id', $user_id);
    $affiliate_id = affwp_get_affiliate_id($user_id);
    $groups = affwp_get_affiliate_meta($affiliate_id, 'affiliate_groups', TRUE);

    if ( !empty($groups) && count($groups) >= 1 ) {

      if ( in_array($group_id, $groups) ) {
        unset($groups[array_search($group_id, $groups)]);
      }

    }

    affwp_update_affiliate_meta($affiliate_id, 'affiliate_groups', $groups);

    /*$setting_key = 'AFFWP_AG_enable_user_roles';
    $setting = affiliate_wp()->settings->get($setting_key);

    if ( $setting == '1' ) {
      AffiliateWP_Affiliate_Groups_Base::sync_groups_user_roles_static($affiliate_id);
    }*/

  }

  ///////////// Affiliate Functions ////////////

  public function get_affiliate_group_rate_type($type, $affiliate_id) {

    $new_rate_type = $this->get_affiliates_rate($affiliate_id, 'type');

    return (!empty($new_rate_type)) ? $new_rate_type : $type;

  }

  public function get_affiliate_group_rate($rate, $affiliate_id, $type, $reference) {

    $new_rate = $this->get_affiliates_rate($affiliate_id, 'rate');

    if ( !empty($new_rate) && is_numeric($new_rate) ) :

      $new_rate = ('percentage' === $type) ? $new_rate / 100 : $new_rate;
      return $new_rate;

    else:

      return $rate;

    endif;

  }

  // get the affiliates default rate
  public function get_affiliates_rate($affiliate_id, $return = '') {

    // return equals 'type' or value'

    $possible_rates = $this->get_affiliates_possible_rates($affiliate_id);

    //if ( count($possible_rates) >= 1) {
    if ( !empty($possible_rates) ) {

      $rate = $this->get_affiliates_group_priority($affiliate_id, $possible_rates);

      if ( !empty($rate) ) {

        if ( $return == 'type' ) {

          return $rate['rate_type'];

        } elseif ( $return == 'rate' ) {

          return $rate['rate'];

        } else {
          return $rate; // array
        }

      }

    } else {

      return '';

    }

  }

  // Prioritize group
  public function get_affiliates_group_priority($affiliate_id, $possible_rates, $priority = 'default') {

    // Return the highest percentage rate by default, then flat rate.
    if ( $priority == 'default' ) {

      $highest_flat_rate = 0.00;
      $highest_flat_rate_key = '';
      $highest_percentage_rate = 0.00;
      $highest_percentage_rate_key = '';

      foreach ($possible_rates as $group_id => $data) :

        if ( $data['rate_type'] == 'percentage' ) {

          if ( $data['rate'] > $highest_percentage_rate ) {
            $highest_percentage_rate = $data['rate'];
            $highest_percentage_rate_key = $group_id;
          }

        } elseif ( $data['rate_type'] == 'flat' ) {

          if ( $data['rate'] > $highest_flat_rate ) {
            $highest_flat_rate = $data['rate'];
            $highest_flat_rate_key = $group_id;
          }

        }

      endforeach;

      if ( !empty($highest_percentage_rate_key) ) {

        return $possible_rates[$highest_percentage_rate_key];

      } elseif ( !empty($highest_flat_rate_key) ) {

        return $possible_rates[$highest_flat_rate_key];

      }

    }

  }

  // Get all affiliates possible group rates
  private function get_affiliates_possible_rates($affiliate_id) {

    //$user_id = affwp_get_affiliate_user_id($affiliate_id);
    //$active_groups = $this->get_users_active_groups($user_id);
    $active_groups = $this->get_affiliates_active_groups($affiliate_id);

    $rates = array();

    foreach ($active_groups as $key => $name) {
      $rates[$key] = array();
      $rates[$key]['rate_type'] = $this->get_group_settings($key, 'rate_type');
      $rates[$key]['rate'] = $this->get_group_settings($key, 'rate');
    }

    return $rates;
  }

  // Get the affiliates best rate group ID
  private function get_affiliates_best_rate_group($affiliate_id, $amount) {

    $all_rates = $this->get_affiliates_possible_rates($affiliate_id);
    $best_rate_group = '';
    $groups = array();

    foreach ($all_rates as $key => $data) {
      $rate_type = $data['rate_type'];
      $rate = $data['rate'];

      if ( $rate_type == 'percentage' ) {
        $groups[$key] = ((($rate / 100) * $amount));
      } elseif ( $rate_type == 'flat' ) {
        $groups[$key] = $rate;
      }

    }

    arsort($groups);
    $key = key($groups);
    if ( !empty($key) ) $best_rate_group = $key;
    return $best_rate_group;

  }

  ///////////// Calculation Functions //////////////

  // Calculate the referral amount
  public function calc_referral_amount($referral_amount, $affiliate_id, $amount, $reference, $product_id) {

    $best_rate_group = $this->get_affiliates_best_rate_group($affiliate_id, $amount);

    // Get the global group referral amount
    $global_group_amount = '';
    if ( !empty($best_rate_group) ) {

      // New way
      $new_rate_vars = array(
        'new_rate' => $this->get_group_settings($best_rate_group, 'rate'),
        'new_type' => $this->get_group_settings($best_rate_group, 'rate_type'),
        'amount' => $amount,
      );

      $new_rate_vars = apply_filters('aff_groups_rate_vars', $new_rate_vars, $referral_amount, $affiliate_id, $amount, $reference, $product_id);

      $global_group_amount = $this->calculate_referral($new_rate_vars['new_type'], $new_rate_vars['new_rate'], $new_rate_vars['amount']);

    }

    if ( (isset($global_group_amount)) && (!empty($global_group_amount)) ) {
      $new_referral_amount = $global_group_amount;
    } else {
      $new_referral_amount = $referral_amount;
    }

    return apply_filters('aff_groups_referral_amount', $new_referral_amount, $affiliate_id, $amount, $reference, $product_id);

  }

  // Do the actual calculation
  public function calculate_referral($new_type, $new_rate, $amount) {
    if ( $new_type == 'percentage' ) {

      if ( $new_rate > 1 ) {
        $new_rate = $new_rate / 100;
      }

      $new_referral_amount = ($amount * $new_rate);

    } elseif ( $new_type == 'flat' ) {

      $new_referral_amount = $new_rate;

    }

    return $new_referral_amount;
  }


  ////////////// HTML Funtions //////////////

  // New affilate form HTML
  public function add_groups_to_new_affiliate_form() {

    ob_start(); ?>

    <table class="form-table">
      <tbody>

      <tr class="form-row">

        <th scope="row">
          <label for="rate"><?php _e('Affiliate Groups', 'affiliate-wp-affiliate-groups'); ?></label>
        </th>

        <td>
          <?php
          $groups = $this->get_all_active_groups();
          $option = '0';
          foreach ($groups as $key => $atts) {
            //print_r($atts);
            ?>
            <label>
              <input type="checkbox" name="<?php echo $key; ?>" value="1" id="affiliate_groups_<?php echo $option; ?>">
              <?php
              echo $atts['name'] . ' - <strong>' . $this->group_rate_html($key) . '</strong>';
              ?>
            </label>
            <br>
            <?php
            ++$option;
          } ?>

          <?php do_action('aff_groups_new_aff_before_desc'); ?>

          <p class="description">
            <?php __('If an affiliate is a member of multiple groups, the highest rate will apply.<br>Group rates have priority over the \'Referral Rate\' set above', 'affiliate-wp-affiliate-groups'); ?>
          </p>

          <?php do_action('aff_groups_new_aff_after_desc'); ?>

        </td>

      </tr>

      </tbody>
    </table>

    <?php
    $content = ob_get_contents();
    ob_end_clean();
    echo $content;

  }

  // Remove groups from edit profile roles list
  public function remove_groups_from_edit_user_profile_roles($all_roles) {

    $all_groups = $this->get_all_groups();

    if ( !empty($all_groups) ) {

      foreach ($all_groups as $key => $data) {
        if ( array_key_exists($key, $all_roles) ) {
          unset($all_roles[$key]);
        }
      }

    }

    return $all_roles;
  }

  // Remove groups from user page views
  public function remove_groups_from_user_views($views) {

    if ( !empty($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_views']) ) :

      if ( $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_views'] != '1' ) {

        $groups = $this->get_all_active_groups();

        foreach ($groups as $group_id => $atts) {
          unset($views[$group_id]);
        }
      }

    endif;

    return $views;
  }

  // Add fields and data to the edit affilate page
  public function add_groups_to_affiliate_page($affiliate) {

    $affiliate_id = $affiliate->affiliate_id;
    $user_id = affwp_get_affiliate_user_id($affiliate_id);

    // Only output fields for an active affiliate
    //if(affwp_is_affiliate($user_id)) {

    //if(affwp_is_active_affiliate($affiliate_id)) {

    $active_groups = $this->get_all_active_groups();

    ob_start(); ?>

    <table class="form-table">
      <tbody>

      <tr class="form-row">

        <th scope="row">
          <label for="account-email"><?php _e('Affiliate Groups', 'affiliate-wp-affiliate-groups'); ?></label>
        </th>

        <td>

          <?php
          $option = '0';
          foreach ($active_groups as $key => $data) {

            $group_type = $this->get_group_settings($key, 'rate_type');
            $group_rate = $this->get_group_settings($key, 'rate');

            ?>
            <label>
              <input type="checkbox" name="<?php echo $key; ?>" value="1" id="affiliate_groups_<?php echo $option; ?>"
                <?php if ( $this->is_affiliate_in_group($affiliate_id, $key) ) echo 'checked="checked"'; ?>>
              <?php
              echo $data['name'] . ' - <strong>' .
                apply_filters('aff_groups_edit_aff_group_html', $this->group_rate_html($key, $affiliate), $key, $affiliate) .
                '</strong>';
              ?>
              <?php do_action('aff_groups_edit_aff_after_group_option', $key, $affiliate); ?>
            </label>
            <br>
            <?php
            ++$option;
          } ?>

          <?php do_action('aff_groups_edit_aff_before_desc', $affiliate); ?>

          <p class="description">
            <?php __('If an affiliate is a member of multiple groups, the highest rate will apply.<br>Group rates have priority over the \'Referral Rate\' set above', 'affiliate-wp-affiliate-groups'); ?>
          </p>

          <?php do_action('aff_groups_edit_aff_after_desc', $affiliate); ?>

        </td>

      </tr>

      </tbody>
    </table>

    <?php
    $content = ob_get_contents();
    ob_end_clean();
    echo $content;

    //}

    //}

  }

  // Generate a groups rate HTML
  public function group_rate_html($group_id, $affiliate = '') {

    if ( is_object($affiliate) ) $affiliate_id = $affiliate->affiliate_id;

    $rate_types = affwp_get_affiliate_rate_types();
    $group_type = $this->get_group_settings($group_id, 'rate_type');
    $group_rate = $this->get_group_settings($group_id, 'rate');

    $html = '';

    if ( $group_type == 'percentage' ) {

      $html = $group_rate . '%';

    } elseif ( $group_type == 'flat' ) {

      $html = $rate_types['flat'] . ': ' . $group_rate;
      $html = affwp_currency_filter($group_rate);
    }

    $filter_data = array(
      'group_id' => $group_id,
      'group_type' => $group_type,
      'group_rate' => $group_rate,
    );

    if ( !empty($affiliate) ) :

      $filter_data['affiliate_id'] = $affiliate_id;
      $filter_data['affiliate_obj'] = $affiliate;

    endif;

    return apply_filters('aff_groups_rate_html', $html, $filter_data);
  }

  ///////////// Processing POST Data functions ////////////////

  // Process edit affiliate form
  public function process_edit_affiliate_form() {

    if ( isset($_REQUEST['affwp_action']) && $_REQUEST['affwp_action'] == 'update_affiliate' ) {

      // Required because method called from function prior to the plugin loading
      //$this->plugin_config['plugin_prefix'] = 'AFFWP_AG';
      //$this->plugin_settings = get_option('affwp_settings');

      $affiliate_id = $_REQUEST['affiliate_id'];

      $user_id = affwp_get_affiliate_user_id($affiliate_id);

      // Add the affiliate groups
      $groups = $this->get_all_groups();

      foreach ($groups as $key => $atts) {
        if ( (isset($_POST[$key])) && ($_POST[$key] == '1') ) {
          $this->add_affiliate_group($user_id, $key);
        } else {
          $this->remove_affiliate_group($user_id, $key);
        }
      }

    }

    /*if ( ($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_enable_user_roles']) == '1' ) {
      $this->sync_groups_user_roles($affiliate_id);
    }*/

  }

  // Add affiliate groups after new affilate
  public function process_new_affiliate_form($add) {

    // Check if user can edit the profile
    if ( current_user_can('edit_users') ) {

      // Required because method called from function prior to the plugin loading
      //$this->plugin_config['plugin_prefix'] = 'AFFWP_AG';
      //$this->plugin_settings = get_option('affwp_settings');

      if ( !empty($add) ) {

        $user_id = affwp_get_affiliate_user_id($add);
        global $_REQUEST;

        $groups = $this->get_all_groups();

        foreach ($groups as $key => $atts) {
          if ( (isset($_REQUEST[$key])) && ($_REQUEST[$key] == '1') ) {
            $this->add_affiliate_group($user_id, $key);
          }
        }
      }

    }
  }


  // Auto grouping after registration
  public function affiliate_auto_grouping_registration($affiliate_id) {

    // Required because method called from function prior to the plugin loading
    $this->plugin_config['plugin_prefix'] = 'AFFWP_AG';
    $this->plugin_settings = get_option('affwp_settings');

    if ( ($this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_auto_grouping_enable']) == '1' ) :

      $mode = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_auto_grouping_mode'];

      // Default for older versions
      if ( empty($mode) ) $mode = 'list';

      $user_id = affwp_get_affiliate_user_id($affiliate_id);
      $referrer_id = affiliate_wp()->tracking->get_affiliate_id();

      if ( $mode == 'clone_parent_strict' ) {

        if ( !empty($referrer_id) ) {

          //$groups = $this->get_users_roles( affwp_get_affiliate_user_id( $referrer_id ) );
          $groups = $this->get_affiliates_groups($referrer_id);

          if ( !empty($groups) ) {
            foreach ($groups as $key => $value) {
              $this->add_affiliate_group($user_id, $value);
            }
          }

        }

      } elseif ( $mode == 'clone_parent_fallback' ) {

        if ( !empty($referrer_id) ) {

          //$groups = $this->get_users_roles( affwp_get_affiliate_user_id( $referrer_id ) );
          $groups = $this->get_affiliates_groups($referrer_id);

          if ( !empty($groups) ) {
            foreach ($groups as $key => $value) {
              $this->add_affiliate_group($user_id, $value);
            }
          }

        } else {

          $groups = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_auto_grouping_options'];

          if ( !empty($groups) ) {
            foreach ($groups as $key => $value) {
              $this->add_affiliate_group($user_id, $key);
            }
          }

        }

      } elseif ( $mode == 'list' ) {

        $groups = $this->plugin_settings[$this->plugin_config['plugin_prefix'] . '_auto_grouping_options'];

        if ( !empty($groups) ) {
          foreach ($groups as $key => $value) {
            $this->add_affiliate_group($user_id, $key);
          }
        }

      }

    endif;

  }

  /// Affiliates List
  // $this AffWP_Affiliates_Table
  public function affiliate_table_columns($prepared_columns, $columns) {

    $new_list = array();

    foreach ($prepared_columns as $column_id => $column_name) {

      $new_list[$column_id] = $column_name;

      if ( $column_id == 'unpaid_earnings' ) :

        $new_list['group'] = 'Groups';

      endif;

    }

    return $new_list;

  }

  // Get the affiliate group for the admin list
  public function affwp_affiliate_table_group($value, $affiliate) {

    $affiliate_id = $affiliate->affiliate_id;

    $groups_string = $this->get_affiliates_groups_string($affiliate_id);

    return $groups_string;

  }

  // Add Affiliates Group to the admin chart
  public function mla_custom_chart_info($chart_info, $affiliate_id, $chart_location) {

    //if ( $chart_location == 'admin_area' ) :

    $groups = $this->get_affiliates_groups_string($affiliate_id);

    if ( !empty($groups) )  :

      $chart_info[__('Groups', 'affiliatewp-multi-level-affiliates')] = $this->get_affiliates_groups_string($affiliate_id);

    endif;

    //endif;

    return $chart_info;

  }

  // Add affiliates groups to the MLA chart node class
  public function mla_chart_node_attributes($node_data, $affiliate_id, $chart_location) {

    $class_string = '';

    $groups = get_affiliates_active_groups($affiliate_id);

    if ( !empty($groups) ) :

      $class_string .= ' groups';

      foreach ($groups as $group_id => $group_name) :

        $class_string .= ' ' . $group_id;

      endforeach;

      $node_data['className'] = $node_data['className'] . $class_string;

    endif;

    return $node_data;

  }


  // Get a affiliates group/s to display
  public function get_affiliates_groups_string($affiliate_id) {

    $groups_string = '';

    $groups = get_affiliates_active_groups($affiliate_id);
    $max_groups = count($groups);

    if ( !empty($groups) ) :

      $count = 1;
      foreach ($groups as $group_id => $group_name) :

        $groups_string .= $group_name;
        if ( $max_groups > $count ) $groups_string .= ', ';

        $count++;
      endforeach;

    endif;

    return $groups_string;

  }

  // Filter the affiliate table rate HTML
  public function get_affiliate_table_rate($value, $affiliate) {

    //print_r( $affiliate );
    $affiliate_id = $affiliate->affiliate_id;

    $possible_rates = $this->get_affiliates_possible_rates($affiliate_id);
    //print_r($possible_rates);

    if ( count($possible_rates) >= 1 ) {

      $rates_html = array();
      $highest_flat_rate = 0.00;
      $highest_percentage_rate = 0.00;

      foreach ($possible_rates as $group_id => $data) :

        if ( $data['rate_type'] == 'percentage' ) {

          if ( $data['rate'] > $highest_percentage_rate ) {
            $highest_percentage_rate = $data['rate'];
            $rates_html['percentage'] = $this->group_rate_html($group_id);
          }

        } elseif ( $data['rate_type'] == 'flat' ) {

          if ( $data['rate'] > $highest_flat_rate ) {
            $highest_flat_rate = $data['rate'];
            $rates_html['flat'] = $this->group_rate_html($group_id);
          }

        }

      endforeach;

      if ( !empty($rates_html) ) :

        krsort($rates_html);

        $html = '';

        foreach ($rates_html as $rate_html) :
          $html .= $rate_html . '<br>';
        endforeach;

        return $html;

      endif;


    } else {

      return $value;

    }
  }
  //// End Affiliates List


  // Plugin test
  public function plugin_test_function() {
    //$affiliate_id = '148';
  }


} // End of class

?>