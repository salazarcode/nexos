<?php

class AffiliateWP_MLA_Common {
	
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
		
		add_shortcode( 'mla_output_debug_data', array( $this, 'mla_output_debug_data' ) );	
	}
	
	// Get a plugin setting
	public function plugin_setting( $key ) {
		
		if( !empty($this->plugin_settings[$this->plugin_config['plugin_prefix'].'_'.$key ]) ) {
			return $this->plugin_settings[$this->plugin_config['plugin_prefix'].'_'.$key ];
		}else {
			return NULL;
		}
		
	}
	
	// Get matrix setting
	public function matrix_setting($setting_key, $group_id) {
		
		$value = AffiliateWP_Multi_Level_Affiliates_Settings::get_matrix_setting($setting_key, $group_id);
		
		return $value;	
	}
	
	// Get the MLA mode
	public function mla_mode() {

		//$mla_mode = $this->plugin_setting( 'mla_mode' );
		
		// Default set on activation but return 'matrix' as default as a backup
		if( empty($mla_mode) ) $mla_mode = 'matrix';
		
		return $mla_mode;
	}
	
	// Get order total - new method
	public function get_order_total() {
		
		$default_total = 0.00;
		
		$saved_total = get_transient( 'mla_referral_order_total' );
		$order_total = ( $saved_total > 0.00 ) ? $saved_total : $default_total;
		
		return $order_total;
		
	}
	
	// Get referrals total - new method
	public function get_commissions_total( $matrix_data ) {
		
		$referrals = $matrix_data['referrals'];
		$commission_total = 0;

		foreach( $referrals as $key => $data ) :
		
			$referral_amount = $data['referral_total'];
			$commission_total += $referral_amount;
		
		endforeach;
		
		return $commission_total;
		
	}
	
	// Check if Affiliate Groups enabled
	public function groups_enabled() {
		if ( class_exists('AffiliateWP_Affiliate_Groups') ) return (bool) TRUE;
	}
	
	// Get WordPress timezone
	public function wp_get_timezone_string() {
 
		// if site timezone string exists, return it
		if ( $timezone = get_option( 'timezone_string' ) )
			return $timezone;
	 
		// get UTC offset, if it isn't set then return UTC
		if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
			return 'UTC';
	 
		// adjust UTC offset from hours to seconds
		$utc_offset *= 3600;
	 
		// attempt to guess the timezone string from the UTC offset
		if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
			return $timezone;
		}
	 
		// last try, guess timezone string manually
		$is_dst = date( 'I' );
	 
		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset )
					return $city['timezone_id'];
			}
		}
		 
		// fallback to UTC
		return 'UTC';
	}
	
	
	// Store debug data
	public function store_debug_data($data) {
		$data = maybe_serialize( $data );
		set_transient( 'mla_data', $data, 12 * HOUR_IN_SECONDS );
	}
	
	// Output debug data
	public function mla_output_debug_data() {
		
		echo '<br><br>'.'---------------------- MLA Debug Data ----------------------'.'<br><br><br><br>';
		//print_r( get_transient( 'mla_data') );
		//print_r( maybe_unserialize( get_transient( 'mla_data') ) );
		//echo $mla_parent = affwp_get_affiliate_meta( '56', 'mla_level_1', TRUE );
		
		//$affwp_mla = affiliate_wp_mla();
		/*$settings = affiliate_wp_mla()->plugin_settings;
		$config = affiliate_wp_mla()->plugin_config;
		echo '<pre>'; print_r($settings); echo '</pre>';
		echo '<pre>'; print_r($config); echo '</pre>';*/
		
		echo '<br><br><br><br>'.'---------------------- END MLA Debug Data ----------------------'.'<br><br>';
		
	}
	
	// Calculate Percentage Remainder
	public function calculate_percentage_remainder( $max_percentage, $matrix_data ) {

		$commission_total = $this->get_commissions_total($matrix_data);
		//update_option( 'remainder_total1', $commission_total);
		$order_total = apply_filters( 'mla_percentage_remainder_referral_base_amount', $matrix_data['matrix_order_total'], array() );
		//update_option( 'remainder_total2', $order_total);
		$max_commission = AffiliateWP_MLA_Referral::calculate_referral_amount( 'percentage', $max_percentage, $order_total );
		//update_option( 'remainder_total3', $max_commission);

		if( $max_commission > $commission_total ) {

			$percentage_remainder = ($max_commission - $commission_total);
			update_option( 'remainder_total4', $max_commission);
			return $percentage_remainder;

		}else {

			return 0;

		}

	}

	public function get_purchaser_from_parent_referral($parent_referral_id){
    //$parent_referral_id = $args['parent_id'];
    $parent_referral_object = affwp_get_referral($parent_referral_id);
    $customer_id = $parent_referral_object->customer_id;
    $customer_object = affwp_get_customer($customer_id);
    $user_id = $customer_object->user_id;

    return $user_id;
  }
	
}

?>