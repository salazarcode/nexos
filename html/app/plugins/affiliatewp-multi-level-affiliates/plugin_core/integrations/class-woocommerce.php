<?php

class AffiliateWP_MLA_WooCommerce extends AffiliateWP_MLA_Common {
	
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
		
		$this->context = 'woocommerce';
		
		// Per product settings
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'product_settings' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		
		// Variable per product settings
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_settings' ), 100, 3 );
		add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'save_variation_data' ) );
		
		// Filter the referral amount
		//add_filter( 'mla_referral_amount', array( $this, 'mla_referral_amount' ) , 15, 4 );
		
		// Filter the referral
		add_filter( 'mla_referral', array( $this, 'mla_referral' ) , 15, 4 );
		
		// Filter the groups referral amount
		//add_filter( 'mla_referral_amount_groups', array( $this, 'mla_referral_amount_groups' ) , 15, 4 );
		
		// Filter the groups referral
		add_filter( 'mla_referral_groups', array( $this, 'mla_referral_groups' ) , 15, 4 );

		// Per product multipliers
    add_filter('mla_product_referral_amount_woocommerce', array($this, 'mla_product_referral_amount_woocommerce'), 10, 2);
		
	}
	
	// Add product settings tab
	public function product_tab( $tabs ) {

		$tabs['affiliate_wp_mla'] = array(
			'label'  => __( 'AffiliateWP MLA', 'affiliatewp-multi-level-affiliates' ),
			'target' => 'affwp_mla_product_settings',
			'class'  => array( ),
		);

		return $tabs;

	}
	
	// Add product settings to tab
	public function product_settings() {

		global $post;

		?>
		<div id="affwp_mla_product_settings" class="panel woocommerce_options_panel">

			<div class="options_group">
				<h4 class="matrix_group_heading"><?php _e( 'Global MLA Settings', 'affiliatewp-multi-level-affiliates' ); ?></h4>
				<?php

				/* Global Settings */
				woocommerce_wp_checkbox( array(
					'id'          => '_affwp_mla_woocommerce_referrals_disabled_default',
					'label'       => __( 'Disable referrals', 'affiliatewp-multi-level-affiliates' ),
					/*'description' => __( '' ).' '. $group_data['name'],*/
					'cbvalue'     => 1
				) );
				
				woocommerce_wp_select( array(
					'id'          => '_affwp_mla_woocommerce_product_rate_type_default',
					'label'       => __( 'Rate Type', 'affiliatewp-multi-level-affiliates' ),
					'options'     => array_merge( array( '' => __( 'Matrix Default', 'affiliatewp-multi-level-affiliates' ) ), affwp_get_affiliate_rate_types() ),
					'desc_tip'    => true,
					/*'description' => __( '', 'affiliatewp-multi-level-affiliates' ),*/
				) );
				
				$global_levels = $this->matrix_setting( 'matrix_depth', 'default' );
				for ($x = 1; $x <= $global_levels; $x++) :
					woocommerce_wp_text_input( array(
						'id'          => '_affwp_mla_woocommerce_product_rate_default_level_'.$x,
						'label'       => __( 'Level', 'affiliatewp-multi-level-affiliates' ).' '.$x,
						'desc_tip'    => true,
						/*'description' => __( '', 'affiliatewp-multi-level-affiliates' )*/
					) );
       			endfor;
				
				/* Groups Settings */
				if( $this->groups_enabled() ) :
				
					$groups = get_active_affiliate_groups();
					foreach( $groups as $id => $group_data ) :
					if( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :
					
						?>
                        <h4 class="matrix_group_heading"><?php echo $group_data['name'] .' '. __( 'MLA Settings ', 'affiliatewp-multi-level-affiliates' ); ?></h4>
                        <?php
						
						woocommerce_wp_checkbox( array(
							'id'          => '_affwp_mla_woocommerce_referrals_disabled_'.$id,
							'label'       => __( 'Disable referrals', 'affiliate-wp' ),
							/*'description' => __( '' ).' '. $group_data['name'], 'affiliatewp-multi-level-affiliates' */
							'cbvalue'     => 1
						) );
						
						woocommerce_wp_select( array(
							'id'          => '_affwp_mla_woocommerce_product_rate_type_'.$id,
							'label'       => __( 'Rate Type', 'affiliatewp-multi-level-affiliates' ),
							'options'     => array_merge( array( '' => __( 'Matrix Default', 'affiliatewp-multi-level-affiliates' ) ), affwp_get_affiliate_rate_types() ),
							'desc_tip'    => true,
							/*'description' => __( '', 'affiliatewp-multi-level-affiliates' ),*/
						) );
						
						$group_levels = $this->matrix_setting( 'matrix_depth', $id );
						for ($x = 1; $x <= $group_levels; $x++) :
							woocommerce_wp_text_input( array(
								'id'          => '_affwp_mla_woocommerce_product_rate_'.$id.'_level_'.$x,
								'label'       => __( 'Level', 'affiliatewp-multi-level-affiliates' ).' '.$x,
								'desc_tip'    => true,
								/*'description' => __( '', 'affiliatewp-multi-level-affiliates' )*/
							) );
						endfor;
					
					endif;
					endforeach;

				endif;

				wp_nonce_field( 'affwp_mla_woo_product_nonce', 'affwp_mla_woo_product_nonce' );
				
				?>
			</div>
		</div>
	<?php
	}
	
	// Save settings from tab
	public function save_meta( $post_id = 0 ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		if( empty( $_POST['affwp_mla_woo_product_nonce'] ) || ! wp_verify_nonce( $_POST['affwp_mla_woo_product_nonce'], 'affwp_mla_woo_product_nonce' ) ) {
			return $post_id;
		}

		$post = get_post( $post_id );

		if( ! $post ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'product' != $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		/* Global Settings */
		if( isset( $_POST['_affwp_mla_' . $this->context . '_referrals_disabled_default'] ) ) {

			update_post_meta( $post_id, '_affwp_mla_' . $this->context . '_referrals_disabled_default', 1 );

		} else {

			delete_post_meta( $post_id, '_affwp_mla_' . $this->context . '_referrals_disabled_default' );

		}
		
		if( ! empty( $_POST['_affwp_mla_' . $this->context . '_product_rate_type_default'] ) ) {

			$rate_type = sanitize_text_field( $_POST['_affwp_mla_' . $this->context . '_product_rate_type_default'] );
			update_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_type_default', $rate_type );

		} else {

			delete_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_type_default' );

		}
		
		$global_levels = $this->matrix_setting( 'matrix_depth', 'default' );
		for ($x = 1; $x <= $global_levels; $x++) :
		
			if( isset( $_POST['_affwp_mla_' . $this->context . '_product_rate_default_level_'.$x] ) ) {

			$rate = sanitize_text_field( $_POST['_affwp_mla_' . $this->context . '_product_rate_default_level_'.$x] );
			update_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_default_level_'.$x, $rate );

			} else {
	
				delete_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_default_level_'.$x );
	
			}
		
		endfor;
		
		/* Groups Settings */
		if( $this->groups_enabled() ) :
				
			$groups = get_active_affiliate_groups();
			
			foreach( $groups as $id => $group_data ) :
			if( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :
			
				if( isset( $_POST['_affwp_mla_' . $this->context . '_referrals_disabled_'.$id] ) ) {
		
					update_post_meta( $post_id, '_affwp_mla_' . $this->context . '_referrals_disabled_'.$id, 1 );
		
				} else {
		
					delete_post_meta( $post_id, '_affwp_mla_' . $this->context . '_referrals_disabled_'.$id );
		
				}
				
				if( ! empty( $_POST['_affwp_mla_' . $this->context . '_product_rate_type_'.$id] ) ) {
		
					$rate_type = sanitize_text_field( $_POST['_affwp_mla_' . $this->context . '_product_rate_type_'.$id] );
					update_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_type_'.$id, $rate_type );
		
				} else {
		
					delete_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_type_'.$id );
		
				}
				
				$group_levels = $this->matrix_setting( 'matrix_depth', $id );
				for ($x = 1; $x <= $group_levels; $x++) :
				
					if( isset( $_POST['_affwp_mla_' . $this->context . '_product_rate_'.$id.'_level_'.$x] ) ) {
		
					$rate = sanitize_text_field( $_POST['_affwp_mla_' . $this->context . '_product_rate_'.$id.'_level_'.$x] );
					update_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_'.$id.'_level_'.$x, $rate );
		
					} else {
			
						delete_post_meta( $post_id, '_affwp_mla_' . $this->context . '_product_rate_'.$id.'_level_'.$x );
			
					}
				
				endfor;
			
			endif;
			endforeach;
		
		endif;
		/* End Groups Settings */
		
		$this->save_variation_data( $post_id );
		
	}
	
	// Add variable product settings
	public function variation_settings( $loop, $variation_data, $variation ) {

		$disabled  = get_post_meta( $variation->ID, '_affwp_mla_' . $this->context . '_referrals_disabled_default', true );
		$rate_type = get_post_meta( $variation->ID, '_affwp_mla_' . $this->context . '_product_rate_type_default', true );
		
		?>

		<div id="affwp_mla_product_variation_settings">

			<h4 class="matrix_group_heading"><?php _e( 'Global MLA Settings', 'affiliatewp-multi-level-affiliates' ); ?></h4>
            
            <p class="form-row form-row-full options">
				<label for="_affwp_mla_woocommerce_variation_referrals_disabled_default[<?php echo $variation->ID; ?>]">
					<input type="checkbox" class="checkbox" name="_affwp_mla_woocommerce_variation_referrals_disabled_default[<?php echo $variation->ID; ?>]" id="_affwp_mla_woocommerce_variation_referrals_disabled_default[<?php echo $variation->ID; ?>]" <?php checked( $disabled, true ); ?> /> <?php _e( 'Disable referrals for this product variation', 'affiliatewp-multi-level-affiliates' ); ?>
				</label>
			</p>

			<p class="form-row form-row-full">
				<label for="_affwp_mla_woocommerce_variation_rate_types_default[<?php echo $variation->ID; ?>]"><?php echo __( 'Referral Rate Type', 'affiliatewp-multi-level-affiliates' ); ?></label>
				<select name="_affwp_mla_woocommerce_variation_rate_types_default[<?php echo $variation->ID; ?>]" id="_affwp_mla_woocommerce_variation_rate_types_default[<?php echo $variation->ID; ?>]">
					<option value=""><?php _e( 'Site Default', 'affiliatewp-multi-level-affiliates' ); ?></option>
					<?php foreach( affwp_get_affiliate_rate_types() as $key => $type ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $rate_type, $key ); ?>><?php echo esc_html( $type ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
            
            <?php
            $global_levels = $this->matrix_setting( 'matrix_depth', 'default' );
			for ($x = 1; $x <= $global_levels; $x++) :
			$default_rate_level = get_post_meta( $variation->ID,  '_affwp_mla_woocommerce_product_rate_default_level_'.$x , true );
			?>
                
              <p class="form-row form-row-full">
                  <label for="_affwp_mla_woocommerce_variation_rates_default_level_<?php echo $x; ?>[<?php echo $variation->ID; ?>]"><?php echo __( 'Level', 'affiliatewp-multi-level-affiliates' ); echo ' '.$x ;?></label>
                  <input type="text" size="5" name="_affwp_mla_woocommerce_variation_rates_default_level_<?php echo $x; ?>[<?php echo $variation->ID; ?>]" value="<?php echo esc_attr( $default_rate_level ); ?>" class="wc_input_price" id="_affwp_mla_woocommerce_variation_rates_default_level_<?php echo $x; ?>[<?php echo $variation->ID; ?>]" placeholder="" />
              </p>

			<?php
            endfor;

            /* Groups Settings */
			if( $this->groups_enabled() ) :
			
				$groups = get_active_affiliate_groups();
				foreach( $groups as $id => $group_data ) :
				if( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :
				
				$disabled  = get_post_meta( $variation->ID, '_affwp_mla_' . $this->context . '_referrals_disabled_'.$id, true );
				$rate_type = get_post_meta( $variation->ID, '_affwp_mla_' . $this->context . '_product_rate_type_'.$id, true );
				?>
                
                    <h4 class="matrix_group_heading"><?php echo $group_data['name'] .' '. __( 'MLA Settings', 'affiliatewp-multi-level-affiliates' ); ?></h4>
                    
                    <p class="form-row form-row-full options">
                        <label for="_affwp_mla_woocommerce_variation_referrals_disabled_<?php echo $id; ?>[<?php echo $variation->ID; ?>]">
                            <input type="checkbox" class="checkbox" name="_affwp_mla_woocommerce_variation_referrals_disabled_<?php echo $id; ?>[<?php echo $variation->ID; ?>]" id="_affwp_mla_woocommerce_variation_referrals_disabled_<?php echo $id; ?>[<?php echo $variation->ID; ?>]" <?php checked( $disabled, true ); ?> /> <?php _e( 'Disable referrals for this product variation', 'affiliatewp-multi-level-affiliates' ); ?>
                        </label>
                    </p>
        
                    <p class="form-row form-row-full">
                        <label for="_affwp_mla_woocommerce_variation_rate_types_<?php echo $id; ?>[<?php echo $variation->ID; ?>]"><?php echo __( 'Referral Rate Type', 'affiliatewp-multi-level-affiliates' ); ?></label>
                        <select name="_affwp_mla_woocommerce_variation_rate_types_<?php echo $id; ?>[<?php echo $variation->ID; ?>]" id="_affwp_mla_woocommerce_variation_rate_types_<?php echo $id; ?>[<?php echo $variation->ID; ?>]">
                            <option value=""><?php _e( 'Matrix Default', 'affiliatewp-multi-level-affiliates' ); ?></option>
                            <?php foreach( affwp_get_affiliate_rate_types() as $key => $type ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $rate_type, $key ); ?>><?php echo esc_html( $type ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    
                    <?php
                    $group_levels = $this->matrix_setting( 'matrix_depth', $id );
                    for ($x = 1; $x <= $group_levels; $x++) :
					$group_rate_level = get_post_meta( $variation->ID, '_affwp_mla_' . $this->context .'_product_rate_'.$id.'_level_'.$x , true );
					?>
                        
                      <p class="form-row form-row-full">
                          <label for="_affwp_mla_woocommerce_variation_rates_<?php echo $id; ?>_level_<?php echo $x; ?>[<?php echo $variation->ID; ?>]"><?php echo __( 'Level', 'affiliatewp-multi-level-affiliates' ); echo ' '.$x ;?></label>
                          <input type="text" size="5" name="_affwp_mla_woocommerce_variation_rates_<?php echo $id; ?>_level_<?php echo $x; ?>[<?php echo $variation->ID; ?>]" value="<?php echo esc_attr( $group_rate_level ); ?>" class="wc_input_price" id="_affwp_mla_woocommerce_variation_rates_<?php echo $id; ?>_level_<?php echo $x; ?>[<?php echo $variation->ID; ?>]" placeholder="" />
                      </p>
					
					<?php
            		endfor;

				endif;
				endforeach;
				
			endif;
			?>

		</div>

		<?php

	}
	
	// Save variable product settings
	public function save_variation_data( $product_id = 0 ) {

		if( ! empty( $_POST['variable_post_id'] ) && is_array( $_POST['variable_post_id'] ) ) {

			foreach( $_POST['variable_post_id'] as $variation_id ) {
				
				update_post_meta( $variation_id, 'post_data', $_POST );

				$variation_id = absint( $variation_id );

				/* Global Variation Settings */
				if( ! empty( $_POST['_affwp_mla_woocommerce_variation_referrals_disabled_default'] ) && ! empty( $_POST['_affwp_mla_woocommerce_variation_referrals_disabled_default'][ $variation_id ] ) ) {

					update_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_referrals_disabled_default', 1 );

				} else {

					delete_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_referrals_disabled_default' );

				}
				
				if( ! empty( $_POST['_affwp_mla_woocommerce_variation_rate_types_default'] ) && ! empty( $_POST['_affwp_mla_woocommerce_variation_rate_types_default'][ $variation_id ] ) ) {

					$rate_type = sanitize_text_field( $_POST['_affwp_mla_woocommerce_variation_rate_types_default'][ $variation_id ] );
					update_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_type_default', $rate_type );

				} else {

					delete_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_type_default' );

				}

				$global_levels = $this->matrix_setting( 'matrix_depth', 'default' );
				for ($x = 1; $x <= $global_levels; $x++) :
				
					if( isset( $_POST['_affwp_mla_' . $this->context . '_variation_rates_default_level_'.$x][ $variation_id ] ) ) {
		
					$rate = sanitize_text_field( $_POST['_affwp_mla_' . $this->context . '_variation_rates_default_level_'.$x][ $variation_id ] );
					update_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_default_level_'.$x, $rate );
		
					} else {
			
						delete_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_default_level_'.$x );
			
					}
				
				endfor;
				
				/* Groups Settings */
				if( $this->groups_enabled() ) :
						
					$groups = get_active_affiliate_groups();
					
					foreach( $groups as $id => $group_data ) :
					if( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :
					
						if( isset( $_POST['_affwp_mla_' . $this->context . '_variation_referrals_disabled_'.$id][ $variation_id ] ) ) {
				
							update_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_referrals_disabled_'.$id, 1 );
				
						} else {
				
							delete_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_referrals_disabled_'.$id );
				
						}
						
						if( ! empty( $_POST['_affwp_mla_' . $this->context . '_variation_rate_types_'.$id] ) ) {
				
							$rate_type = sanitize_text_field( $_POST['_affwp_mla_' . $this->context . '_variation_rate_types_'.$id][ $variation_id ] );
							update_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_type_'.$id, $rate_type );
				
						} else {
				
							delete_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_type_'.$id );
				
						}
						
						$group_levels = $this->matrix_setting( 'matrix_depth', $id );
						for ($x = 1; $x <= $group_levels; $x++) :
						
							if( isset( $_POST['_affwp_mla_' . $this->context . '_variation_rates_'.$id.'_level_'.$x][ $variation_id ] ) ) {
				
							$rate = sanitize_text_field( $_POST['_affwp_mla_' . $this->context . '_variation_rates_'.$id.'_level_'.$x][ $variation_id ] );
							update_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_'.$id.'_level_'.$x, $rate );
				
							} else {
					
								delete_post_meta( $variation_id, '_affwp_mla_' . $this->context . '_product_rate_'.$id.'_level_'.$x );
					
							}
						
						endfor;
					
					endif;
					endforeach;
				
				endif;
				/* End Groups Settings */

			}

		}

	}
	
	// Global product rules
	// Same in all integrations
	/*
	public function mla_referral_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars ) {
		
		$context = $matrix_data['args']['context'];
		if( $context != $this->context )  :
			return $referral_amount;
		endif;
		
		$commission = $this->get_commission_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars );
		
		return $commission;
	
	}
	*/
	
	public function mla_referral( $referral, $matrix_data, $filter_vars, $default_referral_vars ) {
		
		//$referral_amount = $referral['referral_total'];
		
		$context = $matrix_data['args']['context'];
		if( $context != $this->context )  :
			return $referral;
		endif;
		
		$new_referral_data = $this->get_referral_data( $referral, $matrix_data, $filter_vars, $default_referral_vars );

		$new_referral_amount = $new_referral_data['referral_total'];
		$new_referral_log = $new_referral_data['log'];
		
		if( $new_referral_data['referral_total'] != $referral['referral_total'] ) :
		
			$referral['referral_total'] = $new_referral_data['referral_total'];
			$referral['log'][] = __( 'Amount modified by MLA global product rates', 'affiliatewp-multi-level-affiliates' ).': '.$new_referral_amount;
		
		endif;
		
		if( !empty($new_referral_data['log']) ) $referral['log']['per_product'] = $new_referral_data['log'];
		
		return $referral;
	
	}
		

	// Group product rules. Filters each referral amount at each level found by the groups class
	// Loop through all of an affiliate's active groups and return the highest commission (multiple groups per affiliate)
	// Same in all integrations
	/*
	public function mla_referral_amount_groups( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars ) {
		
		$context = $matrix_data['args']['context'];
		if( $context != $this->context )  :
			return $referral_amount;
		endif;

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
	*/
	public function mla_referral_groups( $referral, $matrix_data, $filter_vars, $default_referral_vars ) {
		
		//$referral_amount = $referral['referral_total'];
		
		$context = $matrix_data['args']['context'];
		if( $context != $this->context )  :
			return $referral;
		endif;

		$new_referral_amount = 0;
		$new_referral_log = array();

		$affiliate_id = $filter_vars['affiliate_id'];
		
		// Loop through all affiliate's groups and find the best
		$groups = array();
		if( !empty($affiliate_id) ) $groups = get_affiliates_active_groups( $affiliate_id ); 
		
		if( !empty($groups) ) :
		
			foreach ( $groups as $group_id => $group_name ) :
			
				$group_referral_amount = 0;
				$group_referral_log = array();
			
				$filter_vars['group_id'] = $group_id;
				
				$group_referral_data = $this->get_referral_data( $referral, $matrix_data, $filter_vars, $default_referral_vars );

				$group_referral_amount = $group_referral_data['referral_total'];
				$group_referral_log = $group_referral_data['log'];
				
				if( $group_referral_data['referral_total'] > $new_referral_amount ) : 
				
					$new_referral_amount = $group_referral_data['referral_total'];
					$new_referral_log = $group_referral_data['log'];
					
				endif;
			
			endforeach;
		
		endif;
		
		if ( $new_referral_amount != $referral['referral_total'] ) :
		
			$referral['referral_total'] = $new_referral_amount;
			$referral['log'][] = __( 'Amount modified by MLA group product rates', 'affiliatewp-multi-level-affiliates' ).': '.$new_referral_amount;
			
		endif;
		
		if( !empty($new_referral_log) ) $referral['log']['groups_per_product'] = $new_referral_log;
		
		return $referral;
	
	}
	
	// Regenerate the referral amount and log based on product settings
	// Same in all integrations
	public function get_referral_data( $referral, $matrix_data, $referral_filter_vars, $default_referral_vars ) {
		
		//$referral_amount = $referral['referral_total'];

		//$order_id = $matrix_data['args']['reference'];
		$level = $referral_filter_vars['level'] ;
		
		$group_id = ( isset($referral_filter_vars['group_id']) && !empty($referral_filter_vars['group_id']) ) ? $referral_filter_vars['group_id'] : 'default';
		
		$product_order_data = $this->get_product_order_data( $matrix_data, $group_id );
		
		//$return_data = array( 'referral_total' => 0, 'log' => array( 'products' => array() ) );
		$return_data = array( 'referral_total' => 0, 'log' => array() );
		
		foreach ($product_order_data['products'] as $product_id => $product_data ) :
		
			$per_product_log = array();
		
			$product_order_total = $product_data['total'];
			$rate_type = $this->get_product_setting( $product_id, 'product_rate_type', $group_id, $level);
			$rate_value = $this->get_product_setting( $product_id, 'product_rate', $group_id, $level);
			
			// Fallback to standard per level rates
			$product_order_total = ( isset($product_order_total) ) ? $product_order_total : $default_referral_vars['base_amount'];
			$rate_type = ( !empty($rate_type) ) ? $rate_type : $default_referral_vars['rate_type'];
			$rate_value = ( !empty($rate_value) || $rate_value == '0' ) ? $rate_value : $default_referral_vars['rate_value'];

			$product_order_total = apply_filters( 'mla_product_referral_order_total_'.$this->context, $product_order_total, array( 'product_id' => $product_id, 'product_data' => $product_data ) );

			//if( !empty($product_order_total) && !empty($rate_type) && !empty($rate_value) ) :
      if ( !empty($rate_type) && !empty($rate_value) ) :
			
				$product_referral_amount = AffiliateWP_MLA_Referral::calculate_referral_amount( $rate_type, $rate_value, $product_order_total );
				
				$product_filter_vars = array(
					'rate_type' => $rate_type, 
					'rate_value' => $rate_value, 
					'base_amount' => $product_order_total,
					'product_id' => $product_id,
					'product_data' => $product_data,
					'matrix_data' => $matrix_data,
					'referral_filter_vars' => $referral_filter_vars	
				);		
				$product_referral_amount = apply_filters( 'mla_product_referral_amount_'.$this->context, $product_referral_amount, $product_filter_vars );
				
				// add the amount to the per product log
				$per_product_log['referral_amount'] = $product_referral_amount;
				
				$product_referral = array(
					'product_referral_amount' => $product_referral_amount,
					'product_referral_log' => $per_product_log
				);				
				$product_referral = apply_filters( 'mla_product_referral_'.$this->context, $product_referral, $product_filter_vars );
				
				// Add the return data - total
				$return_data['referral_total'] += $product_referral['product_referral_amount'];
				
				// Add the return data -log
				if( !empty($product_referral['product_referral_log']) ) :
					$return_data['log']['products'][$product_id] = $product_referral['product_referral_log'];
				endif;
				
				//$product_commission_amount = apply_filters( 'mla_product_referral_amount_'.$this->context, $product_commission_amount, $product_filter_vars );
				//$return_data['referral_total'] += $product_commission_amount;
			
			endif;

		endforeach;

		return $return_data;
		
	}
	
	// Get order details
	public function get_product_order_data( $matrix_data, $group_id = 'default' ) {
		
		$order_id = $matrix_data['args']['reference'];
		
		$order = new WC_Order($order_id);
		$order_data = array( 'total' => 0, 'products' => array() );
 
		$items = $order->get_items();
		
		// Shipping total
		$order_shipping = $order->get_total_shipping();
		// Add shipping tax
		if ( !affiliate_wp()->settings->get( 'exclude_tax' ) ) :
		
			$order_shipping += $order->get_shipping_tax();
			
		endif;
		
		foreach ( $items as $product ) :
		
			$product_id = ( !empty($product['variation_id']) ) ? $product['variation_id'] : $product['product_id'];
			
			$product_total = 0;
		    
			// Check if product disabled
			if ( !$this->get_product_setting( $product_id, 'referrals_disabled', $group_id ) ) :

				// Start with the discounted (coupons considered) line total (exclusive of tax and shipping)
				$product_total = $product['line_total'];
				
				// Distribute shipping accross products
				$product_shipping = 0;
				if ( $order_shipping > 0 && !affiliate_wp()->settings->get( 'exclude_shipping' ) ) :
					
					$product_shipping = $order_shipping / count( $items );
					$product_total += $product_shipping;
					
				endif;
				
				// Add tax
				if ( !affiliate_wp()->settings->get( 'exclude_tax' ) ) :
					
					$product_total += $product['line_tax'];
					
				endif;

			endif;
			
			//if( $product_total > 0 ) :
				
				$order_data['products'][$product_id]['total'] = $product_total;
				$order_data['total'] += $product_total;
			
			//endif;

		endforeach;
		
		return $order_data;

	}
	
	
	// Get product setting. If product is a variable product, the parents settings are fallback (if set)
	// keys: referrals_disabled, product_rate_type, product_rate (requires level)
	public function get_product_setting( $product_id, $key, $group_id = 'default', $level = '', $fallback_to_parent = true ) {

		$get_key = '_affwp_mla_' . $this->context . '_'.$key.'_'.$group_id;
		$get_key = ( !empty($level) && $key == 'product_rate' ) ? $get_key.'_level_'.$level : $get_key;
		
		$setting = get_post_meta( $product_id, $get_key, true );
		
		// Parent fallbacks for variable products
		$product = wc_get_product( $product_id );
		$parent_id = $product->get_parent_id();
		if( !empty($parent_id) && $fallback_to_parent ) :
			
			$parent_setting = get_post_meta( $parent_id, $get_key, true );
			if( $key == 'referrals_disabled' ) :
			
				$setting = ( empty($setting) ) ? $parent_setting : $setting;
			
			elseif ($key == 'product_rate_type'  ):
			
				$setting = ( empty($setting) ) ? $parent_setting : $setting;
			
			
			elseif( $key == 'product_rate') :
			
				$setting = ( (empty($setting) && $setting != '0') ) ? $parent_setting : $setting;
				
			endif;
			
		endif;

		return $setting;
		
	}

  function mla_product_referral_amount_woocommerce($product_commission_amount, $product_filter_vars) {

    $order_id = $product_filter_vars['matrix_data']['args']['reference'];
    $product_id = $product_filter_vars['product_id'];
    //$rate_type = $product_filter_vars['rate_type'];

    // Flat rate multiplier
    if ( $product_filter_vars['rate_type'] == 'flat' ) :

      $order = new WC_Order($order_id);
      $items = $order->get_items();

      foreach ($items as $product) :

        $line_product_id = (!empty($product['variation_id'])) ? $product['variation_id'] : $product['product_id'];

        if ( $line_product_id == $product_id ) :

          return $product_commission_amount * $product['qty'];

        endif;

      endforeach;

    endif;

    return $product_commission_amount;

  }
	
	
} // end class

?>