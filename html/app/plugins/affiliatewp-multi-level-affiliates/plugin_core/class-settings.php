<?php

class AffiliateWP_Multi_Level_Affiliates_Settings {

  protected $plugin_config;
  public $plugin_settings;

  public function __construct() {

    /*
    if (defined('AFFWP_MLA_PLUGIN_CONFIG')) {
      $this->plugin_config = unserialize(AFFWP_MLA_PLUGIN_CONFIG);
    }else{
      $this->plugin_config = array();
    }
    if (defined('AFFWP_MLA_PLUGIN_SETTINGS')) {
      $this->plugin_settings = unserialize(AFFWP_MLA_PLUGIN_SETTINGS);
    }else{
      $this->plugin_settings = array();
    }
    */
    $this->plugin_config = affiliate_wp_mla()->plugin_config;

    $this->plugin_settings = $this->get_all_settings();

    // Set the global matrix default settings
    $this->set_global_matrix_defaults();

    // Plugin upgrade tasks
    $this->plugin_upgrade_tasks();

    if ( $this->check_if_settings_page() ) {
      add_action('admin_init', array($this, 'deactivate_license'), 1);
      // Run the MLM migration
      do_action('mlm_mla_migration');
    }

    // New settings tab & settings
    add_filter('affwp_settings_tabs', array($this, 'settings_tab'));
    add_filter('affwp_settings', array($this, 'settings'), 10, 1);

    // Fancy settings UI
    add_action('admin_init', array($this, 'save_matrix_settings'), 1);
    add_action('admin_init', array($this, 'output_matrix_settings'));

    // Affiliate Admin
    // Add group options to add new affiliate form
    add_action('affwp_new_affiliate_end', array($this, 'add_parent_id_to_new_affiliate_form'));

    // Add fields and data to the edit affiliate page
    add_action('affwp_edit_affiliate_end', array($this, 'add_parent_id_to_affiliate_page'), 98);

  }

  // Plugin upgrade tasks
  public function plugin_upgrade_tasks() {

    $previous_version = get_site_option($this->plugin_config['plugin_prefix'] . '_version');
    $new_version = $this->plugin_config['plugin_version'];

    if ( !empty($previous_version) ) {

      // update lps
      if ( $previous_version < '1.3.5' )  :
        update_site_option($this->plugin_config['plugin_prefix'] . '_lps', 1);
      endif;

      // update db version
      if ( $previous_version != $new_version ) :
        update_site_option($this->plugin_config['plugin_prefix'] . '_version', $this->plugin_config['plugin_version']);
      endif;

    } else {

      update_site_option($this->plugin_config['plugin_prefix'] . '_version', $this->plugin_config['plugin_version']);

    }

  }

  // Check if on the plugins settings page
  public function check_if_settings_page() {
    if ( isset($_GET['tab']) && $_GET['tab'] == $this->plugin_config['plugin_prefix'] ) return TRUE;
  }

  // Create the settings tab
  public function settings_tab($tabs) {
    $tabs[$this->plugin_config['plugin_prefix']] = __('MLA', 'affiliatewp-multi-level-affiliates');
    return $tabs;
  }

  // Get all settings
  public function get_all_settings() {

    if ( class_exists('Affiliate_WP') ) {
      $options = affiliate_wp()->settings->get_all();
    }

    // Add the standard
    $settings = $this->settings(array());
    foreach ($settings[$this->plugin_config['plugin_prefix']] as $key => $value) {

      if ( class_exists('Affiliate_WP') ) {
        if ( !empty($options[$key]) ) {
          $thesettings[$key] = $options[$key];
        }
      }
    }

    // Add the custom matrix settings
    $matrix_settings_list = $this->matrix_settings_list();
    foreach ($matrix_settings_list as $key => $data) {

      if ( class_exists('Affiliate_WP') ) {

        if ( !empty($options[$this->plugin_config['plugin_prefix'] . '_' . $key]) ) {
          $thesettings[$this->plugin_config['plugin_prefix'] . '_' . $key] = $options[$this->plugin_config['plugin_prefix'] . '_' . $key];
        } else {
          $thesettings[$this->plugin_config['plugin_prefix'] . '_' . $key] = '';
        }

      }

    }

    return $thesettings;

  }

