<?php
$charts_affiliate_id = (!empty($data->affiliate_id)) ? $data->affiliate_id : affwp_get_affiliate_id( get_current_user_id() );
?>

<?php 
$network_chart = new AffiliateWP_MLA_Charts($charts_affiliate_id);
$chart_settings = $network_chart->get_chart_settings( $data );
$network_chart->get_chart_dependencies();
?>

<div id="affwp-mla-reports-toolbar" class="affwp-mla-reports-toolbar dashboard"> 

<form method="post">
    
<input name="page" type="hidden" id="page" value="affiliate-wp-reports">
<input name="tab" type="hidden" id="tab" value="mla_charts">
    
    <div>
    
    <table class="affwp-tab-table" width="100%" cellpadding="5" cellspacing="5">
      <tbody>
        <tr>
          <td width="10%">
          </td>
          <td width="20%">
          <input name="chart_enable_pan" type="checkbox" id="chart_enable_pan" value="1" <?php if($chart_settings->pan) echo 'checked' ;?>> <?php _e( 'Enable Pan', 'affiliatewp-multi-level-affiliates' ) ;?> 
          </td>
          <td width="20%">
          <input name="chart_enable_zoom" type="checkbox" id="chart_enable_zoom" value="1" <?php if($chart_settings->zoom) echo 'checked' ;?>> <?php _e( 'Enable Zoom', 'affiliatewp-multi-level-affiliates' ) ;?> 
          </td>
          <td width="20%">
          <!--<select name="chart" id="chart">
            <option value="network"><?php _e( 'Affiliate Network', 'affiliatewp-multi-level-affiliates' ) ;?></option>
          </select>-->
          </td>
          <td>
          <!--<input type="submit" name="mla_generate_chart" value="Generate Chart">-->
          </td>
          <td>
          <div class="mla_chart_toolbar_actions" style="text-align:right">
              <span class="chart_expand fas fa-expand-arrows-alt"></span>
              <span class="chart_export fas fa-download"></span>
          </div>
          </td>
        </tr>
      </tbody>
	</table>
    
	</div>
    
</form>

</div>