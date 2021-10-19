<?php

// Click Studio License class V1.5

if (!class_exists('Click_Studio_Licenses_V1_5')) {
class Click_Studio_Licenses_V1_5 {

# URL to check for updates, this is where the index.php script goes
public $api_url;

# Type of package to be updated
//public $settings;

protected $license_key;

function __construct($plugin_config, $plugin_settings) {
		
	$this->plugin_config = $plugin_config;
	$this->plugin_settings = $plugin_settings;
	$this->license_key = ( !empty($this->plugin_settings[$this->plugin_config['plugin_prefix'].'_'.'license_key']) ) ? $this->plugin_settings[$this->plugin_config['plugin_prefix'].'_'.'license_key'] : '';
		
	$this->capture_license_key();
	
	if( !empty($this->plugin_config['plugin_prefix']) ) :
		add_action( 'cs_updater_'.$this->plugin_config['plugin_prefix'].'_after_check', array( $this, 'check_update_license' ) );
	endif;
	
	//$this->check_update_license();

}

// Capture the license key
public function capture_license_key() {
	
	// Process if no other actions are active
	if ( !isset($_GET[$this->plugin_config['plugin_prefix'].'_license_change']) ) {
	
		//$current_key = $this->get_license_option('license_key');
		
		if( !empty($this->license_key) ) {
			
			// Set new key
			$this->set_license_option('license_key', $this->license_key);
			
		}
	
	}
}

// Get both license key and status
public function get_license_options() {
	$license_data = array(
		'license_key' => $this->get_license_option('license_key'),
		'license_status' => $this->get_license_option('license_status'),
		'activation_error_code' => $this->get_license_option('activation_error_code'),
	);
	
	return $license_data;
}

// Get a license key or status
public function get_license_option($option_key) {
	// Accepts 'license_key' or 'license_status' as the option key
	return get_site_option(  $this->plugin_config['plugin_prefix'].'_'.$option_key, '' );
}

// Set a license key or status
public function set_license_option($option_key, $value) {
	return update_site_option( $this->plugin_config['plugin_prefix'].'_'.$option_key, trim($value) );
}

// Remove a license key or status
public function remove_license_option($option_key) {
	return delete_site_option($this->plugin_config['plugin_prefix'].'_'.$option_key);
}

// Remove all license data
public function remove_license_data() {
	$license_data = $this->get_license_options();
	foreach($license_data as $key => $value) {
		//delete_site_option($this->plugin_config['plugin_prefix'].'_'.$key);
		$this->remove_license_option($key);
	}
}

// Add AffiliateWP setting
/*public function add_affwp_setting( $key, $value ) {
	
	$options = affiliate_wp()->settings->get_all();
	$options[$this->plugin_config['plugin_prefix'].'_'.$key] = $value;
	update_option( 'affwp_settings', $options );
	
}*/

// Remove AffiliateWP settings
public function remove_affwp_settings() {
	
	$options = affiliate_wp()->settings->get_all();
	unset( $options[$this->plugin_config['plugin_prefix'].'_'.'license_key'] );
	unset( $options[$this->plugin_config['plugin_prefix'].'_'.'license_status'] );
	update_option( 'affwp_settings', $options );
	
}

// $license_data->license will be either 'valid' or 'invalid' or 'deactivated'
// Activate license
public function activate_license() {

	$license_key = $this->get_license_option('license_key');
	$license_current_status = $this->get_license_option('license_status');
	
	if( !empty($license_key) && $license_current_status != 'valid' ){

		$license_data = $this->edd_api_request('activate_license');
		$license_status = $license_data->license;
		
		if( (isset($license_status)) && (!empty($license_status)) ) {
			
			if( $license_status == 'valid' ) {

				$this->remove_license_option('activation_error_code');
				$this->set_license_option('license_status', $license_status);
				$this->set_license_option('lps', 1);
				
			}elseif( $license_status == 'invalid' ) {
				
				// If expired, just leave it
				if( $license_current_status != 'expired' ) :
				
					if( isset($license_data->error) && !empty($license_data->error) ) :
					
						$this->set_license_option('license_status', $license_status);
						$this->set_license_option('activation_error_code', $license_data->error);
						
					endif;
				
				endif;
				
			}
			
		}

		return $license_data;
	
	}

}

// Deactivate license
public function deactivate_license() {

	if( 
	(isset($_GET[$this->plugin_config['plugin_prefix'].'_license_change'])) &&
	($_GET[$this->plugin_config['plugin_prefix'].'_license_change'] == 'deactivate') 
	){
		$license_key = $this->get_license_option('license_key');
		if( !empty($license_key) ) {
	
			$license_data = $this->edd_api_request('deactivate_license');
			$license_status = $license_data->license;
			if( (isset($license_status)) && ($license_status == 'deactivated') ) {
				$this->remove_license_data();
				$this->remove_affwp_settings();
				$this->set_license_option('lps', 2);
				return TRUE;
			}
	
		}
		
	}
	
	return FALSE;
	
}

// Check license status
public function check_license() {
	
	$license_key = $this->get_license_option('license_key');
	if( !empty($license_key) ) {
		
		$license_data = $this->edd_api_request('check_license');
		return $license_data->license;
	}
}

// Updater action
public function check_update_license( $version_info = '' ) {
	
	//update_option( 'cs_updater_check', 2 );
	
	$license_status = $this->check_license();
	$lps = $this->get_license_option('lps'); 
	
	if( !empty($license_status) ) :
	
		if( $license_status == 'site_inactive' || $license_status == 'inactive'|| $license_status == 'disabled') {
			
			if( !empty($lps) && $lps != 2 ) :
			
				$this->remove_license_data();
				$this->remove_affwp_settings();
				$this->set_license_option('lps', 2);
				
				return TRUE;
			
			endif;
			
		}elseif( $license_status == 'expired' ){
			
			$this->set_license_option('license_status', 'expired');
		
		// Will only reactivate an expired license, not a previously deactivated one
		}elseif( $license_status == 'valid' && (!empty($lps) && $lps != 2) ){
			
			//$this->add_affwp_setting( 'license_key', $this->get_license_option('license_key') );
			//$this->add_affwp_setting( 'license_status', 'valid' );
			$this->remove_license_option('activation_error_code');
			$this->set_license_option('license_status', 'valid');
			$this->set_license_option('lps', 1);
		}
	
	endif;

}

// EDD API request
private function edd_api_request($action) {
	// Accepts 'activate_license' or 'deactivate_license' or 'check_license'
	$api_params = array(
		'edd_action'=> $action,
		'license' 	=> $this->get_license_option('license_key'),
		'item_id'	=> $this->plugin_config['plugin_item_id'],
		'item_name' => '',
		'url'       => home_url()
	);

	$response = wp_remote_post( $this->plugin_config['plugin_updater_url'], array( 'timeout' => 15, 'body' => $api_params ) );

	if ( is_wp_error( $response ) )	{
		return false;
	} else {
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		return $license_data;
	}
	
}

// Get the license message and actions
public function license_status_msg() {
	
	$status_msg = '';

	$license_key = $this->get_license_option('license_key');
	$license_status = $this->get_license_option('license_status');
	$lps = $this->get_license_option('lps');
		
	if( 'valid' === $license_status && !empty( $license_key ) ) {
		
		$status_msg .= '<p style="color:green;">&nbsp;' . __( 'Your license is valid', 'affiliatewp-multi-level-marketing' ) . '. <br><br></p>';	
		
		//$href = add_query_arg( $this->plugin_config['plugin_prefix'].'_license_change', 'deactivate', $_SERVER['REQUEST_URI'] );
		//$href_text = 'Deactivate License';
		//$status_msg .= '<a target="_self" class="button-primary" href="'.$href.'">'.$href_text.'</a>';
	
	// If invalid and not activated = invalid license					
	} elseif( 'invalid' === $license_status && !empty( $license_key ) && $lps != '1') {	
	
		$error_code = $this->get_license_option('activation_error_code');
		if( !empty($error_code) ) $error_code = ' (error code: '.$error_code.')';
		$status_msg .= '<p style="color:red;">&nbsp;' . __( 'Your license is invalid'.$error_code, 'affiliatewp-multi-level-marketing' ) . '</p>';	
	
	// If invalid but activated = expired			
	}elseif( 'expired' === $license_status && !empty( $license_key ) && $lps == '1') {
		
		$status_msg .= '<p style="color:red;">' . __( 'Your license has expired. Plugin features remain enabled but auto updates are disabled<br><a href="https://www.clickstudio.com.au/checkout/?edd_license_key='.$license_key.'&download_id='.$this->plugin_config['plugin_item_id'].'" target="blank">Renew your license - click here</a><br><br>', 'affiliatewp-multi-level-marketing' ) . '</p>';	
		
		//$href = add_query_arg( $this->plugin_config['plugin_prefix'].'_license_change', 'deactivate', $_SERVER['REQUEST_URI'] );
		//$href_text = 'Deactivate License';
		//$status_msg .= '<a target="_self" class="button-primary" href="'.$href.'">'.$href_text.'</a>';
		
	}else{
		
		$status_msg .= '<p style="">&nbsp;' . __( 'Enter your license key and save the settings to activate.', 'affiliatewp-multi-level-marketing' ) . '</p>';
		
	}
	
	if( !empty($lps) && $lps == '2' ) {
		
		$status_msg .= '<p style="color:red;">&nbsp;' . __( 'A licence was previously deactivated for this site. License activation is now required to restore functionality.', 'affiliatewp-multi-level-marketing' ) . '</p>';
	
	}elseif( !empty($lps) && $lps == '1' ) {
		
		$href = add_query_arg( $this->plugin_config['plugin_prefix'].'_license_change', 'deactivate', $_SERVER['REQUEST_URI'] );
		$href_text = 'Deactivate License';
		$status_msg .= '<a target="_self" class="button-primary" href="'.$href.'">'.$href_text.'</a>';
		
		$status_msg .= '<p>'. __( 'Deactivating your license on this site will disable all plugin features. Be careful!', 'affiliatewp-multi-level-affiliates' ).'</p>';

	}else{

		$status_msg .= '<p>'. __( 'An activated license is required to enable plugin features.', 'affiliatewp-multi-level-affiliates' ).'</p>';

	}
		
	return $status_msg;
}

 
} // End of class

} // End of check class doesn't exist
?>