  // Generate the form settings
  public function settings($settings) {

    if ( is_admin() && $this->check_if_settings_page() ) {
      $license_msg = $this->license_status_msg();
      $license_msg = (!empty($license_msg)) ? $license_msg : '';
    } else {
      $license_msg = '';
    }

    $settings2 = array(
      $this->plugin_config['plugin_prefix'] => apply_filters('affwp_settings_mla',
        array(
          $this->plugin_config['plugin_prefix'] . '_section_licensing' => array(
            'name' => '<strong>' . __('License Settings', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),

          $this->plugin_config['plugin_prefix'] . '_license_key' => array(
            'name' => __('License Key', 'affiliatewp-multi-level-affiliates'),
            'desc' => $license_msg,
            'type' => 'text',
            'disabled' => $this->is_license_valid()
          ),

          $this->plugin_config['plugin_prefix'] . '_section_general_settings' => array(
            'name' => '<strong>' . __('General Settings', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),
          $this->plugin_config['plugin_prefix'] . '_enable_mla' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Enable MLA', 'affiliatewp-multi-level-affiliates') . '</strong><p>' . __('Enable or disable all MLA features', 'affiliatewp-multi-level-affiliates') . '</p>',
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_section_integrations_settings' => array(
            'name' => '<strong>' . __('Integrations', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),
          $this->plugin_config['plugin_prefix'] . '_buddypress_enable' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Add dashboard tab to BuddyPress settings', 'affiliatewp-multi-level-affiliates'),
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_recurring_referrals_enable' => array(
            'name' => __('', 'affiliatewp-multi-level-affiliates'),
            'desc' => '<strong>' . __('Enable Recurring Referrals', 'affiliatewp-multi-level-affiliates') . '</strong><p>' . __('Integrate MLA with the Recurring Referrals add-on', 'affiliatewp-multi-level-affiliates') . '</p>',
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_section_chart_settings' => array(
            'name' => '<strong>' . __('Chart Settings', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),
          $this->plugin_config['plugin_prefix'] . '_enable_chart_pan' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Enable Pan by default', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_enable_chart_zoom' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Enable Zoom by default', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_chart_levels' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Number of levels to display. Defaults to the Maxtrix Payment Levels', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'text',
          ),
          $this->plugin_config['plugin_prefix'] . '_section_dashboard_settings1' => array(
            'name' => '<strong>' . __('Dashboard Tab 1', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_tab_enable' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Enable Dashboard Tab 1', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_tab_title' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Tab 1 title', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'text',
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_features' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Tab 1 features', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'multicheck',
            'options' => array(
              'rates' => __('Affiliate Commission Rates', 'affiliatewp-multi-level-affiliates'),
              'earnings' => __('Affiliate Earnings', 'affiliatewp-multi-level-affiliates'),
              'network_statistics' => __('Network Statistics', 'affiliatewp-multi-level-affiliates'),
              'network_chart' => __('Network chart', 'affiliatewp-multi-level-affiliates'),
              /*'reports' => __( 'Reports', '' ),*/
            ),
          ),
          $this->plugin_config['plugin_prefix'] . '_section_dashboard_settings2' => array(
            'name' => '<strong>' . __('Dashboard Tab 2', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_tab_2_enable' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Enable Dashboard Tab 2', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_tab_2_title' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Tab 2 title', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'text',
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_2_features' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Tab 2 features', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'multicheck',
            'options' => array(
              'rates' => __('Affiliate Commission Rates', 'affiliatewp-multi-level-affiliates'),
              'earnings' => __('Affiliate Earnings', 'affiliatewp-multi-level-affiliates'),
              'network_statistics' => __('Network Statistics', 'affiliatewp-multi-level-affiliates'),
              'network_chart' => __('Network chart', 'affiliatewp-multi-level-affiliates'),
              /*'reports' => __( 'Reports', '' ),*/
            ),
          ),
          $this->plugin_config['plugin_prefix'] . '_section_dashboard_settings3' => array(
            'name' => '<strong>' . __('Dashboard Tab 3', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_tab_3_enable' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Enable Dashboard Tab 3', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'checkbox'
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_tab_3_title' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Tab 3 title', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'text',
          ),
          $this->plugin_config['plugin_prefix'] . '_dashboard_3_features' => array(
            'name' => __('', ''),
            'desc' => '<strong>' . __('Tab 3 features', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'type' => 'multicheck',
            'options' => array(
              'rates' => __('Affiliate Commission Rates', 'affiliatewp-multi-level-affiliates'),
              'earnings' => __('Affiliate Earnings', 'affiliatewp-multi-level-affiliates'),
              'network_statistics' => __('Network Statistics', 'affiliatewp-multi-level-affiliates'),
              'network_chart' => __('Network chart', 'affiliatewp-multi-level-affiliates'),
              /*'reports' => __( 'Reports', '' ),*/
            ),
          ),
          $this->plugin_config['plugin_prefix'] . '_section_matrix_settings' => array(
            'name' => '<strong>' . __('Matrix Settings', 'affiliatewp-multi-level-affiliates') . '</strong>',
            'desc' => '',
            'type' => 'header'
          ),

        )
      )
    );

    $settings2 = $this->check_integration_settings($settings2);


    // Merge settings
    $settings = array_merge($settings, $settings2);

    return $settings;
  }

  // Remove integration settings when not required
  public function check_integration_settings($settings2) {

    // AffiliateWP BuddyPress
    if ( !class_exists('AffiliateWP_BuddyPress') ) :
      unset($settings2[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'] . '_buddypress_enable']);
    endif;

    // AffiliateWP Recurring Referrals
    if ( !class_exists('AffiliateWP_Recurring_Referrals') ) :
      unset($settings2[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'] . '_recurring_referrals_enable']);
    endif;


    // Integrations Section
    if (
      !class_exists('AffiliateWP_BuddyPress') &&
      !class_exists('AffiliateWP_Recurring_Referrals')
    ) :
      unset($settings2[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'] . '_section_integrations_settings']);
    endif;

    return $settings2;
  }

  // Remove integration settings if not required
  public function check_product_integration_settings($settings, $id) {

    $enabled_integrations = apply_filters('affwp_enabled_integrations', affiliate_wp()->settings->get('integrations', array()));

    // WooCommerce
    if ( array_key_exists('woocommerce', $enabled_integrations) ) {
      return $settings;

    } else {

      unset($settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'] . '_' . $id . '_enable_product_rates']);
      return $settings;

    }

  }


  ////////////////////////////////////////////////////////////////////////

  // Maxtix settings methods

  // Set the global matrix default
  public function set_global_matrix_defaults() {

    $options = affiliate_wp()->settings->get_all();

    // AffiliateWP default settings
    $default_type = (isset($options['referral_rate_type']) && !empty($options['referral_rate_type'])) ? $options['referral_rate_type'] : 'percentage';
    $default_rate = (isset($options['referral_rate']) && !empty($options['referral_rate'])) ? $options['referral_rate'] : '20';

    $group_depth = $this->get_matrix_setting('matrix_depth', 'default');
    //$group_rate = $this->get_matrix_setting( 'rate_value', 'default' );
    $level_1_rate = $this->get_matrix_setting('level_1_rate', 'default');
    $group_type = $this->get_matrix_setting('rate_type', 'default');

    if ( empty($group_depth) ) $this->update_matrix_setting('matrix_depth', '1', 'default');
    //if( empty($group_rate) ) $this->update_matrix_setting( 'rate_value', $default_rate, 'default');
    if ( empty($level_1_rate) && $level_1_rate != 0 ) $this->update_matrix_setting('level_1_rate', $default_rate, 'default');
    if ( empty($group_type) ) $this->update_matrix_setting('rate_type', $default_type, 'default');

    // Generate defaults for affiliate groups. Backup only. Set via action in groups
    if ( class_exists('AffiliateWP_Affiliate_Groups') ) {
      $this->set_affiliate_group_settings();
    }

  }

  // Add default Matrix settings for new affiliate group
  public function set_affiliate_group_settings($active_groups = array()) {

    if ( (!count($active_groups) >= 1) ) $active_groups = get_active_affiliate_groups();

    $options = affiliate_wp()->settings->get_all();
    $default_type = (!empty($options['referral_rate_type'])) ? $options['referral_rate_type'] : '';
    $default_rate = (!empty($options['referral_rate'])) ? $options['referral_rate'] : '';

    if ( !empty($active_groups) ) {

      foreach ($active_groups as $group_id => $data) {

        $group_mla_mode = get_affiliate_group_setting('mla_mode', $group_id);

        $group_rate_type = get_affiliate_group_setting('rate_type', $group_id);
        $group_rate_type = (!empty($group_rate_type)) ? $group_rate_type : $default_type;

        $group_rate_value = get_affiliate_group_setting('rate', $group_id);
        $group_rate_value = (!empty($group_rate_value)) ? $group_rate_value : $default_rate;

        if ( $group_mla_mode == 'enabled_extended' ) {

          $current_matrix_depth = $this->get_matrix_setting('matrix_depth', $group_id);
          $current_rate_type = $this->get_matrix_setting('rate_type', $group_id);
          //$current_rate_value = $this->get_matrix_setting( 'rate_value', $group_id );
          $current_rate_value = $this->get_matrix_setting('level_1_rate', $group_id);

          if ( empty($current_matrix_depth) ) $this->update_matrix_setting('matrix_depth', '1', $group_id);
          if ( empty($current_rate_type) ) $this->update_matrix_setting('rate_type', $group_rate_type, $group_id);
          //if( empty( $current_rate_value ) ) $this->update_matrix_setting( 'rate_value', $group_rate_value, $group_id );
          if ( empty($current_rate_value) && $current_rate_value != '0' ) $this->update_matrix_setting('level_1_rate', $group_rate_value, $group_id);


        }

      }

    }

  }

  // Get a Matrix setting. Public Static. Tested
  public static function get_matrix_setting($setting_key, $group_id) {

    /*
    if (defined('AFFWP_MLA_PLUGIN_CONFIG')) {
      $plugin_config = unserialize(AFFWP_MLA_PLUGIN_CONFIG);
    }else{
      $plugin_config = array();
    }
    */
    $plugin_config = affiliate_wp_mla()->plugin_config;


    if ( empty($plugin_config['plugin_prefix']) ) $plugin_config['plugin_prefix'] = 'AFFWP_MLA';

    $options = affiliate_wp()->settings->get_all();

    if ( isset($options[$plugin_config['plugin_prefix'] . '_' . $setting_key . '_' . $group_id]) && (!empty($options[$plugin_config['plugin_prefix'] . '_' . $setting_key . '_' . $group_id]) || $options[$plugin_config['plugin_prefix'] . '_' . $setting_key . '_' . $group_id] == 0) ) {

      return $options[$plugin_config['plugin_prefix'] . '_' . $setting_key . '_' . $group_id];

    } else {

      return NULL;

    }

  }

  // Update a Matrix setting. Public Static
  public static function update_matrix_setting($setting_key, $value, $group_id) {

    /*
    if (defined('AFFWP_MLA_PLUGIN_CONFIG')) {
      $plugin_config = unserialize(AFFWP_MLA_PLUGIN_CONFIG);
    }else{
      $plugin_config = array();
    }
    */
    $plugin_config = affiliate_wp_mla()->plugin_config;

    if ( empty($plugin_config['plugin_prefix']) ) $plugin_config['plugin_prefix'] = 'AFFWP_MLA';

    affiliate_wp()->settings->set(array($plugin_config['plugin_prefix'] . '_' . $setting_key . '_' . $group_id => $value), TRUE);

  }

  // Delete a Matrix setting. Public Static
  public static function delete_matrix_setting($setting_key, $group_id) {

    /*
    if (defined('AFFWP_MLA_PLUGIN_CONFIG')) {
      $plugin_config = unserialize(AFFWP_MLA_PLUGIN_CONFIG);
    }else{
      $plugin_config = array();
    }
    */
    $plugin_config = affiliate_wp_mla()->plugin_config;

    str_replace($plugin_config['plugin_prefix'] . '_', '', $setting_key);

    $options = affiliate_wp()->settings->get_all();

    unset($options[$plugin_config['plugin_prefix'] . '_' . $setting_key . '_' . $group_id]);

    update_option('affwp_settings', $options);

  }


  // Get affiliate groups
  public function get_activated_groups() {

    $groups = array();

    if ( class_exists('AffiliateWP_Affiliate_Groups') ) {
      $groups = get_active_affiliate_groups();

      return $groups;
    }

  }

  // Save the Matrix fields
  public function save_matrix_settings() {

    if ( isset($_POST['mla_process_matrix']) && !empty($_POST['mla_process_matrix']) ) {

      $options = array();
      //$options = affiliate_wp()->settings->get_all();

      $form_fields = $this->matrix_settings_list();

      foreach ($form_fields as $name => $data) {

        //if( isset( $_POST[$this->plugin_config['plugin_prefix'].'_'.$name] ) ) : // new

        $field = $_POST[$this->plugin_config['plugin_prefix'] . '_' . $name];

        if ( $data['saniitize'] ) {
          $sanitize_function = 'sanitize_' . $data['type'];
          $field = $sanitize_function($field);
        }

        $options[$this->plugin_config['plugin_prefix'] . '_' . $name] = $field;

        //endif;

      }

      // Get current and new commission levels
      //$current_levels = $this->get_matrix_setting( 'matrix_depth', $group_id );
      //$new_levels = $_POST[$this->plugin_config['plugin_prefix'].'_matrix_depth_default'];

      affiliate_wp()->settings->set($options, TRUE);
      //update_option( 'affwp_settings', $options );

      // Regenerate all level data if levels change.
      //if( $current_levels != $new_levels ) :
      require_once plugin_dir_path(__FILE__) . 'class-common.php';
      require_once plugin_dir_path(__FILE__) . 'class-affiliate.php';
      $affiliate_obj = new AffiliateWP_MLA_Affiliate(-1);
      $restructure = $affiliate_obj->restructure_entire_mla_network();
      //endif;

    }

  }

  // Register the Matrix settings as a field
  public function output_matrix_settings() {

    add_settings_field(
      $this->plugin_config['plugin_prefix'] . 'AFFWP_MLA_matrix_settings_section',
      __('', ''),
      array($this, 'output_matrix_tables'),
      'affwp_settings_' . $this->plugin_config['plugin_prefix'],
      'affwp_settings_' . $this->plugin_config['plugin_prefix']
    );

  }

  // Generate the Matrix settings tables
  public function output_matrix_tables() {

    echo $this->get_matrix_settings_table(array('id' => 'default', 'section_title' => 'Global Settings'));

    $groups = $this->get_activated_groups();

    if ( !empty($groups) ) {

      // Loop groups
      foreach ($groups as $id => $group_data) {

        // Check if group is enabled for settings
        if ( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) {
          echo $this->get_matrix_settings_table(array('id' => $id, 'section_title' => $group_data['name'] . ' Settings'));
        }
      }
    }

  }

  // Transform matrix settings for default and groups
  public function transform_matrix_settings_list($matrix_settings_list) {

    $transformed_list = array();

    // Add the main matrix settings
    foreach ($matrix_settings_list as $name => $data) {
      $key = $name . '_default';
      $transformed_list[$key] = $data;
    }

    // Add the rate levels
    $default_depth = $this->get_matrix_setting('matrix_depth', 'default');
    $levels = ($default_depth >= 1) ? $default_depth : 1;
    for ($x = 1; $x <= $levels; $x++) :

      $transformed_list['level_' . $x . '_rate_default'] = array(
        'type' => 'text_field',
        'label' => 'Default Rate Value',
        'saniitize' => (bool)TRUE,
      );

    endfor;

    $groups = $this->get_activated_groups();

    // Add the matrix settings for each group
    if ( !empty($groups) ) :

      foreach ($groups as $id => $group_data) :

        // Add main settings
        foreach ($matrix_settings_list as $name => $data) {
          $key = $name . '_' . $id;
          $transformed_list[$key] = $data;
        }

        // Add the level rates
        //$default_depth = $this->get_matrix_setting( 'matrix_depth', 'default');
        $default_depth = $this->get_matrix_setting('matrix_depth', $id);
        $levels = ($default_depth >= 1) ? $default_depth : 1;
        for ($x = 1; $x <= $levels; $x++) :

          $transformed_list['level_' . $x . '_rate_' . $id] = array(
            'type' => 'text_field',
            'label' => 'Group Rate Value',
            'saniitize' => (bool)TRUE,
          );

        endfor;

      endforeach;

    endif;

    return $transformed_list;

  }


  // The raw matrix settings array
  public function matrix_settings_list($type = '') {

    $matrix_settings_list = array(

      'matrix_type' => array(
        'type' => 'check_box',
        'label' => 'Matrix Type',
        'saniitize' => (bool)FALSE,
      ),
      'allow_spillover' => array(
        'type' => 'check_box',
        'label' => 'Matrix Type',
        'saniitize' => (bool)FALSE,
      ),
      'matrix_default_parent' => array(
        'type' => 'text_field',
        'label' => 'Default Parent Affiliate',
        'saniitize' => (bool)TRUE,
      ),
      'matrix_width' => array(
        'type' => 'text_field',
        'label' => 'Matrix Width',
        'saniitize' => (bool)TRUE,
      ),
      'matrix_depth' => array(
        'type' => 'text_field',
        'label' => 'Matrix Depth',
        'saniitize' => (bool)TRUE,
      ),
      'matrix_branches' => array(
        'type' => 'text_field',
        'label' => 'Matrix Branches',
        'saniitize' => (bool)TRUE,
      ),
      'direct_referral_mode' => array(
        'type' => 'option',
        'label' => 'Direct Referral Mode',
        'saniitize' => (bool)FALSE,
      ),
      'level_one_affiliate' => array(
        'type' => 'option',
        'label' => 'Level 1 Affiliate',
        'saniitize' => (bool)FALSE,
      ),
      'rate_type' => array(
        'type' => 'option',
        'label' => 'Default Rate Type',
        'saniitize' => (bool)FALSE,
      ),
      /*'rate_value' => array(
        'type' => 'text_field',
        'label' => 'Default Rate Value',
        'saniitize' => (bool) TRUE,
      ),*/
      'enable_direct_referral' => array(
        'type' => 'check_box',
        'label' => 'Enable Direct Referral',
        'saniitize' => (bool)FALSE,
      ),
      'direct_referral_rate_type' => array(
        'type' => 'option',
        'label' => 'Direct Referral Rate Type',
        'saniitize' => (bool)FALSE,
      ),
      'direct_referral_rate' => array(
        'type' => 'text_field',
        'label' => 'Direct Referral Rate Value',
        'saniitize' => (bool)TRUE,
      ),
      'team_leader_mode' => array(
        'type' => 'option',
        'label' => 'Team Leader Mode',
        'saniitize' => (bool)FALSE,
      ),
      'team_leader_rate_type' => array(
        'type' => 'option',
        'label' => 'Team Leader Rate Type',
        'saniitize' => (bool)FALSE,
      ),
      'team_leader_min_level' => array(
        'type' => 'text_field',
        'label' => 'Team Leader Min Level',
        'saniitize' => (bool)TRUE,
      ),
      'team_leader_max_level' => array(
        'type' => 'text_field',
        'label' => 'Team Leader Max Level',
        'saniitize' => (bool)TRUE,
      ),
      'team_leader_rate_value' => array(
        'type' => 'text_field',
        'label' => 'Team Leader Rate Value',
        'saniitize' => (bool)TRUE,
      ),
      'team_leader_rate_override' => array(
        'type' => 'check_box',
        'label' => 'Team Leader Rate Override',
        'saniitize' => (bool)FALSE,
      ),
      'team_leader_single_only' => array(
        'type' => 'check_box',
        'label' => 'Team Leader Single Only',
        'saniitize' => (bool)FALSE,
      ),
      'steam_leader_mode' => array(
        'type' => 'option',
        'label' => 'Super Team Leader Mode',
        'saniitize' => (bool)FALSE,
      ),
      'steam_leader_rate_type' => array(
        'type' => 'option',
        'label' => 'Super Team Leader Rate Type',
        'saniitize' => (bool)FALSE,
      ),
      'steam_leader_min_level' => array(
        'type' => 'text_field',
        'label' => 'Super Team Leader Min Level',
        'saniitize' => (bool)TRUE,
      ),
      'steam_leader_max_level' => array(
        'type' => 'text_field',
        'label' => 'Super Team Leader Max Level',
        'saniitize' => (bool)TRUE,
      ),
      'steam_leader_rate_value' => array(
        'type' => 'text_field',
        'label' => 'Super Team Leader Rate Value',
        'saniitize' => (bool)TRUE,
      ),
      'steam_leader_rate_override' => array(
        'type' => 'check_box',
        'label' => 'Super Team Leader Rate Override',
        'saniitize' => (bool)FALSE,
      ),
      'steam_leader_single_only' => array(
        'type' => 'check_box',
        'label' => 'Team Leader Single Only',
        'saniitize' => (bool)FALSE,
      ),

    );

    if ( empty($type) ) {
      return $this->transform_matrix_settings_list($matrix_settings_list);
    } elseif ( $type == 'raw' ) {
      return $matrix_settings_list;
    }

  }

  // Get affiliates drop down list
  public function get_affiliates_dropdown() {

    if ( !isset($this->affiliates_dropdown) && !empty($this->affiliates_dropdown) ) {

      return $this->affiliates_dropdown;

    } else {

      // Get all affiliates
      $all_affiliates = affiliate_wp()->affiliates->get_affiliates(array('number' => 0));

      // Build an array of affiliate IDs and names for the drop down
      $affiliate_dropdown = array();

      if ( $all_affiliates && !empty($all_affiliates) ) {

        foreach ($all_affiliates as $a) {

          //if ( $affiliate_name = affiliate_wp()->affiliates->get_affiliate_name( $a->affiliate_id ) ) {

          //$affiliate_dropdown[$a->affiliate_id] = $affiliate_name;

          $display = '';

          $user_id = affwp_get_affiliate_user_id($a->affiliate_id);

          if ( $user_id ) :
            $user_data = get_userdata($user_id);
            //echo '<pre>'; print_r($user_data); echo '</pre>';

            if ( $user_data ) :

              $display = $user_data->user_login;

            endif;

            if ( $affiliate_name = affiliate_wp()->affiliates->get_affiliate_name($a->affiliate_id) ) :

              $display .= ' - ' . $affiliate_name;

            endif;

            $affiliate_dropdown[$a->affiliate_id] = $display;

          endif;

          //}

        }

      }

      $this->affiliates_dropdown = $affiliate_dropdown;
      return $affiliate_dropdown;

    }

  }

  // Generate a single matrix settings table
  public function get_matrix_settings_table($args = array()) {

    ob_start();
    ?>

    <input type="hidden" name="mla_process_matrix" value="1">

    <div class='AFFWP_MLA_matrix_settings_container_<?php echo $args['id']; ?>' style="background-color:white;padding:20px;margin-bottom:20px;">
      <div style="text-align:left"><h4 style="background-color:#B9B9B9;color:#FFFFFF;text-transform: uppercase;padding:5px 0px 5px 20px;margin-top:0px"><?php echo $args['section_title']; ?></h4></div>

      <table width="750" height="127" cellpadding="4" class="form-table">
        <tbody>

        <th scope="col" colspan="3"><?php _e('Network Structure Settings', 'affiliatewp-multi-level-affiliates') ?></th>
        <tr>
          <td colspan="3"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_default_parent_<?php echo $args['id']; ?>"><?php _e('Default Parent Affiliate:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_default_parent_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_default_parent_<?php echo $args['id']; ?>">

              <option value="">None</option>
              <?php $affiliate_dropdown = $this->get_affiliates_dropdown(); ?>
              <?php foreach ($affiliate_dropdown as $affiliate_id => $affiliate_name) : ?>
                <option value="<?php echo $affiliate_id; ?>"
                        <?php if ($this->get_matrix_setting('matrix_default_parent', $args['id']) == $affiliate_id) { ?>selected="selected"<?php }; ?>><?php echo $affiliate_name; ?></option>
              <?php endforeach; ?>

              <script type="text/javascript">
                  jQuery(document).ready(function ($) {

                      $('#<?php echo $this->plugin_config['plugin_prefix'];?>_matrix_default_parent_<?php echo $args['id'];?>').select2({
                          allowClear: true,
                          width: '50%'
                      });

                  });
              </script>

            </select></td>
        </tr>

        <tr>
          <!--<td width="208"><label for="matrix_depth_<?php echo $args['id']; ?>">Max Depth:</label>
        <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_depth_<?php echo $args['id']; ?>" type="text" required id="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_depth_<?php echo $args['id']; ?>" value="<?php echo esc_attr($this->get_matrix_setting('matrix_depth', $args['id'])); ?>" size="6"><span style="color:red;"> *</span>
       </td>-->
          <td width="220"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_width_<?php echo $args['id']; ?>"><?php _e('Max Width:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_width_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_width_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('matrix_width', $args['id'])); ?>" size="6">
          </td>
          <td width="288">
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_allow_spillover_<?php echo $args['id']; ?>"><?php _e('Allow Spillover:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_allow_spillover_<?php echo $args['id']; ?>" type="checkbox"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_allow_spillover_<?php echo $args['id']; ?>" value="1"
                   <?php if ($this->get_matrix_setting('allow_spillover', $args['id']) == 1) { ?>checked<?php }; ?>>
          </td>
        </tr>

        <!--<tr>
          <td colspan="3"><span class="mla_settings_tip">For a forced matrix, enter a 'Max Width' value. Leave empty for unilevel.</span></td>
        </tr>-->

        <th scope="col" colspan="3"><?php _e('Level 1 Commission Settings', 'affiliatewp-multi-level-affiliates'); ?></th>
        <tr>
          <td colspan="3"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_level_one_affiliate_<?php echo $args['id']; ?>"><?php _e('The Level 1 commission should be awarded to:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_level_one_affiliate_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_level_one_affiliate_<?php echo $args['id']; ?>">
              <option value="parent"
                      <?php if ($this->get_matrix_setting('level_one_affiliate', $args['id']) == 'parent') { ?>selected="selected"<?php }; ?>><?php _e('Parent', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="purchaser"
                      <?php if ($this->get_matrix_setting('level_one_affiliate', $args['id']) == 'purchaser') { ?>selected="selected"<?php }; ?>><?php _e('Purchaser', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select>
          </td>
        </tr>
        <tr>
          <td colspan="3"><span
                    class="mla_settings_tip"><?php _e("For the Purchaser to receive the level 1 commission, they must be a logged in affiliate otherwise the referrer (if tracked via cookie or lifetime add-on) receives it by default.", 'affiliatewp-multi-level-affiliates'); ?></span>
          </td>
        </tr>
        <tr>
          <td colspan="3"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_mode_<?php echo $args['id']; ?>"><?php _e('The Level 1 Commission is Determined by:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_mode_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_mode_<?php echo $args['id']; ?>">
              <option value="mla"
                      <?php if ($this->get_matrix_setting('direct_referral_mode', $args['id']) == 'mla') { ?>selected="selected"<?php }; ?>><?php _e('MLA - Level 1 Rate set below or per product', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="disabled"
                      <?php if ($this->get_matrix_setting('direct_referral_mode', $args['id']) == 'disabled') { ?>selected="selected"<?php }; ?>><?php _e('AffiliateWP Referral Rate or other add-ons', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select>
          </td>
        </tr>

        <th scope="col" colspan="3"><?php _e('Per Level Settings', 'affiliatewp-multi-level-affiliates'); ?></th>

        <tr>
          <td><label for="matrix_depth_<?php echo $args['id']; ?>"><?php _e('Payment Levels:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_depth_<?php echo $args['id']; ?>" type="text" required
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_matrix_depth_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('matrix_depth', $args['id'])); ?>" size="6"><span style="color:red;"> *</span>
          </td>
          <td><label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_rate_type_<?php echo $args['id']; ?>"><?php _e('Rate Type:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_rate_type_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_rate_type_<?php echo $args['id']; ?>">
              <option value="default"
                      <?php if ($this->get_matrix_setting('rate_type', $args['id']) == 'default') { ?>selected="selected"<?php }; ?>><?php _e('Site Default', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="percentage"
                      <?php if ($this->get_matrix_setting('rate_type', $args['id']) == 'percentage') { ?>selected="selected"<?php }; ?>><?php _e('Percentage', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="flat"
                      <?php if ($this->get_matrix_setting('rate_type', $args['id']) == 'flat') { ?>selected="selected"<?php }; ?>><?php _e('Flat', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select></td>
          <td>
            <!--      	<label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_rate_value_<?php echo $args['id']; ?>"><?php _e('Default Value:', 'affiliatewp-multi-level-affiliates'); ?></label>
        <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_rate_value_<?php echo $args['id']; ?>" type="text" required id="<?php echo $this->plugin_config['plugin_prefix']; ?>_rate_value_<?php echo $args['id']; ?>" value="<?php echo esc_attr($this->get_matrix_setting('rate_value', $args['id'])); ?>" size="6"><span style="color:red;"> *</span>
-->       </td>
          <td></td>
        </tr>
        <!--<tr>
      <td colspan="3"><span class="mla_settings_tip"><?php _e('Default value is only used for empty per levels values below.', 'affiliatewp-multi-level-affiliates'); ?></span></td>
    </tr>-->

        <th scope="col" colspan="3"><?php _e('Per Level Rate Values', 'affiliatewp-multi-level-affiliates'); ?><span style="color:red;"> *</span></th>

        <?php //if( !empty(get_option('matrix_depth_'.$args['id'])) &&  get_option('matrix_depth_'.$args['id']) >= 1) :
        $level_rates_fields = ($this->get_matrix_setting('matrix_depth', $args['id']) >= 1) ? $this->get_matrix_setting('matrix_depth', $args['id']) : 1;
        ?>
        <tr>
          <td colspan="3"><!--<label for="level_rates_<?php echo $args['id']; ?>">Per Level Rate Values:</label>-->
            <?php for ($x = 1; $x <= $level_rates_fields; $x++) : ?>
              <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_level_<?php echo $x; ?>_rate_<?php echo $args['id']; ?>" type="text"
                     id="<?php echo $this->plugin_config['plugin_prefix']; ?>_level_<?php echo $x; ?>_rate_<?php echo $args['id']; ?>"
                     placeholder="<?php _e('Level', 'affiliatewp-multi-level-affiliates'); ?> <?php echo $x; ?>"
                     value="<?php echo esc_attr($this->get_matrix_setting('level_' . $x . '_rate', $args['id'])); ?>" size="6" maxlength="3">&nbsp;&nbsp;
            <?php endfor; ?>
          </td>
        </tr>
        <?php //endif; ?>
        <tr>
          <td colspan="3"><span class="mla_settings_tip"><?php _e('Save your settings if some levels are not displayed.', 'affiliatewp-multi-level-affiliates'); ?></span></td>
        </tr>

        <?php //if( $args['id'] == 'default') : ?>

        <th scope="col" colspan="3"><?php _e('Secondary (bonus) Commission - Referrer (direct)', 'affiliatewp-multi-level-affiliates'); ?></th>
        <tr>

          <td>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_enable_direct_referral_<?php echo $args['id']; ?>"><?php _e('Enable Direct Commission:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_enable_direct_referral_<?php echo $args['id']; ?>" type="checkbox"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_enable_direct_referral_<?php echo $args['id']; ?>" value="1"
                   <?php if ($this->get_matrix_setting('enable_direct_referral', $args['id']) == 1) { ?>checked<?php }; ?>>
          </td>

        </tr>
        <tr>

          <td>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_rate_type_<?php echo $args['id']; ?>"><?php _e('Rate Type:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_rate_type_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_rate_type_<?php echo $args['id']; ?>">
              <option value="default"
                      <?php if ($this->get_matrix_setting('direct_referral_rate_type', $args['id']) == 'default') { ?>selected="selected"<?php }; ?>><?php _e('Site Default', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="percentage"
                      <?php if ($this->get_matrix_setting('direct_referral_rate_type', $args['id']) == 'percentage') { ?>selected="selected"<?php }; ?>><?php _e('Percentage', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="flat"
                      <?php if ($this->get_matrix_setting('direct_referral_rate_type', $args['id']) == 'flat') { ?>selected="selected"<?php }; ?>><?php _e('Flat', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select>
          </td>
          <td>

          <td>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_rate_<?php echo $args['id']; ?>"><?php _e('Rate Value:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_rate_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_direct_referral_rate_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('direct_referral_rate', $args['id'])); ?>" size="6">
          </td>

        </tr>


        <th scope="col" colspan="3"><?php _e('Secondary (bonus) Commission - Team Leaders', 'affiliatewp-multi-level-affiliates'); ?></th>
        <tr>
          <td colspan="3"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_mode_<?php echo $args['id']; ?>"><?php _e('Bonus Commission Mode:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_mode_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_mode_<?php echo $args['id']; ?>">
              <option value="disabled"
                      <?php if ($this->get_matrix_setting('team_leader_mode', $args['id']) == 'disabled') { ?>selected="selected"<?php }; ?>><?php _e('Disabled - No Bonus Commission Paid', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="within_max_levels"
                      <?php if ($this->get_matrix_setting('team_leader_mode', $args['id']) == 'within_max_levels') { ?>selected="selected"<?php }; ?>><?php _e('Enabled - Within Payment Levels Only', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="all_levels"
                      <?php if ($this->get_matrix_setting('team_leader_mode', $args['id']) == 'all_levels') { ?>selected="selected"<?php }; ?>><?php _e('Enabled - Any Level', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="set_levels"
                      <?php if ($this->get_matrix_setting('team_leader_mode', $args['id']) == 'set_levels') { ?>selected="selected"<?php }; ?>><?php _e('Enabled - Set levels', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select></td>
        </tr>
        <tr>
          <td>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_min_level_<?php echo $args['id']; ?>"><?php _e('Min level:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_min_level_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_min_level_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('team_leader_min_level', $args['id'])); ?>" size="6">
          </td>
          <td>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_max_level_<?php echo $args['id']; ?>"><?php _e('Max level:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_max_level_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_max_level_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('team_leader_max_level', $args['id'])); ?>" size="6">
          </td>
        </tr>
        <tr class="mla_team_leader_options">
          <td colspan="2"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_type_<?php echo $args['id']; ?>"><?php _e('Rate Type:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_type_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_type_<?php echo $args['id']; ?>">
              <option value="default"
                      <?php if ($this->get_matrix_setting('team_leader_rate_type', $args['id']) == 'default') { ?>selected="selected"<?php }; ?>><?php _e('Site Default', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="percentage"
                      <?php if ($this->get_matrix_setting('team_leader_rate_type', $args['id']) == 'percentage') { ?>selected="selected"<?php }; ?>><?php _e('Percentage', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="percentage_remainder"
                      <?php if ($this->get_matrix_setting('team_leader_rate_type', $args['id']) == 'percentage_remainder') { ?>selected="selected"<?php }; ?>><?php _e('Percentage Remainder', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="flat"
                      <?php if ($this->get_matrix_setting('team_leader_rate_type', $args['id']) == 'flat') { ?>selected="selected"<?php }; ?>><?php _e('Flat', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select></td>
          <td><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_value_<?php echo $args['id']; ?>"><?php _e('Rate Value:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_value_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_value_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('team_leader_rate_value', $args['id'])); ?>" size="6"><span style="color:red;"> *</span>
          </td>
        </tr>
        <!--<tr>
          <td colspan="3"><span class="mla_settings_tip">Default rate used for empty 'per levels' values below.</span></td>
        </tr>-->
        <tr class="mla_team_leader_options <!--within_payment_levels-->">
          <td colspan="3">
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_single_only_<?php echo $args['id']; ?>" type="checkbox"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_single_only_<?php echo $args['id']; ?>" value="1"
                   <?php if ($this->get_matrix_setting('team_leader_single_only', $args['id']) == 1) { ?>checked<?php }; ?>>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_single_only_<?php echo $args['id']; ?>"><?php _e('Award single commission only when inside payment levels.', 'affiliatewp-multi-level-affiliates'); ?></label>
          </td>
        </tr>
        <tr class="mla_team_leader_options <!--within_payment_levels--> single_only">
          <td colspan="3">
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_override_<?php echo $args['id']; ?>" type="checkbox"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_override_<?php echo $args['id']; ?>" value="1"
                   <?php if ($this->get_matrix_setting('team_leader_rate_override', $args['id']) == 1) { ?>checked<?php }; ?>>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_team_leader_rate_override_<?php echo $args['id']; ?>"><?php _e('Use Per Level rate if higher than the bonus rate (when single commission enabled).', 'affiliatewp-multi-level-affiliates'); ?></label>
          </td>
        </tr>
        <tr>
          <td colspan="3"><span class="mla_settings_tip"><?php _e("To make an affiliate a 'Team Leader' you must enable it on their profile.", 'affiliatewp-multi-level-affiliates'); ?></span></td>
        </tr>

        <th scope="col" colspan="3"><?php _e('Secondary (bonus) Commission - Super Team Leaders', 'affiliatewp-multi-level-affiliates'); ?></th>
        <tr>
          <td colspan="3"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_mode_<?php echo $args['id']; ?>"><?php _e('Bonus Commission Mode:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_mode_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_mode_<?php echo $args['id']; ?>">
              <option value="disabled"
                      <?php if ($this->get_matrix_setting('steam_leader_mode', $args['id']) == 'disabled') { ?>selected="selected"<?php }; ?>><?php _e('Disabled - No Bonus Commission Paid', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="within_max_levels"
                      <?php if ($this->get_matrix_setting('steam_leader_mode', $args['id']) == 'within_max_levels') { ?>selected="selected"<?php }; ?>><?php _e('Enabled - Within Payment Levels Only', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="all_levels"
                      <?php if ($this->get_matrix_setting('steam_leader_mode', $args['id']) == 'all_levels') { ?>selected="selected"<?php }; ?>><?php _e('Enabled - Any Level', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="set_levels"
                      <?php if ($this->get_matrix_setting('steam_leader_mode', $args['id']) == 'set_levels') { ?>selected="selected"<?php }; ?>><?php _e('Enabled - Set levels', 'affiliatewp-multi-level-affiliates'); ?></option>

            </select></td>
        </tr>
        <tr>
          <td>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_min_level_<?php echo $args['id']; ?>"><?php _e('Min level:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_min_level_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_min_level_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('steam_leader_min_level', $args['id'])); ?>" size="6">
          </td>
          <td>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_max_level_<?php echo $args['id']; ?>"><?php _e('Max level:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_max_level_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_max_level_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('steam_leader_max_level', $args['id'])); ?>" size="6">
          </td>
        </tr>
        <tr class="mla_steam_leader_options">
          <td colspan="2"><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_type_<?php echo $args['id']; ?>"><?php _e('Rate Type:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <select name="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_type_<?php echo $args['id']; ?>"
                    id="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_type_<?php echo $args['id']; ?>">
              <option value="default"
                      <?php if ($this->get_matrix_setting('steam_leader_rate_type', $args['id']) == 'default') { ?>selected="selected"<?php }; ?>><?php _e('Site Default', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="percentage"
                      <?php if ($this->get_matrix_setting('steam_leader_rate_type', $args['id']) == 'percentage') { ?>selected="selected"<?php }; ?>><?php _e('Percentage', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="percentage_remainder"
                      <?php if ($this->get_matrix_setting('steam_leader_rate_type', $args['id']) == 'percentage_remainder') { ?>selected="selected"<?php }; ?>><?php _e('Percentage Remainder', 'affiliatewp-multi-level-affiliates'); ?></option>
              <option value="flat"
                      <?php if ($this->get_matrix_setting('steam_leader_rate_type', $args['id']) == 'flat') { ?>selected="selected"<?php }; ?>><?php _e('Flat', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select></td>
          <td><label
                    for="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_value_<?php echo $args['id']; ?>"><?php _e('Rate Value:', 'affiliatewp-multi-level-affiliates'); ?></label>
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_value_<?php echo $args['id']; ?>" type="text"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_value_<?php echo $args['id']; ?>"
                   value="<?php echo esc_attr($this->get_matrix_setting('steam_leader_rate_value', $args['id'])); ?>" size="6"><span style="color:red;"> *</span>
          </td>
        </tr>
        <!--<tr>
          <td colspan="3"><span class="mla_settings_tip">Default rate used for empty 'per levels' values below.</span></td>
        </tr>-->
        <tr class="mla_steam_leader_options <!--within_payment_levels-->">
          <td colspan="3">
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_single_only_<?php echo $args['id']; ?>" type="checkbox"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_single_only_<?php echo $args['id']; ?>" value="1"
                   <?php if ($this->get_matrix_setting('steam_leader_single_only', $args['id']) == 1) { ?>checked<?php }; ?>>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_single_only_<?php echo $args['id']; ?>"><?php _e('Award single commission only when inside payment levels.', 'affiliatewp-multi-level-affiliates'); ?></label>
          </td>
        </tr>
        <tr class="mla_steam_leader_options <!--within_payment_levels--> single_only">
          <td colspan="3">
            <input name="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_override_<?php echo $args['id']; ?>" type="checkbox"
                   id="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_override_<?php echo $args['id']; ?>" value="1"
                   <?php if ($this->get_matrix_setting('steam_leader_rate_override', $args['id']) == 1) { ?>checked<?php }; ?>>
            <label for="<?php echo $this->plugin_config['plugin_prefix']; ?>_steam_leader_rate_override_<?php echo $args['id']; ?>"><?php _e('Use Per Level rate if higher than the bonus rate (when single commission is enabled).', 'affiliatewp-multi-level-affiliates'); ?></label>
          </td>
        </tr>
        <tr>
          <td colspan="3"><span class="mla_settings_tip"><?php _e("To make an affiliate a 'Super Team Leader' you must enable it on their profile.", 'affiliatewp-multi-level-affiliates'); ?></span>
          </td>
        </tr>

        <?php //endif; ?>

        </tbody>
      </table>

    </div>
    <?php //submit_button(); ?>

    <?php //print_r($this->plugin_settings) ;?>
    <?php
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
  }


  //////////// Affiliate Admin Methods


  // New affilate form HTML
  public function add_parent_id_to_new_affiliate_form() {

    // Get all affiliates
    $all_affiliates = affiliate_wp()->affiliates->get_affiliates(array('number' => 0));

    // Build an array of affiliate IDs and names for the drop down
    $affiliate_dropdown = array();

    if ( $all_affiliates && !empty($all_affiliates) ) {

      foreach ($all_affiliates as $a) {

        if ( $affiliate_name = affiliate_wp()->affiliates->get_affiliate_name($a->affiliate_id) ) {
          $affiliate_dropdown[$a->affiliate_id] = $affiliate_name;
        }

      }

      // Make sure to remove current affiliate from the array so they can't be their own parent affiliate
      //unset( $affiliate_dropdown[$affiliate->affiliate_id] );

    }

    ob_start(); ?>

    <table class="form-table">
      <tbody>

      <tr class="form-row">

        <th scope="row">
          <label for="rate"><?php _e('Parent Affiliate', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <select name="parent_affiliate_id" id="parent_affiliate_id">
            <option value="">None</option>
            <?php foreach ($affiliate_dropdown as $affiliate_id => $affiliate_name) : ?>
              <option value="<?php echo esc_attr($affiliate_id); ?>"><?php echo esc_html($affiliate_name); ?></option>
            <?php endforeach; ?>
          </select>
          <p class="description"><?php _e('Start typing the affiliate\'s name to search.', 'affiliatewp-multi-level-marketing'); ?></p>
        </td>

      </tr>

      <tr class="form-row">

        <th scope="row">
          <label for="rate"><?php _e('Direct Affiliate', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <select name="direct_affiliate_id" id="direct_affiliate_id">
            <option value="">None</option>
            <?php foreach ($affiliate_dropdown as $affiliate_id => $affiliate_name) : ?>
              <option value="<?php echo esc_attr($affiliate_id); ?>"><?php echo esc_html($affiliate_name); ?></option>
            <?php endforeach; ?>
          </select>
          <p class="description"><?php _e('Enter the name of the affiliate to perform a search.', 'affiliatewp-multi-level-affiliates'); ?></p>
        </td>

      </tr>

      </tbody>
    </table>

    <?php
    $content = ob_get_contents();
    ob_end_clean();
    echo $content;

  }

  // Add fields and data to the edit affilate page
  public function add_parent_id_to_affiliate_page($affiliate) {

    $affiliate_id = $affiliate->affiliate_id;
    $user_id = affwp_get_affiliate_user_id($affiliate_id);

    require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-common.php';
    require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-affiliate.php';
    $mla_affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
    $parent_affiliate_id = $mla_affiliate->get_parent_affiliate_id($affiliate_id);
    $direct_affiliate_id = $mla_affiliate->get_direct_affiliate_id($affiliate_id);

    // Only output fields for an active affiliate
    //if(affwp_is_affiliate($user_id)) {

    //if(affwp_is_active_affiliate($affiliate_id)) {

    // Get all affiliates
    $all_affiliates = affiliate_wp()->affiliates->get_affiliates(array('number' => 0));

    // Build an array of affiliate IDs and names for the drop down
    $affiliate_dropdown = array();

    if ( $all_affiliates && !empty($all_affiliates) ) {

      foreach ($all_affiliates as $a) {

        if ( $affiliate_name = affiliate_wp()->affiliates->get_affiliate_name($a->affiliate_id) ) {
          $affiliate_dropdown[$a->affiliate_id] = $affiliate_name;
        }

      }

      // Make sure to remove current affiliate from the array so they can't be their own parent affiliate
      unset($affiliate_dropdown[$affiliate->affiliate_id]);

    }

    ob_start(); ?>

    <table class="form-table">
      <tbody>

      <tr class="form-row">

        <th scope="row">
          <label for="parent_affiliate_id"><?php _e('Parent Affiliate', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <select name="parent_affiliate_id" id="parent_affiliate_id">
            <option value="">None</option>
            <?php foreach ($affiliate_dropdown as $affiliate_id => $affiliate_name) : ?>
              <option value="<?php echo esc_attr($affiliate_id); ?>"<?php selected($parent_affiliate_id, $affiliate_id); ?>><?php echo esc_html($affiliate_name); ?></option>
            <?php endforeach; ?>
          </select>
          <p class="description"><?php _e('Enter the name of the affiliate to perform a search.', 'affiliatewp-multi-level-affiliates'); ?></p>
        </td>

      </tr>

      <tr class="form-row">

        <th scope="row">
          <label for="direct_affiliate_id"><?php _e('Direct Affiliate', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <select name="direct_affiliate_id" id="direct_affiliate_id">
            <option value="">None</option>
            <?php foreach ($affiliate_dropdown as $affiliate_id => $affiliate_name) : ?>
              <option value="<?php echo esc_attr($affiliate_id); ?>"<?php selected($direct_affiliate_id, $affiliate_id); ?>><?php echo esc_html($affiliate_name); ?></option>
            <?php endforeach; ?>
          </select>
          <p class="description"><?php _e('Enter the name of the affiliate to perform a search.', 'affiliatewp-multi-level-affiliates'); ?></p>
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

  /////////// Licensing Methods ///////////////

  // Only modify this method
  /*public function remove_license_settings() {
    $options = affiliate_wp()->settings->get_all();
    unset( $options[$this->plugin_config['plugin_prefix'].'_'.'license_key'] );
    unset( $options[$this->plugin_config['plugin_prefix'].'_'.'license_status'] );
    update_option( 'affwp_settings', $options );
  }*/

  // Deactive license
  public function deactivate_license() {

    if (
      (isset($_GET[$this->plugin_config['plugin_prefix'] . '_license_change'])) &&
      ($_GET[$this->plugin_config['plugin_prefix'] . '_license_change'] == 'deactivate')
    ) {

      $license = new Click_Studio_Licenses_V1_5($this->plugin_config, $this->plugin_settings);
      if ( $license->deactivate_license() ) {

        // Redirect to settings page
        $location = $_SERVER['HTTP_REFERER'];
        wp_safe_redirect($location);

      }

    }

  }

  // Get the license message actions and messages. Also activate license keys.
  public function license_status_msg() {

    $license = new Click_Studio_Licenses_V1_5($this->plugin_config, $this->plugin_settings);

    if ( isset($_GET['cs_remove_license_data']) && $_GET['cs_remove_license_data'] == true ) {

      $license->remove_license_data();
      $license->remove_affwp_settings();

    } else {

      $license->activate_license();

      $license_message = $license->license_status_msg();

      return $license_message;

    }

  }

  // Check license status
  public function is_license_valid() {

    if ( $this->check_if_settings_page() ) {

      $license = new Click_Studio_Licenses_V1_5($this->plugin_config, $this->plugin_settings);
      $status = $license->get_license_option('license_status');

      if ( !empty($status) && $status == 'valid' ) return true;

    }

    return false;

  }

} // End of class
?>