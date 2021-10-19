<?php

class AffiliateWP_Multi_Level_Affiliates_Base {

	protected $plugin_settings;
	protected $plugin_config;

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
		$affwp_mla = affiliate_wp_mla();
		$this->plugin_config = $affwp_mla->plugin_config;
		$this->plugin_settings = $affwp_mla->plugin_settings;
		
		// If plugin features enabled
		if( isset( $this->plugin_settings[$this->plugin_config['plugin_prefix'].'_enable_mla'] ) ) :
			if( $this->plugin_settings[$this->plugin_config['plugin_prefix'].'_enable_mla'] == '1' ) :
				$lps = get_site_option(  $this->plugin_config['plugin_prefix'].'_'.'lps', '' );
				if( !empty($lps) && $lps != '2' ) :
			
				$this->load_textdomain();
				$this->includes();
				$this->setup_objects();	
				
				endif;
			endif;
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

		$locale = apply_filters( 'plugin_locale', $get_locale, 'affiliatewp-multi-level-affiliates' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'affiliatewp-multi-level-affiliates', $locale );

		$mofile_global = WP_LANG_DIR . '/affiliatewp-multi-level-affiliates/'. $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/ folder
			load_textdomain( 'affiliatewp-multi-level-affiliates', $mofile_global );
		}else {
			// Load the default language files from plugin
			load_plugin_textdomain( 'affiliatewp-multi-level-affiliates', false, $lang_dir );
		}
	}
	
	private function includes() {
		
		// Includes
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/includes/gamajo-template-loader.php';
		
		// Core
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-template-loader.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-common.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-hooks.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-shortcodes.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-affiliate.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-referral.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-matrix.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-statistics.php';
		//require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-groups.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-notifications.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-team-leader.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-reports.php';
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/class-charts.php';
		
		// Integrations
		
		// Groups
		require_once $this->plugin_config['plugin_dir'] . 'plugin_core/integrations/class-groups.php';
		
		// WooCommerce
		if( class_exists('WooCommerce') ) :
			require_once $this->plugin_config['plugin_dir'] . 'plugin_core/integrations/class-woocommerce.php';	
		endif;
		
		// EDD
		if( class_exists('Easy_Digital_Downloads') ) :
			require_once $this->plugin_config['plugin_dir'] . 'plugin_core/integrations/class-edd.php';	
		endif;
		
		// AffiliateWP BuddyPress
		if ( class_exists( 'AffiliateWP_BuddyPress' ) ) :
			if( $this->plugin_settings[$this->plugin_config['plugin_prefix'].'_dashboard_tab_enable'] == '1' 
				&& $this->plugin_settings[$this->plugin_config['plugin_prefix'].'_buddypress_enable'] == '1' ) :
				require_once $this->plugin_config['plugin_dir'] . 'plugin_core/integrations/affiliatewp-buddypress.php';
			endif;	
		endif;
		
	}
	
	private function setup_objects() {
		$this->hooks = new AffiliateWP_MLA_Hooks();
		$this->shortcodes = new AffiliateWP_MLA_Shortcodes();
		$this->notifications = new AffiliateWP_MLA_Notifications();
		//$this->matrix = new AffiliateWP_MLA_Matrix();
		$this->team_leader = new AffiliateWP_MLA_Team_Leader();
		$this->groups = new AffiliateWP_MLA_Groups();
		
		if( class_exists('WooCommerce') ) :
			$this->woocommerce = new AffiliateWP_MLA_WooCommerce();
		endif;
		
		if( class_exists('Easy_Digital_Downloads') ) :
			$this->edd = new AffiliateWP_MLA_Edd();
		endif;
		
	}
	
} // End of class

?>