<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class AffiliateWP_Affiliate_Landing_Pages_Admin {

	/**
	 * Sets up the class.
	 *
	 * @access public
	 * @since  1.0
	 */
	public function __construct() {

		// Settings tab.
		add_filter( 'affwp_settings_tabs', array( $this, 'setting_tab' ) );

		// Settings.
		add_filter( 'affwp_settings', array( $this, 'register_settings' ) );

	}

	/**
	 * Register the new settings tab.
	 *
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	public function setting_tab( $tabs ) {
		$tabs['affiliate-landing-pages'] = __( 'Affiliate Landing Pages', 'affiliatewp-affiliate-landing-pages' );
		return $tabs;
	}

	/**
	 * Register our settings.
	 *
	 * @access public
	 * @since 1.0
	 * @return array
	 */
	public function register_settings( $settings = array() ) {

		$settings['affiliate-landing-pages'] = array(
			'affiliate-landing-pages'            => array(
				'name' => __( 'Enable', 'affiliatewp-affiliate-landing-pages' ),
				'desc' => __( 'Enable Affiliate Landing Pages. This will allow a page or post to be assigned to an affiliate.', 'affiliatewp-affiliate-landing-pages' ),
				'type' => 'checkbox',
			),
			'affiliate-landing-pages-post-types' => array(
				'name'    => __( 'Post Types', 'affiliatewp-affiliate-landing-pages' ),
				'desc'    => __( 'Select which post types support landing pages.', 'affiliatewp-affiliate-landing-pages' ),
				'type'    => 'multicheck',
				'options' => $this->gather_post_types(),
			),
		);

		return $settings;

	}

	/**
	 * Gathers a list of public post types.
	 *
	 * @since 1.0.3
	 *
	 * @return array List of valid post types.
	 */
	private function gather_post_types() {
		$results    = array();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		foreach ( $post_types as $post_type ) {
			$results[ $post_type->name ] = $post_type->label;
		}

		unset( $results['attachment'] );

		/**
		 * Filters the post types to display in the landing page admin.
		 *
		 * @since 1.0.3
		 *
		 * @param array $results List of public post type slugs keyed by their name.
		 */
		return apply_filters( 'affwp_alp_admin_post_types', $results );
	}

}

new AffiliateWP_Affiliate_Landing_Pages_Admin();
