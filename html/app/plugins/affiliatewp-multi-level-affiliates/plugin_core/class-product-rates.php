<?php
// Yet to complete
class AffiliateWP_MLA_Product_Rates extends AffiliateWP_MLA_Common {
	
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
		
		// Per product settings
		//add_action( 'edd_meta_box_settings_fields', array( $this, 'mla_product_rates_meta_box_content' ), 100 );
		//add_action( 'add_meta_boxes', array( $this, 'mla_product_rates_meta_box' ) );
		//add_filter( 'edd_metabox_fields_save', array( $this, 'download_save_fields' ) );
		
		// Filter the referral amount
		//add_action( 'mla_referral_amount', array( $this, 'mla_referral_amount' ) , 15, 4 );
		//add_action( 'mla_referral_amount_groups', array( $this, 'mla_referral_amount_groups' ) , 15, 4 );

	}
	
	// Add the downloads MLA meta box
	public function mla_product_rates_meta_box() {

		$screens = array( 'download' );
	
		foreach ( $screens as $screen ) {
			add_meta_box(
				'mla-product-rates',
				__( 'MLA Product Rates', 'affiliatewp-multi-level-affiliates' ),
				array( $this, 'mla_product_rates_meta_box_content' ),
				$screen
			);
		}
	}
	
	// Add the settings to the downloads MLA meta box
	public function mla_product_rates_meta_box_content( $download_id = '' ) {
		
		$download_id = $_GET['post'];
		
		$global_disabled = $this->get_product_setting( $download_id, 'referrals_disabled', 'default' );
		$global_rate_type = $this->get_product_setting( $download_id, 'product_rate_type', 'default' );

		?>
		<div class="affwp_mla_product_settings edd">
			<div class="options_group">
            	
                <!--Global Settings-->
				<h4 class="matrix_group_heading"><?php _e( 'Global MLA Settings', 'affiliatewp-multi-level-affiliates' ); ?></h4>
				<p>
                    <label for="affwp_mla_referrals_disabled_default"> <?php _e( 'Disable referrals', 'affiliatewp-multi-level-affiliates' ); ?></label>
                    <input type="checkbox" name="_affwp_mla_referrals_disabled_default" id="affwp_mla_referrals_disabled_default" value="1"<?php checked( $global_disabled, true ); ?> />
				</p>
				
                <p>
                    <label for="_affwp_mla_product_rate_type_default"><?php _e( 'Rate Type', 'affiliatewp-multi-level-affiliates' ); ?></label>
                    <select name="_affwp_mla_product_rate_type_default" id="_affwp_mla_product_rate_type_default">
                        <option value=""><?php _e( 'Matrix Default', 'affiliatewp-multi-level-affiliates' ); ?></option>
                        <?php foreach( affwp_get_affiliate_rate_types() as $key => $type ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $global_rate_type, $key ); ?>><?php echo esc_html( $type ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
				
				<?php
				$global_levels = $this->matrix_setting( 'matrix_depth', 'default' );
				for ($x = 1; $x <= $global_levels; $x++) :
				$level_value = $this->get_product_setting( $download_id, 'product_rate', 'default', $x );
				?>
                
                <p>
					<label for="_affwp_mla_product_rate_default_level_<?php echo $x; ?>"><?php _e( 'Level', 'affiliatewp-multi-level-affiliates' ); echo ' '.$x ;?></label>
                    <input type="text" name="_affwp_mla_product_rate_default_level_<?php echo $x; ?>" id="_affwp_mla_product_rate_default_level_<?php echo $x; ?>" class="" value="<?php echo esc_attr( $level_value ); ?>" />
                 </p>
                            
       			<?php
                endfor;
				?>

				<?php
				/* Groups Settings */
				if( $this->groups_enabled() ) :
				
					$groups = get_active_affiliate_groups();
					foreach( $groups as $id => $group_data ) :
					if( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :
					
						$group_disabled = $this->get_product_setting( $download_id, 'referrals_disabled', $id );
						$group_rate_type = $this->get_product_setting( $download_id, 'product_rate_type', $id );
					?>
                    	
						<h4 class="matrix_group_heading"><?php echo $group_data['name'] .' '. __( 'MLA Settings', 'affiliatewp-multi-level-affiliates' ); ?></h4>
                        <p>
                            <label for="affwp_mla_referrals_disabled_<?php echo $id ;?>"><?php _e( 'Disable referrals', 'affiliatewp-multi-level-affiliates' ); ?></label>
                            <input type="checkbox" name="_affwp_mla_referrals_disabled_<?php echo $id ;?>" id="affwp_mla_referrals_disabled_<?php echo $id ;?>" value="1"<?php checked( $group_disabled, true ); ?> />  
                        </p>
                        
                        <p>
                            <label for="_affwp_mla_product_rate_type_<?php echo $id ;?>"><?php _e( 'Rate Type', 'affiliatewp-multi-level-affiliates' ); ?></label>
                            <select name="_affwp_mla_product_rate_type_<?php echo $id ;?>" id="_affwp_mla_product_rate_type_<?php echo $id ;?>">
                                <option value=""><?php _e( 'Matrix Default', 'affiliatewp-multi-level-affiliates' ); ?></option>
                                <?php foreach( affwp_get_affiliate_rate_types() as $key => $type ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $group_rate_type, $key ); ?>><?php echo esc_html( $type ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    
                    	<?php
						$group_levels = $this->matrix_setting( 'matrix_depth', $id );
						for ($x = 1; $x <= $group_levels; $x++) :
						$level_value = $this->get_product_setting( $download_id, 'product_rate', $id, $x );
						?>
                         <p>
                            <label for="_affwp_mla_product_rate_<?php echo $id ;?>_level_<?php echo $x; ?>"><?php _e( 'Level', 'affiliatewp-multi-level-affiliates' ); echo ' '.$x ;?></label>   
                            <input type="text" name="_affwp_mla_edd_product_rate_<?php echo $id ;?>_level_<?php echo $x; ?>" id="_affwp_mla_product_rate_<?php echo $id ;?>_level_<?php echo $x; ?>" class="" value="<?php echo esc_attr( $level_value ); ?>" />
                         </p>
                        <?php

						endfor;
					
					endif;
					endforeach;

				endif;
				
				?>
			</div>
		</div>
	
	<?php
	}

	// Save the settings.
	// Re-do for post based integrations
	public function download_save_fields( $fields = array() ) {
		
		$fields[] = '_affwp_mla_referrals_disabled_default';
		$fields[] = '_affwp_mla_product_rate_type_default';
		
		$global_levels = $this->matrix_setting( 'matrix_depth', 'default' );
		for ($x = 1; $x <= $global_levels; $x++) :
		
			$fields[] = '_affwp_mla_product_rate_default_level_'.$x;
			
		endfor;
		
		// Groups
		if( $this->groups_enabled() ) :
		
			$groups = get_active_affiliate_groups();
			foreach( $groups as $id => $group_data ) :
			
				if( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :
				
					$fields[] = '_affwp_mla_referrals_disabled_'.$id;
					$fields[] = '_affwp_mla_product_rate_type_'.$id;
					
					$group_levels = $this->matrix_setting( 'matrix_depth', $id );
					for ($x = 1; $x <= $group_levels; $x++) :
					
						$fields[] = '_affwp_mla_product_rate_'.$id.'_level_'.$x;
					
					endfor;
				
				endif;
						
			endforeach;
			
		endif;
		
		return $fields;
	}
	
	// Global product rules.
	// Same in all integrations
	public function mla_referral_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars ) {
		
		$commission = $this->get_commission_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars );
		
		return $commission;
	
	}
	
	// Group product rules. Filters each referral amount at each level found by the groups class
	// Loop through all of an affiliate's active groups and return the highest commission (multiple groups per affiliate)
	// Same in all integrations
	public function mla_referral_amount_groups( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars ) {

		$commission = 0;

		$affiliate_id = $filter_vars['affiliate_id'];
		
		$groups = array();
		if( !empty($affiliate_id) ) $groups = get_affiliates_active_groups( $affiliate_id ); 
		
		if( !empty($groups) ) :
		
			foreach ( $groups as $group_id => $group_name ) :
			
				$group_commission = 0;
			
				$filter_vars['group_id'] = $group_id;
				$group_commission = $this->get_commission_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars );
				
				if( $group_commission > $commission ) $commission = $group_commission;
			
			endforeach;
		
		endif;
		
		return $commission;
	
	}
	
	// Regenerate the referral amount based on product settings
	// Same in all integrations
	public function get_commission_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars ) {

		//$order_id = $matrix_data['args']['reference'];
		$level = $filter_vars['level'] ;
		
		$group_id = ( isset($filter_vars['group_id']) && !empty($filter_vars['group_id']) ) ? $filter_vars['group_id'] : 'default';
		
		$product_order_data = $this->get_product_order_data( $matrix_data, $group_id );
		
		$commission = 0;
		
		foreach ($product_order_data['products'] as $product_id => $product_data ) :
		
			$product_order_total = $product_data['total'];
			$rate_type = $this->get_product_setting( $product_id, 'product_rate_type', $group_id, $level);
			$rate_value = $this->get_product_setting( $product_id, 'product_rate', $group_id, $level);
			
			// Fallback to standard per level rates
			$product_order_total = ( isset($product_order_total) ) ? $product_order_total : $default_referral_vars['base_amount'];
			$rate_type = ( !empty($rate_type) ) ? $rate_type : $default_referral_vars['rate_type'];
			$rate_value = ( !empty($rate_value) || $rate_value == '0' ) ? $rate_value : $default_referral_vars['rate_value'];
			
			if( !empty($product_order_total) && !empty($rate_type) && !empty($rate_value) ) :
			
				$product_commission_amount = AffiliateWP_MLA_Referral::calculate_referral_amount( $rate_type, $rate_value, $product_order_total );
				$commission += $product_commission_amount;
			
			endif;

		endforeach;
		
		return $commission;
		
	}
	
	// Get order details
	// Same in all post based integrations
	public function get_product_order_data( $matrix_data, $group_id = 'default' ) {
		
	  $products = $matrix_data['products'];
	  if( !empty($products) ) :
	  
		  foreach( $products as $key => $product ) {
			  
			  // Check if product disabled
			  if ( !$this->get_product_setting( $product['id'], 'referrals_disabled', $group_id ) ) :

			  	$order_data['products'][$product['id']]['total'] = $product['price'];
			  	$order_data['total'] += $product['price'];
			  
			  endif;

		  }
		  
	  endif;
	  
	  return $order_data;

	}
	
	// Get product setting
	// keys: referrals_disabled, product_rate_type, product_rate (requires level)
	// Same in all post based integrations
	public function get_product_setting( $product_id, $key, $group_id = 'default', $level = '', $fallback_to_parent = true ) {

		$get_key = '_affwp_mla_'.$key.'_'.$group_id;
		$get_key = ( !empty($level) && $key == 'product_rate' ) ? $get_key.'_level_'.$level : $get_key;
		
		$setting = get_post_meta( $product_id, $get_key, true );

		return $setting;
		
	}

	
} // end class

?>