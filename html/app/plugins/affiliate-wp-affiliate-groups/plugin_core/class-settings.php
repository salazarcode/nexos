<?php
class AffiliateWP_Affiliate_Groups_Settings {

	protected $plugin_config;
	public $plugin_settings;

	public function __construct() {
		
		//if (defined('AFFWP_AG_PLUGIN_CONFIG')) $this->plugin_config = unserialize(AFFWP_AG_PLUGIN_CONFIG);
		$this->plugin_config = affiliate_wp_groups()->plugin_config;
		
		$this->plugin_settings = $this->get_all_settings();
		
		// Plugin upgrade tasks
		$this->plugin_upgrade_tasks();
		
		// New settings tab & settings
		add_filter( 'affwp_settings_tabs', array( $this, 'settings_tab' ) );
		add_filter( 'affwp_settings', array( $this, 'settings' ), 10, 1 );
		
		if($this->check_if_settings_page()) {
			add_action('admin_init', array( $this, 'deactivate_license' ), 1);
			// Reset the master settings and process actions
			add_action( 'admin_init', array( $this, 'generate_master_settings' ), 1, 1 );
		}
	

	}
	
	// Plugin upgrade tasks
	public function plugin_upgrade_tasks() {
		
	  $previous_version = get_site_option( $this->plugin_config['plugin_prefix'].'_version' ); 
	  $new_version = $this->plugin_config['plugin_version'];
	  
	  if( !empty($previous_version) ) {
		
		  // update lps
		  if( $previous_version < '1.5.3' )  :
			  update_site_option( $this->plugin_config['plugin_prefix'].'_lps', 1 );
		  endif;
		  
		  // update db version
		  if( $previous_version != $new_version ) :
	  		  update_site_option( $this->plugin_config['plugin_prefix'].'_version', $this->plugin_config['plugin_version'] );
	  	  endif;
	  
	  }else{
		  
		  update_site_option( $this->plugin_config['plugin_prefix'].'_version', $this->plugin_config['plugin_version'] );
		  
	  }
 
	}

	// Check if on the plugins settings page
	public function check_if_settings_page() {
		if( isset($_GET['tab']) && $_GET['tab'] == $this->plugin_config['plugin_prefix'] ) return TRUE;
	}
	
	// Create the settings tab
	public function settings_tab( $tabs ) {
		$tabs[$this->plugin_config['plugin_prefix']] = __( 'Groups', '' );
		return $tabs;
	}
	
	// Get all settings
	public function get_all_settings() {
	
		$settings = $this->settings(array());
		//print_r($settings);

		foreach($settings[$this->plugin_config['plugin_prefix']] as $key => $value) {
			
			if (class_exists( 'Affiliate_WP' ) ) {
				$thesettings[$key] = affiliate_wp()->settings->get($key);
			}
		}
		
		return $thesettings;
	
	}
	
	// Get a group setting. Public Static
	public static function get_affiliate_groups_setting($setting_key, $group_id) {
		
		$options = get_option('affwp_settings');
		
		//$plugin_config = unserialize(AFFWP_AG_PLUGIN_CONFIG);
		//$plugin_settings = unserialize(AFFWP_AG_PLUGIN_SETTINGS);
		$affwp_groups = affiliate_wp_groups();
		$plugin_config = $affwp_groups->plugin_config;
		$plugin_settings = $affwp_groups->plugin_settings;
		
		if( isset($options[$plugin_config['plugin_prefix'].'_'.$group_id.'_'.$setting_key]) )  :
		
			return $options[$plugin_config['plugin_prefix'].'_'.$group_id.'_'.$setting_key];
			
		endif;
		
	}
	
	// Update a group setting. Public Static
	public static function update_affiliate_groups_setting($setting_key, $value, $group_id='') {
		
		$options = get_option('affwp_settings');
		
		//$plugin_config = unserialize(AFFWP_AG_PLUGIN_CONFIG);
		//$plugin_settings = unserialize(AFFWP_AG_PLUGIN_SETTINGS);
		$affwp_groups = affiliate_wp_groups();
		$plugin_config = $affwp_groups->plugin_config;
		$plugin_settings = $affwp_groups->plugin_settings;
		
		// Update the option
		if( empty($group_id) ) {
			$options[$plugin_config['plugin_prefix'].'_'.$setting_key] = $value;
		} else {
			$options[$plugin_config['plugin_prefix'].'_'.$group_id.'_'.$setting_key] = $value;
		}
		
		update_option( 'affwp_settings', $options );
		
		// Reset the constant
		$plugin_settings[$plugin_config['plugin_prefix'].'_'.$group_id.'_'.$setting_key] = $value;
		define( $plugin_config['plugin_prefix'].'_PLUGIN_SETTINGS', serialize( $plugin_settings ) );
		
	}
	
