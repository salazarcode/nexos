<?php
  $reports = new AffiliateWP_MLA_Reports();
  //$dates = $reports->get_report_dates();
  
  $affiliate_ids = array( affwp_get_affiliate_id() );
  
  $per_page  = 30;
  $page      = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
  $pages     = absint( ceil( affwp_count_referrals( affwp_get_affiliate_id() ) / $per_page ) );

  //$dates = affwp_get_report_dates();
  $dates = $reports->get_report_dates(); // Wraper for above
  $start = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'] . ' 00:00:00';
  $end   = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'] . ' 23:59:59';
  $date  = array(
	  'start' => $start,
	  'end'   => $end
  );

  $referrals = affiliate_wp()->referrals->get_referrals(
	  array(
		  'number'       => $per_page,
		  'offset'       => $per_page * ( $page - 1 ),
		  'affiliate_id' => $affiliate_ids,
		  'status'       => array( 'paid', 'unpaid', 'rejected' ),
		  'orderby'      => 'date',
		  'order'        => 'ASC',
		  'date'         => $date,
	  )
  );

?>

<div id="affwp-affiliate-dashboard-referrals" class="affwp-tab-content">

	<h4><?php _e( 'Referrals', 'affiliatewp-multi-level-affiliates' ); ?></h4>
    
	<?php do_action( 'affwp_referrals_dashboard_before_table', affwp_get_affiliate_id() ); ?>

	<table id="affwp-affiliate-dashboard-referrals" class="affwp-table">
		<thead>
			<tr>
				<th class="referral-amount"><?php _e( 'Amount', 'affiliatewp-multi-level-affiliates' ); ?></th>
				<th class="referral-description"><?php _e( 'Description', 'affiliatewp-multi-level-affiliates' ); ?></th>
				<th class="referral-status"><?php _e( 'Status', 'affiliatewp-multi-level-affiliates' ); ?></th>
				<th class="referral-date"><?php _e( 'Date', 'affiliatewp-multi-level-affiliates' ); ?></th>
				<?php do_action( 'affwp_referrals_dashboard_th' ); ?>
			</tr>
		</thead>

		<tbody>
			<?php if ( $referrals ) : ?>

				<?php foreach ( $referrals as $referral ) : ?>
					<tr>
						<td class="referral-amount"><?php echo affwp_currency_filter( affwp_format_amount( $referral->amount ) ); ?></td>
						<td class="referral-description"><?php echo wp_kses_post( nl2br( $referral->description ) ); ?></td>
						<td class="referral-status <?php echo $referral->status; ?>"><?php echo affwp_get_referral_status_label( $referral ); ?></td>
						<td class="referral-date"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $referral->date ) ); ?></td>
						<?php do_action( 'affwp_referrals_dashboard_td', $referral ); ?>
					</tr>
				<?php endforeach; ?>

			<?php else : ?>

				<tr>
					<td colspan="4"><?php _e( 'You have not earned any referrals yet.', 'affiliatewp-multi-level-affiliates' ); ?></td>
				</tr>

			<?php endif; ?>
		</tbody>
	</table>

	<?php do_action( 'affwp_referrals_dashboard_after_table', affwp_get_affiliate_id() ); ?>

	<?php if ( $pages > 1 ) : ?>

		<p class="affwp-pagination">
			<?php
			echo paginate_links(
				array(
					'current'      => $page,
					'total'        => $pages,
					'add_fragment' => '#affwp-affiliate-dashboard-referrals',
					'add_args'     => array(
						'tab' => 'referrals',
					),
				)
			);
			?>
		</p>

	<?php endif; ?>

</div>

