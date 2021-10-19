<?php
/*
Plugin Name: AffiliateWP - Groups
Plugin URI: http://clickstudio.com.au
Description: Set options and rates on a group by group basis
Version: 1.6.12
Author: Click Studio
Author URI: http://clickstudio.com.au
License: GPL2
*/

class AffiliateWP_Affiliate_Groups {

  private static $instance = NULL;

  public static function instance() {

    /*NULL === self::$instance and self::$instance = new self;

    //self::$instance->plugin_config();
    self::$instance->includes();
    self::$instance->setup_objects();
    self::$instance->setup_hooks();

    return self::$instance;	*/

    if ( NULL === self::$instance ) {

      self::$instance = new self;

      self::$instance->includes();
      self::$instance->setup_objects();
      self::$instance->setup_hooks();
    }

    return self::$instance;

  }

  public function __construct() {

    $plugin_config = array();

    $plugin_config['plugin_file'] = __FILE__;
    $plugin_config['plugin_dir'] = plugin_dir_path(__FILE__);
    $plugin_config['plugin_lang_dir'] = basename(dirname(__FILE__)) . '/languages';

    // Item name. Must be identical to the name is EDD
    $plugin_config['plugin_item_id'] = '1774';
    $plugin_config['plugin_item_name'] = 'AffiliateWP - Affiliate Groups';
    $plugin_config['plugin_prefix'] = 'AFFWP_AG';
    $plugin_config['plugin_version'] = '1.6.12';
    $plugin_config['plugin_updater_url'] = 'https://www.clickstudio.com.au';
    $plugin_config['plugin_author'] = 'Click Studio';

    $this->plugin_config = $plugin_config;

    //update_site_option( $plugin_config['plugin_prefix'].'_version', $plugin_config['plugin_version'] );

  }

  // Includes
  private function includes() {
    require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-plugin-base.php';
    require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-settings.php';
    if ( is_admin() ) {
      require_once $this->plugin_config['plugin_dir'] . 'includes/class-licenses.php';
      require_once $this->plugin_config['plugin_dir'] . 'includes/class-updater.php';
    }
  }

  // Set up objects
  private function setup_objects() {
    // AFFWP_AG_PLUGIN_CONFIG
    //define( $this->plugin_config['plugin_prefix'].'_PLUGIN_CONFIG', serialize( $this->plugin_config ) );
    self::$instance->settings = new AffiliateWP_Affiliate_Groups_Settings();
    self::$instance->plugin_settings = self::$instance->settings->plugin_settings;
    // AFFWP_AG_PLUGIN_SETTINGS
    //define( $this->plugin_config['plugin_prefix'].'_PLUGIN_SETTINGS', serialize( self::$instance->plugin_settings ) );
    //self::$instance->base = new AffiliateWP_Affiliate_Groups_Base($this->plugin_config, self::$instance->plugin_settings);
    self::$instance->base = new AffiliateWP_Affiliate_Groups_Base();
    if ( is_admin() ) {
      self::$instance->license = new Click_Studio_Licenses_V1_5($this->plugin_config, self::$instance->plugin_settings);
      self::$instance->updater = new Click_Studio_Updater_V1_4($this->plugin_config, self::$instance->license->get_license_option('license_key'));
    }

  }

  // Set up hooks
  private function setup_hooks() {
  }

} // End of class

// Include the public functions
require_once plugin_dir_path(__FILE__) . 'functions.php';


// Pre-instance dependency check
add_action('init', 'affiliate_wp_groups');
function affiliate_wp_groups() {

  $activation_config = array(
    'plugin_name' => 'AffiliateWP - Affiliate Groups',
    'plugin_path' => plugin_dir_path(__FILE__),
    'plugin_file' => basename(__FILE__),
    'plugin_dependencies' => array(
      'Affiliate_WP' => array(
        'name' => 'AffiliateWP',
        'plugin_folder_file' => 'affiliate-wp/affiliate-wp.php',
        'url' => 'https://affiliatewp.com/ref/613/'
      )
    ),

  );

  require_once 'includes/class-activation.php';
  $activation = new Click_Studio_Activation_V1_1($activation_config);

  // If all dependencies are fine return instance
  if ( $activation->check_dependencies() ) {
    return AffiliateWP_Affiliate_Groups::instance();
    register_activation_hook(__FILE__, 'affilate_groups_activate_plugin');
  }

}

// Activation function	
register_activation_hook(__FILE__, 'affilate_groups_activate_plugin');
function affilate_groups_activate_plugin() {

  if ( class_exists('Affiliate_WP') ) :

    $plugin_version = get_site_option('AFFWP_AG_version', '1.4.0');

    if ( $plugin_version <= '1.4.0' ) :

      /////// Update the groups storage to the new method

      require_once plugin_dir_path(__FILE__) . 'plugin_core/class-plugin-base.php';
      require_once 'functions.php';

      $groups = AffiliateWP_Affiliate_Groups_Base::get_all_active_affiliate_groups();
      if ( $groups && !empty($groups) ) :
        foreach ($groups as $key => $attributes) {
          add_role($key, $attributes['name'], $attributes['capabilities']);
        }
      endif;

      // Get all affiliates
      $all_affiliates = affiliate_wp()->affiliates->get_affiliates(array('number' => 0));

      if ( $all_affiliates && !empty($all_affiliates) ) {

        foreach ($all_affiliates as $a) :

          $affiliate_id = $a->affiliate_id;
          $user_id = affwp_get_affiliate_user_id($affiliate_id);
          $user = get_userdata($user_id);
          $user_roles = $user->roles;

          foreach ($groups as $key => $attributes) :

            if ( in_array($key, (array)$user->roles) ) {
              add_affiliate_to_group($affiliate_id, $key);
            }

          endforeach;

        endforeach;

      }

      if ( $groups && !empty($groups) ) :
        foreach ($groups as $key => $attributes) {
          remove_role($key);
        }
      endif;

      // Unset the user roles settings
      affiliate_wp()->settings->set(array('AFFWP_AG_enable_user_roles' => 0, 'AFFWP_AG_enable_user_views' => 0), TRUE);

      /////// END Update the groups storage to the new method

    endif; // end if < V 1.4.0

  endif;

}

// Deactivation function
register_deactivation_hook(__FILE__, 'affilate_groups_deactivate_plugin');
function affilate_groups_deactivate_plugin() {
}

?>