	// Remove a groups settings when group is deleted
	public function delete_group_settings($key) {
		
		$options = affiliate_wp()->settings->get_all();
		
		unset( $options[$this->plugin_config['plugin_prefix'].'_'.$key.'_header'] );
		unset( $options[$this->plugin_config['plugin_prefix'].'_'.$key.'_enable'] );
		unset( $options[$this->plugin_config['plugin_prefix'].'_'.$key.'_name'] );
		unset( $options[$this->plugin_config['plugin_prefix'].'_'.$key.'_rate_type'] );
		unset( $options[$this->plugin_config['plugin_prefix'].'_'.$key.'_rate'] );
		unset( $options[$this->plugin_config['plugin_prefix'].'_'.$key.'_mla_mode'] );

		update_option( 'affwp_settings', $options );
		
	}
	
	// Check if MLA enabled
	public function mla_enabled() {
		if ( class_exists('AffiliateWP_Multi_Level_Affiliates') ) return (bool) TRUE;
	}

	
	// Generate the form settings
	public function settings( $settings ) {
		
		if( is_admin() && $this->check_if_settings_page() ) {
			$license_msg = $this->license_status_msg();
			$license_msg = ( !empty($license_msg) ) ? $license_msg : '' ;
		} else {
			$license_msg = '';
		}
	
		$settings2 = array(
			$this->plugin_config['plugin_prefix'] => apply_filters( 'affwp_settings_groups',
				array(
					$this->plugin_config['plugin_prefix'].'_section_licensing' => array(
						'name' => '<strong>' . __( 'License Settings', 'affiliate-wp-affiliate-groups' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),
					
					$this->plugin_config['plugin_prefix'].'_license_key' => array(
						'name' => __( 'License Key', 'affiliate-wp-affiliate-groups' ),
						'desc' => $license_msg,
						'type' => 'text',
						'disabled' => $this->is_license_valid()
					),
					
					/*$this->plugin_config['plugin_prefix'].'_section_general_settings' => array(
						'name' => '<strong>' . __( 'General Settings', 'affiliate-wp-affiliate-groups' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),
					
					$this->plugin_config['plugin_prefix'].'_enable_user_roles' => array(
						'name' => __( '', '' ),
						'desc' => __( '<b>Enable user roles</b> (adds each group as a WordPress user role)', '' ),
						'type' => 'checkbox'
					),
					
					$this->plugin_config['plugin_prefix'].'_enable_user_views' => array(
						'name' => __( '', '' ),
						'desc' => __( '<b>Enable user views</b> (displays groups as a user role of the users page)', '' ),
						'type' => 'checkbox'
					),*/
					
					$this->plugin_config['plugin_prefix'].'_section_auto_grouping' => array(
						'name' => '<strong>' . __( 'Auto Grouping Settings', 'affiliate-wp-affiliate-groups' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),
					
					$this->plugin_config['plugin_prefix'].'_auto_grouping_enable' => array(
						'name' => '',
						'desc' => '<b>'.__( 'Enable auto grouping', 'affiliate-wp-affiliate-groups' ).'<b/>',
						'type' => 'checkbox'
					),
					
					$this->plugin_config['plugin_prefix'].'_auto_grouping_mode' => array(
						'name' => __( '', '' ),
						'desc' => '<b>'.__( 'Auto Grouping Mode', 'affiliate-wp-affiliate-groups' ).'<b/>',
						'type' => 'select',
						'options' => array(
							'list' => __( 'The groups selected in the list below', 'affiliate-wp-affiliate-groups' ),
							'clone_parent_strict' => __( 'The same groups as the referrer', 'affiliate-wp-affiliate-groups' ),
							'clone_parent_fallback' => __( 'The same groups as the referrer - Fallback to list below if no referrer', 'affiliate-wp-affiliate-groups' ),
								
						)
					),
					
					$this->plugin_config['plugin_prefix'].'_auto_grouping_options' => array(
						'name' => '',
						'desc' => '<p style="padding-top:20px">'.__( 'Select which groups new affiliates will be added to (when auto grouping is enabled)', 'affiliate-wp-affiliate-groups' ).'<p/><p>'.__( 'Does not apply when adding new affiliates manually', 'affiliate-wp-affiliate-groups' ).'<p/><p style="padding-top:30px">'.$this->generate_new_group_button().'</p>',
						//'desc' => '<p style="padding-top:20px">Select which groups new affiliates will be added to (when auto grouping is enabled)</p><p>Does not apply when adding new affiliates manually</p>'
						//.'<p style="padding-top:30px">'.$this->generate_new_group_button().'</p>',
						'type' => 'multicheck',
						'options' => $this->generate_auto_grouping_checkboxes()
					),
					
				)
			)
		);
		
		$affiliate_groups = get_site_option($this->plugin_config['plugin_prefix'].'_groups', '');
			if( is_array($affiliate_groups) && count($affiliate_groups) > 0 ) {
				foreach($affiliate_groups as $key => $group) {
					if($group['status'] =='1') {
						$settings2 = $this->generate_group_settings($settings2, $key, $group['name']);
					}
				}
			}
	
		
		// Merge settings
		$settings = array_merge( $settings, $settings2 );	
		
		return $settings;
	}
	
	// Generate the group form settings
	private function generate_group_settings($settings, $id, $name) {
		
		$last_key_parts = (explode('_',$id));
		$id_number = end($last_key_parts);
					
		$settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'].'_'.$id.'_header'] = array(
			'name' => '<b>'.$name.'</b>',
			'desc' => '',
			'type' => 'header'
		);
		
		$settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'].'_'.$id.'_enable'] = array(
			'name' => '',
			'desc' => '<b>Enable '. '\''.$name.'\'</b>'.$this->generate_delete_group_html($id),
			'type' => 'checkbox'
		);
					
		$settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'].'_'.$id.'_name'] = array(
			'name' => '',
			//'desc' =>'<b>Group name.</b> \'Affiliate Group '.$id_number.'\' by default',
			'desc' => '<b>'.__( 'Group name', 'affiliate-wp-affiliate-groups' ).'</b>',
			'type' => 'text',
		);
					
