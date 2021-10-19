jQuery(document).ready(function($) {

	jQuery('#parent_affiliate_id').select2({
		allowClear:true,
		width:'50%'
	});

	jQuery('#direct_affiliate_id').select2({
		allowClear: true,
		width: '50%'
	});
	
	/* Hide / Display Relevent Team Leader Options */
	//teamLeaderSettings();
	//sTeamLeaderSettings();
	
	/* Hide / Display Relevent Leader Options on Change */
	jQuery('#AFFWP_MLA_team_leader_mode_default').on('change', function() {
	 	//teamLeaderSettings();
	});
	jQuery('#AFFWP_MLA_team_leader_single_only_default').on('change', function() {
	 	//teamLeaderSettings();
	});
	
	jQuery('#AFFWP_MLA_steam_leader_mode_default').on('change', function() {
	 	//sTeamLeaderSettings();
	});
	jQuery('#AFFWP_MLA_steam_leader_single_only_default').on('change', function() {
	 	//sTeamLeaderSettings();
	});

});

/* Hide / Display Relevent Team Leader Options */
function teamLeaderSettings() {
	
		var team_leader_mode = jQuery('#AFFWP_MLA_team_leader_mode_default :selected').val();
	 	if( team_leader_mode == 'disabled' ) {
			
			jQuery('.mla_team_leader_options').hide();
	  		jQuery('#AFFWP_MLA_team_leader_rate_value_default').removeAttr( 'required' );
			
		}else{
			
			jQuery('.mla_team_leader_options').show();
			jQuery('#AFFWP_MLA_team_leader_rate_value_default').attr('required', true);
			
			/*if( team_leader_mode != 'within_max_levels' ) {*/
				
				/*jQuery('.mla_team_leader_options.within_payment_levels').hide();
				jQuery('.mla_team_leader_options.single_only').hide();*/
				
			/*}else{*/
				
				if(jQuery('#AFFWP_MLA_team_leader_single_only_default').is(':checked')) {
					
					jQuery('.mla_team_leader_options.single_only').show( 1000 );
					
				}else{
					
					jQuery('.mla_team_leader_options.single_only').hide( 1000 );
					
				}
				
			/*}*/
			
		}
   
}

/* Hide / Display Relevent Super Team Leader Options */
function sTeamLeaderSettings() {
		
		var steam_leader_mode = jQuery('#AFFWP_MLA_steam_leader_mode_default :selected').val();
	 	if( steam_leader_mode == 'disabled' ) {
			
			jQuery('.mla_steam_leader_options').hide();
	  		jQuery('#AFFWP_MLA_steam_leader_rate_value_default').removeAttr( 'required' );
			
		}else{
			
			jQuery('.mla_steam_leader_options').show();
			jQuery('#AFFWP_MLA_steam_leader_rate_value_default').attr('required', true);
			
			/*if( steam_leader_mode != 'within_max_levels' ) {*/
				
				jQuery('.mla_steam_leader_options.within_payment_levels').hide();
				jQuery('.mla_steam_leader_options.single_only').hide();
				
			/*}else{*/
				
				if(jQuery('#AFFWP_MLA_steam_leader_single_only_default').is(':checked')) {
					
					jQuery('.mla_steam_leader_options.single_only').show( 1000 );
					
				}else{
					
					jQuery('.mla_steam_leader_options.single_only').hide( 1000 );
					
				}
				
			/*}*/
			
		}
     
}

