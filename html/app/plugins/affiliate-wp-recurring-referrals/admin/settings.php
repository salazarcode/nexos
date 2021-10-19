<?php
class AffiliateWP_Recurring_Admin {
	/**
	 * Get things started
	 *
	 * @access public
	 * @since  1.0
	 * @return void
	 */
	public function __construct() {
		add_filter( 'affwp_settings_tabs', array( $this, 'setting_tab' ) );
		add_filter( 'affwp_settings', array( $this, 'register_settings' ) );
		add_filter( 'affwp_settings_sanitize', array( $this, 'sanitize_recurring_rate' ), 10, 2 );
	}
	/**
	 * Register the new settings tab
	 *
	 * @access public
	 * @since  1.5
	 * @return array
	 */
	public function setting_tab( $tabs ) {
		$tabs['recurring'] = __( 'Recurring Referrals', 'affiliate-wp-recurring-referrals' );
		return $tabs;
	}
	/**
	 * Register our settings
	 *
	 * @access public
	 * @since  1.0
	 * @return array
	 */
	public function register_settings( $settings = array() ) {
		$settings[ 'recurring' ] = array(
			'recurring' => array(
				'name' => __( 'Enable Recurring Referrals', 'affiliate-wp-recurring-referrals' ),
				'desc' => __( 'Check this box to enable referral tracking on all subscription payments', 'affiliate-wp-recurring-referrals' ),
				'type' => 'checkbox'
			),
			'recurring_referral_limit_enabled' => array(
				'name'    => __( 'Enable Recurring Referral Limits', 'affiliate-wp-recurring-referrals' ),
				'desc'    => __( 'Check this box to enable recurring referral limits on all subscription payments', 'affiliate-wp-recurring-referrals' ),
				'type' => 'checkbox'
			),
			'recurring_rate' => array(
				'name' => __( 'Recurring Rate', 'affiliate-wp-recurring-referrals' ),
				'desc' => __( 'Enter the commission rate for recurring payments. If no rate is entered, the affiliate\'s standard rate will be used.', 'affiliate-wp-recurring-referrals' ),
				'type' => 'number',
				'min'  => 0,
				'step' => '0.01',
				'size' => 'small'
			),
			'recurring_rate_type' => array(
				'name'    => __( 'Recurring Rate Type', 'affiliate-wp-recurring-referral' ),
				'desc'    => __( 'Select the commission rate type for recurring payments. If no rate type is entered, the affiliate\'s standard rate type will be used.', 'affiliate-wp-recurring-referrals' ),
				'type'    => 'select',
				'options' => affwp_get_affiliate_rate_types()
			),
			'recurring_referral_limit' => array(
				'name'    => __( 'Recurring Referral Limit', 'affiliate-wp-recurring-referral' ),
				'desc'    => __( 'Set the recurring referral limit for recurring payments. Set to 0 for unlimited recurring referrals. This global recurring referral limit will be used, unless a recurring referral limit is entered for an affiliate.', 'affiliate-wp-recurring-referrals' ),
				'type'    => 'number',
				'step'    => '1',
				'size'    => 'small',
				'default' => 0,
			),
		);

		if ( class_exists( 'AffiliateWP_Tiered_Rates' ) ) {
			$tiered_rates_settings_link = admin_url( 'admin.php?page=affiliate-wp-settings&tab=rates' );
			$settings['recurring']['recurring_rate_tiered_rates_enabled'] = array(
				'name' => __( 'Enable Tiered Rates', 'affiliate-wp-recurring-referrals' ),
				'desc' => sprintf( __( 'Check this box to allow <a href="%1$s">tiered rates</a> to be used for recurring payments.', 'affiliate-wp-recurring-referrals' ), $tiered_rates_settings_link ),
				'type' => 'checkbox'
			);
		}
		return $settings;
	}

	/**
	 * Sanitize the recurring rate on save.
	 *
	 * @since 1.7
	 * @return string
	 */
	public function sanitize_recurring_rate( $value = '', $key = '' ) {

		if ( 'recurring_rate' === $key ) {

			if ( empty( $value ) ) {

				$value = '';

			}

		}

		return $value;
	}
	
}
new AffiliateWP_Recurring_Admin;