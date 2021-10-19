<div id="affwp-tab-content_mla_earnings" class="affwp-tab-content_mla_earnings"> 

    <table id="affwp-affiliate-dashboard-mla-earnings" class="affwp-table">
        <thead>
            <tr>
                <th class="mla_level"></th>
                <th class="mla_referrals"><?php _e( 'Unpaid', 'affiliatewp-multi-level-affiliates' ); ?></th>
                <th class="mla_affiliates_count"><?php _e( 'Paid', 'affiliatewp-multi-level-affiliates' ); ?></th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <th class="mla_level"><?php _e( 'Referrals', 'affiliatewp-multi-level-affiliates' ); ?></th>
                 <td class="mla_referrals"><?php echo affwp_count_referrals( $data->affiliate_id, 'unpaid' ); ?></td>
                <td class="mla_affiliates_count"><?php echo affwp_count_referrals( $data->affiliate_id, 'paid' ); ?></td>
             </tr>
                    
             <tr>
                 <th class="mla_level"><?php _e( 'Earnings', 'affiliatewp-multi-level-affiliates' ); ?></th>
                 <td class="mla_referrals"><?php echo affwp_get_affiliate_unpaid_earnings( $data->affiliate_id, true ); ?></td>
                 <td class="mla_affiliates_count"><?php echo affwp_get_affiliate_earnings( $data->affiliate_id, true ); ?></td>
             </tr>

        </tbody>
    </table>
    
    <?php do_action( 'mla_dashboard_after_earnings', $data->affiliate_id ); ?>
        
</div>