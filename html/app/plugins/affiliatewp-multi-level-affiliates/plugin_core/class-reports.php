<?php

// reference the Dompdf namespace
use Dompdf\Dompdf;

class AffiliateWP_MLA_Reports extends AffiliateWP_MLA_Common {

	public function __construct( $affiliate_id = '' ) {
		
		parent::__construct();
		
		//$this->affiliate_id = (empty($affiliate_id)) ? affwp_get_affiliate_id( get_current_user_id() ) : $affiliate_id;
		
	}
	
	// check if a report
	public function is_report( $report_slug ) {
		
		$report = ( isset( $_GET['mla_report'] ) && !empty( $_GET['mla_report'] ) ) ? $_GET['mla_report'] : NULL;
		
		return( $report == $report_slug) ? TRUE : FALSE;	
	
	}
	
	// Get AffiliateWP default reports controls
	public function get_report_controls() {
		
		$date_options = apply_filters( 'affwp_report_date_options', array(
			'today' 	    => __( 'Today', 'affiliatewp-multi-level-affiliates' ),
			'yesterday'     => __( 'Yesterday', 'affiliatewp-multi-level-affiliates' ),
			'this_week' 	=> __( 'This Week', 'affiliatewp-multi-level-affiliates' ),
			'last_week' 	=> __( 'Last Week', 'affiliatewp-multi-level-affiliates' ),
			'this_month' 	=> __( 'This Month', 'affiliatewp-multi-level-affiliates' ),
			'last_month' 	=> __( 'Last Month', 'affiliatewp-multi-level-affiliates' ),
			'this_quarter'	=> __( 'This Quarter', 'affiliatewp-multi-level-affiliates' ),
			'last_quarter'	=> __( 'Last Quarter', 'affiliatewp-multi-level-affiliates' ),
			'this_year'		=> __( 'This Year', 'affiliatewp-multi-level-affiliates' ),
			'last_year'		=> __( 'Last Year', 'affiliatewp-multi-level-affiliates' ),
			'other'			=> __( 'Custom', 'affiliatewp-multi-level-affiliates' )
		) );

		$dates = affwp_get_report_dates();

		$display = $dates['range'] == 'other' ? 'style="display:inline-block;"' : 'style="display:none;"';

		$current_time = current_time( 'timestamp' );

		?>
		<form id="mla-reports-filter" method="get">
        
        	<select id="mla-report-options" name="mla_report">
			<option value="referrals">Referrals</option>
            </select>
        
			<div class="tablenav top">

				<?php if( is_admin() ) : ?>
					<?php $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'referral'; ?>
					<?php $page = isset( $_GET['page'] ) ? $_GET['page'] : 'affiliate-wp'; ?>
					<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>"/>
				<?php else: ?>
					<?php $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'graphs'; ?>
					<input type="hidden" name="page_id" value="<?php echo esc_attr( get_the_ID() ); ?>"/>
				<?php endif; ?>
				
				<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>"/>
				
				<?php if( isset( $_GET['affiliate_id'] ) ) : ?>
				<input type="hidden" name="affiliate_id" value="<?php echo absint( $_GET['affiliate_id'] ); ?>"/>
				<input type="hidden" name="action" value="view_affiliate"/>
				<?php endif; ?>

				<select id="affwp-graphs-date-options" name="range">
				<?php
					foreach ( $date_options as $key => $option ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $dates['range'] ) . '>' . esc_html( $option ) . '</option>';
					}
				?>
				</select>

				<div id="affwp-date-range-options" <?php echo $display; ?>>

					<?php
					$from = empty( $_REQUEST['filter_from'] ) ? '' : $_REQUEST['filter_from'];
					$to   = empty( $_REQUEST['filter_to'] )   ? '' : $_REQUEST['filter_to'];
					?>
					<span class="affwp-search-date">
						<span><?php _ex( 'From', 'date filter', 'affiliatewp-multi-level-affiliates' ); ?></span>
						<input type="text" class="affwp-datepicker" autocomplete="off" name="filter_from" placeholder="<?php esc_attr_e( 'From - mm/dd/yyyy', 'affiliatewp-multi-level-affiliates' ); ?>" aria-label="<?php esc_attr_e( 'From - mm/dd/yyyy', 'affiliatewp-multi-level-affiliates' ); ?>" value="<?php echo esc_attr( $from ); ?>" />
						<span><?php _ex( 'To', 'date filter', 'affiliatewp-multi-level-affiliates' ); ?></span>
						<input type="text" class="affwp-datepicker" autocomplete="off" name="filter_to" placeholder="<?php esc_attr_e( 'To - mm/dd/yyyy', 'affiliatewp-multi-level-affiliates' ); ?>" aria-label="<?php esc_attr_e( 'To - mm/dd/yyyy', 'affiliatewp-multi-level-affiliates' ); ?>" value="<?php echo esc_attr( $to ); ?>" />
					</span>