		$settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'].'_'.$id.'_rate_type'] = array(
			'name' => '',
			'desc' => '<b>'.__( 'Rate type', 'affiliate-wp-affiliate-groups' ).'</b>',
			'type' => 'select',
			'options' => array(
				'default' => __( 'None', '' ),
				'percentage' => __( 'Percentage', '' ),
				'flat' => __( 'Flat', '' ),	
			)
		);
					
		$settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'].'_'.$id.'_rate'] = array(
			'name' => '',
			'desc' => '<b>'.__( 'Rate value', 'affiliate-wp-affiliate-groups' ).'</b>',
			'type' => 'text',
		);
		
		$settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'].'_'.$id.'_mla_mode'] = array(
			'name' => '',
			'desc' => '<b>'.__( 'Multi Level Affilates Mode', 'affiliate-wp-affiliate-groups' ).'</b><p>'.
			__( 'If enabled, the MLA rates may override the group\'s rates depending on your configuration', 'affiliate-wp-affiliate-groups' ).'</p><p>'.
			__( 'All MLA rates are configured on the MLA tab', 'affiliate-wp-affiliate-groups' ).'</p>',
			//'desc' => '<b>Multi Level Affilates Mode</b><p>If enabled, the MLA rates may override the group\'s rates depending on your configuration</p><p>All MLA rates are configured on the MLA tab.</p>',
			'type' => 'select',
			'options' => array(
				'enabled' => __( 'Enabled - Use Global Matrix Settings', 'affiliate-wp-affiliate-groups' ),
				'enabled_extended' => __( 'Enabled - Set Group Matrix Settings', 'affiliate-wp-affiliate-groups' ),
				'disabled' => __( 'Disabled', 'affiliate-wp-affiliate-groups' ),	
			)
		);
		
		// return $settings;	
		return $this->check_product_integration_settings($settings, $id);
	}
	
	// Remove integration settings if not required
	public function check_product_integration_settings($settings, $id) {
		
		//$enabled_integrations = apply_filters( 'affwp_enabled_integrations', affiliate_wp()->settings->get( 'integrations', array() ) );
		
		// MLA
		if(!$this->mla_enabled()) {
			unset( $settings[$this->plugin_config['plugin_prefix']][$this->plugin_config['plugin_prefix'].'_'.$id.'_mla_mode'] );
		}
		
		// WooCommerce
		//if(array_key_exists('woocommerce', $enabled_integrations)) {
			//return $settings;
			
		//}
		
		return $settings;
		
	}
	
	
	/////////// Master settings methods //////////
	
	// Generate the master settings & process the actions
	public function generate_master_settings() {
		
		$affiliate_groups = get_site_option($this->plugin_config['plugin_prefix'].'_groups', '');

		// New install default group
		if(empty($affiliate_groups)) {
		
			// Status: active, deleted
			
			$affiliate_groups = array(
				'affiliate_group_id_1' => array(
				'name' => apply_filters('change_group_name', __( 'Group', 'affiliate-wp-affiliate-groups' ).' 1', '1'), 
				'status' => '1',
				'capabilities' => array(),
				),
				
			); 
		
		}
		
		// Add extra group
		if( isset($_GET['AFFWP_AG_groups_action']) && $_GET['AFFWP_AG_groups_action']=='new' ) {
			array_multisort(array_keys($affiliate_groups), SORT_NATURAL, $affiliate_groups);
			end($affiliate_groups);
			$last_key_parts = (explode('_',key($affiliate_groups)));
			$last_key_id = end($last_key_parts);
			$new_key_id = $last_key_id + 1;
			
			$new_group = array(
			'affiliate_group_id_'.$new_key_id => array(
				'name' => apply_filters('change_group_name', __( 'Group', 'affiliate-wp-affiliate-groups' ).' '.$new_key_id, $new_key_id), 
				'status' => '1',
				'capabilities' => array(),
				),
			);
			
			
			$affiliate_groups = array_merge($affiliate_groups, $new_group);
			//array_multisort(array_keys($affiliate_groups), SORT_NATURAL, $affiliate_groups);
			
			$options = affiliate_wp()->settings->get_all();
			
			// Set the default settings
			$this->update_affiliate_groups_setting( 'name', 'Affiliate Group '.$new_key_id, 'affiliate_group_id_'.$new_key_id);
			$this->update_affiliate_groups_setting( 'rate_type', $options['referral_rate_type'], 'affiliate_group_id_'.$new_key_id);
			$this->update_affiliate_groups_setting( 'rate', $options['referral_rate'], 'affiliate_group_id_'.$new_key_id);
			
			//$group_args = array( $group_id => 'affiliate_group_id_'.$new_key_id, );
			//do_action( 'affiliate_groups_add_group', $group_args );
			$action = 'add_group';
			$action_group_id = 'affiliate_group_id_'.$new_key_id;
			$redirect = remove_query_arg( array('AFFWP_AG_groups_action', 'AFFWP_AG_groups_action'));
			
		}
		
		// Delete group
		if( isset($_GET['AFFWP_AG_groups_action']) && $_GET['AFFWP_AG_groups_action'] == 'delete' && !empty($_GET['group_id'])) {
			
			// Must have at least one group
			if($_GET['group_id'] != 'affiliate_group_id_1') {
			
				// Change the status of the master setting to '0'
				$affiliate_groups[$_GET['group_id']]['status'] = '0';
				
				// Remove some of the for settings (enable)
				$this->delete_group_settings($_GET['group_id']);
				
				// Add action here

				//do_action( 'affiliate_groups_delete_group', $group_args );
				$action = 'delete_group';
				$action_group_id = $_GET['group_id'];
				$redirect = remove_query_arg( array('AFFWP_AG_groups_action', 'group_id'));
			
			} else {
				//$this->admin_notification = 'You must have at least one group';
			}
		}	
		
		// Reset the group names
		foreach($affiliate_groups as $key => $data) {
			
			$group_name = '';
			
			if( isset($this->plugin_settings[$this->plugin_config['plugin_prefix'].'_'.$key.'_name']) )  :
			
				$group_name = $this->plugin_settings[$this->plugin_config['plugin_prefix'].'_'.$key.'_name'];
			
			endif;
			
			//if(empty($group_name)) $group_name = $data['name'];
			if( !empty($group_name) ) {
				$affiliate_groups[$key]['name'] = $group_name;
			} else {
				$affiliate_groups[$key]['name'] = $this->regenerate_default_group_name($key);
			}
			
		}
		
		// Save the groups setting
		array_multisort(array_keys($affiliate_groups), SORT_NATURAL, $affiliate_groups);
		update_site_option($this->plugin_config['plugin_prefix'].'_groups', $affiliate_groups );
		
		// Run the specific actions
		if( !empty( $action ) ) {
			do_action( 'affiliate_groups_'.$action, $action_group_id );
		}
		
		// Run action every time the settings are changed
		do_action( 'affiliate_groups_update_settings', $affiliate_groups );
		
		// Add / Reset the WordPRess roles
		do_action('affiliate_groups_reset', 'reset');
		
		// Redirect if action processed
		if(!empty($redirect)) wp_redirect( $redirect);
		
		// Return groups if no actions processed
		return $affiliate_groups;
		
	}
	
	// Generate default group names
	public function regenerate_default_group_name($key) {
		$key_parts = (explode('_',$key));
		$key_id = end($key_parts);
		
		return __( 'Group', 'affiliate-wp-affiliate-groups' ).' '.$key_id;
	}
	
	
	/////////////// HTML methods /////////////////
	
	// Generate the new group button
	private function generate_new_group_button() {
		$link = add_query_arg( $this->plugin_config['plugin_prefix'].'_groups_action', 'new', $_SERVER['REQUEST_URI'] );
		$html = '<a href="' . esc_url( $link ) . '" class="button-primary" target="_self">' . __( 'Create New Group', 'affiliate-wp-affiliate-groups' ) . '</a>';
		return $html;
	}
	
	// Generate the delete group button and other html
	private function generate_delete_group_html($key) {
		
			//$html = do_action( 'aff_groups_edit_group_extra_info', $key );
		
			$arguments = array(
						$this->plugin_config['plugin_prefix'].'_groups_action' => 'delete',
						'group_id' => $key,
						);
						
			$link = add_query_arg( $arguments, $_SERVER['REQUEST_URI'] );
			
			$html = '<p style="padding-top:10px">';
			$html .= '<span>ID: '.$key.'</span><br>';
			$html .= '<span style="padding-top:5px"><a href="' . esc_url( $link ) . '" class="" style="color:grey;" target="_self">' . __( 'Delete Group', 'affiliate-wp-affiliate-groups' ) . '</a></span>';
			$html .= '</p>';
			
			return $html;
	}
	
	// Generate auto grouping checkboxes
	private function generate_auto_grouping_checkboxes() {
		
		$options = array();
		
		$affiliate_groups = get_site_option($this->plugin_config['plugin_prefix'].'_groups', '');
		
		if( is_array($affiliate_groups) && count($affiliate_groups) > 0 ) {
			
			//uksort($affiliate_groups, 'natsort');
			foreach($affiliate_groups as $key => $data) {
				if($data['status'] =='1') {
					$options[$key] = __( $data['name'], '' );
				}
			}
		
		}
		
		return $options;
	}
	
	
	/////////// Licensing Methods ///////////////
	
	// Deactive license
	public function deactivate_license() {
		
	  if( 
	  (isset($_GET[$this->plugin_config['plugin_prefix'].'_license_change'])) &&
	  ($_GET[$this->plugin_config['plugin_prefix'].'_license_change'] == 'deactivate') 
	  ){
		  
		  $license = new Click_Studio_Licenses_V1_5($this->plugin_config, $this->plugin_settings);
		  if($license->deactivate_license()) {
			  
			  // Redirect to settings page
			  $location = $_SERVER['HTTP_REFERER'];
			  wp_safe_redirect($location);
		  
		  }
	  
	  }
		
	}
	
	// Get the license message actions and messages. Also activate license keys.
	public function license_status_msg() {

		$license = new Click_Studio_Licenses_V1_5($this->plugin_config, $this->plugin_settings);

		if( isset($_GET['cs_remove_license_data']) && $_GET['cs_remove_license_data'] == true ) {
		
			$license->remove_license_data();
			$license->remove_affwp_settings();
		
		}else{
		
		$license->activate_license();
		
		$license_message = $license->license_status_msg();
		return $license_message;
		
		}
	
	}
	
	// Check license status
	public function is_license_valid() {
		
		if( $this->check_if_settings_page() ) {
		
			$license = new Click_Studio_Licenses_V1_5($this->plugin_config, $this->plugin_settings);
			$status = $license->get_license_option( 'license_status' );
			
			if( !empty($status) && $status == 'valid' ) return true;
		
		}
		
		return false;
		
	}
	
} // End of class
?>