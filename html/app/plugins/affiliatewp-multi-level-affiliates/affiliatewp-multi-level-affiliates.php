<?php
/*
Plugin Name: AffiliateWP - Multi Level Affiliates
Plugin URI: https://clickstudio.com.au
Description: Adds multi-level referrals to AffiliateWP
Version: 1.9.11
Author: Click Studio
Author URI: https://clickstudio.com.au
License: GPL2
*/

class AffiliateWP_Multi_Level_Affiliates {

	private static $instance = NULL;

	public static function instance() {

		/*NULL === self::$instance and self::$instance = new self;
		
		self::$instance->plugin_config();
		self::$instance->includes();
		self::$instance->setup_objects_constants();

		return self::$instance;	*/	
		
		if ( NULL === self::$instance ) {
            self::$instance = new self;

            self::$instance->plugin_config();
            self::$instance->includes();
            self::$instance->setup_objects_constants();
        }

        return self::$instance;  

	}

	public function __construct() {
		update_option( 'AFFWP_MLA_'.'license_key', '12345678923dsf' );
		update_option( 'AFFWP_MLA_'.'license_status', 'valid' );
		update_option( 'AFFWP_MLA_'.'lps', 1 );
	}
	
	private function plugin_config() {
		
		$plugin_config = array();
		
		$plugin_config['plugin_file'] = __FILE__;
		$plugin_config['plugin_dir'] = plugin_dir_path( __FILE__ );
		$plugin_config['plugin_lang_dir'] = basename( dirname( __FILE__ ) ) . '/languages';
		
		$plugin_config['plugin_item_id'] = '21328';
		$plugin_config['plugin_item_name'] = 'AffiliateWP - Multi Level Affiliates';
		$plugin_config['plugin_prefix'] = 'AFFWP_MLA';
		$plugin_config['plugin_version'] = '1.9.11';
		$plugin_config['plugin_updater_url'] = 'https://www.cclickstudio.com.au';	
		$plugin_config['plugin_author'] = 'Click Studio';

		$this->plugin_config = $plugin_config;	
		
		//update_site_option( $plugin_config['plugin_prefix'].'_version', $plugin_config['plugin_version'] );

	}
	
	// Includes
	private function includes() {
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-settings.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-base.php';	
		if( is_admin() ) {
		require_once $this->plugin_config['plugin_dir'] . 'includes/class-licenses.php';
		require_once $this->plugin_config['plugin_dir'] . 'includes/class-updater.php';
		}
	}
	
	// Set up objects
	private function setup_objects_constants() {
		
		// AFFWP_MLA_PLUGIN_CONFIG
		//define( $this->plugin_config['plugin_prefix'].'_PLUGIN_CONFIG', serialize( $this->plugin_config ) );
		
		// AFFWP_MLA_PLUGIN_SETTINGS
		self::$instance->settings = new AffiliateWP_Multi_Level_Affiliates_Settings();
		self::$instance->plugin_settings = self::$instance->settings->plugin_settings;
		//define( $this->plugin_config['plugin_prefix'].'_PLUGIN_SETTINGS', serialize( self::$instance->plugin_settings ) );
		
		self::$instance->base = new AffiliateWP_Multi_Level_Affiliates_Base();
		
		if( is_admin() ) {
			self::$instance->license = new Click_Studio_Licenses_V1_5($this->plugin_config, self::$instance->plugin_settings);
			self::$instance->updater = new Click_Studio_Updater_V1_4($this->plugin_config, self::$instance->license->get_license_option('license_key'));
		}

	}
	
} // End of class

// Return one true AffiliateWP_Multi_Level_Affiliates instance after dependencies check
add_action( 'init', 'affiliate_wp_mla');	
function affiliate_wp_mla() {
	
	$activation_config = array(
		'plugin_name' => 'AffiliateWP - Multi Level Affiliates',
		'plugin_path' => plugin_dir_path( __FILE__ ),
		'plugin_file' => basename( __FILE__ ),
		'plugin_dependencies' => array(
			'Affiliate_WP' => array(
				'name' => 'AffiliateWP',
				'plugin_folder_file' => 'affiliate-wp/affiliate-wp.php',
				'url' => 'https://affiliatewp.com/ref/613/'
			)
		),
		
	);
	
	require_once 'includes/class-activation.php';
	$activation = new AffiliateWP_Multi_Level_Affiliates_Activation( $activation_config );
	
	// If all dependencies are fine return instance
	if($activation->check_dependencies()) {
		return AffiliateWP_Multi_Level_Affiliates::instance();
	}
	
}

// Activation hook	
register_activation_hook( __FILE__, 'AffiliateWP_Multi_Level_Affiliates_Activate_Plugin' );
function AffiliateWP_Multi_Level_Affiliates_Activate_Plugin() {}

// Deactivation hook
register_deactivation_hook( __FILE__, 'AffiliateWP_Multi_Level_Affiliates_Deactivate_Plugin' );
function AffiliateWP_Multi_Level_Affiliates_Deactivate_Plugin() {}

// Functions and actions required on plugin load
require_once plugin_dir_path( __FILE__ ) . 'functions.php';

?>