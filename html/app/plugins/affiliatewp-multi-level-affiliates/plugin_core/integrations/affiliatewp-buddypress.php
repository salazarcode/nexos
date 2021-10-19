<?php

  add_action( 'affwp_bp_after_add_nav_items', 'affwp_mla_bp_add_sub_affiliates_nav_item' );

  function affwp_mla_bp_add_sub_affiliates_nav_item() {
		  
	  $hidden_tabs = affiliate_wp()->settings->get( 'affwp_bp_aff_area_hide_tabs' );
		  
	  if( ! $hidden_tabs['sub_affiliates'] ) :
			  
		  // Add the Sub Affiliates Tab
		  bp_core_new_subnav_item( array(
			  'name' => __('Affiliate Network', 'affiliatewp-multi-level-affiliates'),
			  'slug' => 'affiliate-network',
			  'show_for_displayed_user' => false, 
			  'parent_url' => trailingslashit( bp_displayed_user_domain() . 'affiliate-area'),
			  'parent_slug' => 'affiliate-area',
			  'position' => 70,
			  'screen_function' => 'affwp_mla_bp_affiliate_sub_affiliates',
			  'item_css_id' => 'affiliate-sub-affiliates',
			  'user_has_access' => bp_is_my_profile()
		  ) );
		  
	  endif;
  }
		

  function affwp_mla_bp_affiliate_sub_affiliates() {
	  add_action( 'bp_template_content', 'affwp_mla_bp_show_affiliate_sub_affiliates' );
	  bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
  }

  function affwp_mla_bp_show_affiliate_sub_affiliates() {
	  
	  affwp_bp_page( affwp_bp_access_check() );
		  
	  if( affwp_bp_access_check() == '' ) :
		  
		  affwp_bp_affiliate_area_notices();
				  
		  echo '<div id="affwp-affiliate-dashboard">';
		  
		  echo do_shortcode( '[mla_dashboard]' );
		  
		  echo '</div>';
				  
	  endif;
	  
  }
		
?>