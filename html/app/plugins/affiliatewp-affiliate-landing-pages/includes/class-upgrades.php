<?php
/**
 * Class Affiliate_WP_Affiliate_Landing_Pages_Upgrades
 *
 * @since   1.0.3
 * @package AffiliateWP_Affiliate_Landing_Pages\Upgrades
 */

/**
 * Handles upgrades for this plugin.
 *
 * @since  1.0.3
 */
class Affiliate_WP_Affiliate_Landing_Pages_Upgrades {

	/**
	 * Signals whether the upgrade was successful.
	 *
	 * @since  1.0.3
	 * @var    bool
	 */
	private $upgraded = false;

	/**
	 * AffiliateWP - Lifetime Commissions version.
	 *
	 * @since  1.0.3
	 * @var    string
	 */
	private $version;

	/**
	 * Sets up the Upgrades class instance.
	 *
	 * @since 1.0.3
	 *
	 * @param string $version The AffiliateWP Lifetime Commissions version.
	 */
	public function __construct( $version ) {

		$this->version = $version;
		add_action( 'admin_init', array( $this, 'init' ), -9999 );
	}

	/**
	 * Initializes upgrade routines for the plugin.
	 *
	 * @since 1.0.3
	 */
	public function init() {

		if ( empty( get_option( 'affwp_alp_version' ) ) ) {
			$this->version = '1.0.2'; // last version that didn't have the version option set.
		}

		// Inconsistency between current and saved version.
		if ( true === version_compare( $this->version, AFFWP_ALP_VERSION, '<>' ) ) {
			$this->upgraded = true;
		}

		if (true === version_compare( $this->version, '1.0.3', '<' ) ) {
			$this->v103_upgrade();
		}

		// If upgrades have occurred.
		if ( true === $this->upgraded ) {
			update_option( 'affwp_alp_version_upgraded_from', $this->version );
			update_option( 'affwp_alp_version', AFFWP_ALP_VERSION );
		}

	}

	/**
	 * Performs database upgrades for version 1.0.3.
	 *
	 * @access private
	 * @since  1.0.3
	 */
	private function v103_upgrade() {

		affiliate_wp()->settings->set( array(
			'affiliate-landing-pages-post-types' => array(
				'post' => 'Posts',
				'page' => 'Pages',
			),
		), true );

		// Upgraded!
		$this->upgraded = true;

	}

}
