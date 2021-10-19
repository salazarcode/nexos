<div id="affwp-tab-content_mla" class="affwp-tab-content"> 
	
    <?php if( $data->display_rates ) : ?>
    
        <div class="affwp-affiliate-dashboard-mla-rates" style=" width:48%;margin-right:4%;float:left">
            <h4><?php _e( 'Your Commission Rates', 'affiliatewp-multi-level-affiliates' ); ?></h4>
            <?php echo do_shortcode('[mla_dashboard_rates]'); ?>
        </div>
    
    <?php endif; ?>
    
    <?php if( $data->display_earnings ) : ?> 
           
        <div class="affwp-affiliate-dashboard-mla-earnings" style=" width:48%;float:left">
            <h4><?php _e( 'Your Earnings', 'affiliatewp-multi-level-affiliates' ); ?></h4>
            <?php echo do_shortcode('[mla_dashboard_earnings]'); ?>
        </div>
    
    <?php endif; ?>
    
    <?php if( $data->display_network_statistics ) : ?> 
       
        <div class="affwp-affiliate-dashboard-mla-statistics">
            <h4 style="clear:both"><?php _e( 'Your Network Statistics', 'affiliatewp-multi-level-affiliates' ); ?></h4>
            <?php echo do_shortcode('[mla_dashboard_statistics]'); ?>
        </div>
    
    <?php endif; ?>
    
    <?php if( $data->display_network_chart ) : ?> 
    <?php 
	if( apply_filters( 'mla_chart_tab_content', true ) ) { 
	?> 
       
        <div class="affwp-affiliate-dashboard-mla-network">
            <h4 style="clear:both"><?php _e( 'Your Network Chart', 'affiliatewp-multi-level-affiliates' ); ?></h4>
            <?php echo do_shortcode('[mla_dashboard_chart]'); ?>
        </div>
    
    <?php 
	}else {
			
		do_action( 'mla_chart_tab_alternate_content' );
			
	}?>
    <?php endif; ?>
    
    <?php if( $data->display_reports ) : ?>  
       
        <div class="affwp-affiliate-dashboard-mla-reports">
            <h4 style="clear:both"><?php _e( 'Reports', 'affiliatewp-multi-level-affiliates' ); ?></h4>
            <?php echo do_shortcode('[mla_dashboard_reports]'); ?>
        </div>
    
    <?php endif; ?>
    
</div>