				</div>

				<input type="submit" class="button" value="<?php _e( 'Filter', 'affiliatewp-multi-level-affiliates' ); ?>"/>
			</div>
            
		</form>
		<?php
	}
	
	public static function get_report_dates() {
		
		return affwp_get_report_dates();
		
	}
	
	public function generate_pdf( $vars = array() ) {
		
		$default_vars = array( 
			'output' 		=> 'stream',
			'stream_type' 	=> '1', // 1 is download, 0 is preview
			);
			
    	$vars = array_merge( $default_vars, $vars );
		
		require_once plugin_dir_path( __FILE__ ) . 'includes/lib/dompdf-master/autoload.inc.php';
		
		$dompdf = new DOMPDF();
		$dompdf->set_paper("A4");
		
		$dompdf->load_html( $vars['content'] );
		$dompdf->render();
		
		$output = $dompdf->output();
		
		if( $vars['output'] == 'save' && !empty($vars['folder']) && !empty($vars['file_name']) ) {
		
			//$folder = $vars['folder'];
			//$file_name = $vars['file_name'];
			
			if (!file_exists( ABSPATH."/wp-content/uploads/mla_reports/".$vars['folder'] )) {
				mkdir( ABSPATH."/wp-content/uploads/mla_reports/".$vars['folder'], 0777, true );
			}
	
			file_put_contents( ABSPATH."/wp-content/uploads/mla_reports/".$vars['folder']."/".$vars['file_name'], $output);
		
		}elseif( $vars['output'] == 'stream' ) {
			
			// Output the generated PDF to Browser
			//$dompdf->stream();
			
			// Output the generated PDF (1 = download and 0 = preview)
			$dompdf->stream( $vars['file_name'], array("Attachment"=>$vars['stream_type']) );
			
		}
		
	}
	
	public function generate_report( $vars ) {
		
		$report = ( isset($vars['report']) ) ? $vars['report'] : '';
		
		/*if( isset($vars['output']) ) :
			$vars['output'] = $vars['output'];	
		endif;
		
		if( isset($vars['stream_type']) ) :
			$vars['stream_type'] = $vars['stream_type'];
		endif;*/

		// The report action
		$action = ( isset($_POST['mla_report_action']) ) ? $_POST['mla_report_action'] : '';
		
		// Affiliate parameters
		if( isset($_POST['affiliate_id']) ) {
			$affiliate_id = $_POST['affiliate_id'];
		}else {
			$affiliate_id = ( isset($vars['affiliate_id']) ) ? $vars['affiliate_id'] : '';
		}
		
		// Date parameters
		if( isset($_POST['date_range']) ) {
				
				// Get the date data
				switch ($_POST['date_range']){
				case "current_month":
						$date_vars = array();
					break;
				case "last_month":
						$date_vars = array( 'timeframe' => 'other_month', 'offset' => -1 );
					break;	
					
				default:
						$date_vars = array( 'timeframe' => 'other_month', 'offset' => $_POST['date_range'] );
					break;
				}
			
		}else {
			//current_month
			$date_vars = ( isset($vars['date_vars']) ) ? $vars['date_vars'] : array();
		}
		
		if( !empty($date_vars) ) :
			$ds = new DateTime( '', new DateTimeZone( $this->wp_get_timezone_string() ) );
			$offset = ( !empty($date_vars['offset']) ) ? $date_vars['offset'] : 'this';
			$ds->modify( 'first day of '.$offset.' month' );
			//$month_year = $ds->format( 'Y_m' );
		endif;
		
		// template loaded class
		$template_loader_vars = array( 'sub_directory' => 'reports' );
		$template_loader = new AffiliateWP_MLA_Template_Loader( $template_loader_vars );

		// Switch report
		switch ($report){

		// Admin commission reports
		/*case "affiliate_commissions":
		
			$month_year = $ds->format( 'Y_m' );
		
			$data = array(
				'affiliate_id'	=> $affiliate_id,
				'date_vars'		=> $date_vars,
			);
			
			$template_loader->set_template_data( $data );

			$vars['content'] = $template_loader->get_template_object( 'report-monthly' );
			
			if( isset($vars['output']) && $vars['output'] == 'save' ) :
				$vars['folder'] = 'monthly_reports/'.$month_year;	
			endif;
			$vars['file_name'] = $month_year.'_report_'.affwp_get_affiliate_username($data['affiliate_id']);
			
		break;*/
		
		}

		// end switch report

		if( $action == 'export_pdf' ) $this->generate_pdf( $vars );
		
	}
	
	
	//// Specific Report Data ////
	
	
	
	
}