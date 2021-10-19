<?php 
$statistics = new AffiliateWP_MLA_Statistics( $data->affiliate_id );
$levels = $statistics->get_best_display_rates();
?>	

<div id="affwp-tab-content_mla_rates" class="affwp-tab-content_mla_rates"> 

	<div style="height:130px;overflow:auto;">

        <table id="affwp-affiliate-dashboard-mla-rates" class="affwp-table">
            <thead>
                <tr>
                    <th class="mla_affiliates_count"><?php _e( 'Level', 'affiliatewp-multi-level-affiliates' ); ?></th>
                    <th class="mla_referrals"><?php _e( 'Commission Rate', 'affiliatewp-multi-level-affiliates' ); ?></th>
                </tr>
            </thead>
    
            <tbody>
                <?php foreach( $levels as $level => $levels_data ) : ?>
				<tr>
                    <th class="mla_affiliates_count"><?php echo $level; ?></td>
                    <td class="mla_referrals"><?php echo $levels_data['string']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
    </div>
    
    <?php if( !empty($levels['1']['commission_note']) ) : ?> 
    <p style="font-size:0.875em"><i><?php echo $levels['1']['commission_note']; ?></i></p>
    <?php endif; ?>
    
   <?php do_action( 'mla_dashboard_after_rates', $data->affiliate_id ); ?>

</div>
