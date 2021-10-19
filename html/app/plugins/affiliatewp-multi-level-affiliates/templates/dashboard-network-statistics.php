<?php 
$statistics = new AffiliateWP_MLA_Statistics( $data->affiliate_id );
$network_stats = $statistics->get_network_level_stats();
$network_depth = apply_filters('mla_dashboard_statistics_row_count', count($network_stats) );
?>	

<div id="affwp-tab-content_mla_statistics" class="affwp-tab-content_mla_statistics"> 

	<table id="affwp-affiliate-dashboard-mla-network-statistics" class="affwp-table">
    
		<thead>
			<tr>
				<th class="mla_level"><?php _e( 'Level', 'affiliatewp-multi-level-affiliates' ); ?></th>
				<th class="mla_affiliates_count"><?php _e( 'Affiliates', 'affiliatewp-multi-level-affiliates' ); ?></th>
                <th class="mla_unpaid_referrals"><?php _e( 'Unpaid Referrals', 'affiliatewp-multi-level-affiliates' ); ?></th>
                <th class="mla_unpaid_earnings"><?php _e( 'Unpaid Earnings', 'affiliatewp-multi-level-affiliates' ); ?></th>
                <th class="mla_referrals"><?php _e( 'Paid Referrals', 'affiliatewp-multi-level-affiliates' ); ?></th>
				<th class="mla_earnings"><?php _e( 'Paid Earnings', 'affiliatewp-multi-level-affiliates' ); ?></th>
			</tr>
		</thead>
        
		<tbody>
        	<?php for ($level = 1; $level <= $network_depth; $level++) : ?>
			<tr>
				<th class="mla_level"><?php echo $level; ?></th>
				<td class="mla_affiliates_count"><?php echo $network_stats[$level]['affiliate_count']; ?></td>
            	<td class="mla_unpaid_referrals"><?php echo $network_stats[$level]['unpaid_referrals']; ?></td>
             	<td class="mla_unpaid_earnings"><?php echo affwp_currency_filter( $network_stats[$level]['unpaid_earnings'] ); ?></td>
             	<td class="mla_referrals"><?php echo $network_stats[$level]['referrals']; ?></td>
				<td class="mla_earnings"><?php echo affwp_currency_filter( $network_stats[$level]['earnings'] ); ?></td>
			</tr>      
			<?php endfor; ?>
		</tbody>
        
	</table>
    
    <?php do_action( 'mla_dashboard_after_network_statistics', $data->affiliate_id ); ?>

</div>