<script type="text/javascript">
	jQuery( document ).ready( function($) {
	
		$( '#affwp-graphs-date-options' ).change( function() {
			var $this = $(this);
			if( $this.val() == 'other' ) {
				$( '#affwp-date-range-options' ).css('display', 'inline-block');
			} else {
				$( '#affwp-date-range-options' ).hide();
			}
		});
	
	});
</script>

<?php

$reports = new AffiliateWP_MLA_Reports();
$reports->get_report_controls();

$template_loader = new AffiliateWP_MLA_Template_Loader();

if( $reports->is_report( 'referrals' ) ) : 

	//$template_loader->set_template_data( $data );
		
	echo $template_loader->get_template_object( 'dashboard-affiliate-reports-referrals' );

endif; 
?>