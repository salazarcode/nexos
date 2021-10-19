<?php	
	
add_action( 'plugins_loaded', 'process_all_mlm_affiliates' );

function process_all_mlm_affiliates() {
	
	$affiliates = query_affiliates();
	
	foreach( $affiliates as $affiliate) :
	
		set_affiliate_parent( $affiliate->affiliate_id, $affiliate->affiliate_parent_id );
		
	endforeach;
	
}

function query_affiliates( $args = array() ) {
		
		global $wpdb;
		
		$aff_table  = $wpdb->prefix.'affiliate_wp_mlm_connections';
		
		$sql = 	"SELECT *";
		$sql .= " FROM $aff_table";
		
		$results = $wpdb->get_results( $sql );
		
		return $results;
		
	}


function affiliate_migration_includes() {
	require_once(ABSPATH . 'wp-content/plugins/affiliatewp-multi-level-affiliates/plugin_core/class-settings.php');
	require_once(ABSPATH . 'wp-content/plugins/affiliatewp-multi-level-affiliates/plugin_core/class-common.php');
	require_once(ABSPATH . 'wp-content/plugins/affiliatewp-multi-level-affiliates/plugin_core/class-affiliate.php');
	}

function set_affiliate_parent( $affiliate_id, $parent_id ) {
	
	if ( class_exists('Affiliate_WP') ) :
	
		//echo $affiliate_id.' - '.$parent_id.'<br>';
			
		affiliate_migration_includes();
			
		$affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
		$affiliate->set_parent_affiliate( $affiliate_id, $parent_id);
	
	endif;

}
	
?>