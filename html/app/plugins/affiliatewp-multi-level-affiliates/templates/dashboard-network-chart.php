<?php
$data = ( isset($data) ) ? $data : array();

$charts_affiliate_id = (!empty($data->affiliate_id)) ? $data->affiliate_id : affwp_get_affiliate_id( get_current_user_id() );
$filter_vars = array( 'affiliate_id' => $charts_affiliate_id );
$charts_affiliate_id = apply_filters( 'mla_dashboard_chart_affiliate', $charts_affiliate_id, $filter_vars );
if( !empty($charts_affiliate_id) ) :
?>

<?php
$network_chart = new AffiliateWP_MLA_Charts($charts_affiliate_id);
$chart_settings = $network_chart->get_chart_settings($data);
$network_children = $network_chart->get_network_data($charts_affiliate_id);
$node_display_data = $network_chart->get_node_attributes($charts_affiliate_id);

// deprecated
$node_display_data['title'] = apply_filters( 'mla_dashboard_chart_node_title', affwp_get_affiliate_earnings( $charts_affiliate_id, TRUE ), $charts_affiliate_id );

?>

<?php do_action( 'mla_dashboard_before_chart', $charts_affiliate_id );?>

<div id="chart_container_overflow">
	<div id="chart-container"></div>
</div>

<script type="text/javascript">
(function($){

jQuery(function() {
  
  var datasource = {
    'id': 'top', 
	'name': '<?php echo $node_display_data['name'];?>',
	'className': '<?php echo $node_display_data['className'];?>',
	'title': '<?php echo $node_display_data['title'] ;?>',
	'info': '<?php //echo $parent_info ;?>', 
	'children': <?php echo json_encode($network_children) ?>
  };
  
  /*var datasource = <?php //echo json_encode($children) ?>;*/

  
  var oc = jQuery('#chart-container').orgchart({
    'data' : datasource,
    'depth': <?php echo $chart_settings->chart_depth ;?>,
    'nodeContent': 'title',
    'nodeID': 'id',
	'direction': '<?php echo $chart_settings->direction ;?>',
    'pan': '<?php echo $chart_settings->pan ;?>',
    'zoom': '<?php echo $chart_settings->zoom ;?>',
	<!--'exportButton': true,-->
  	<!--'exportFilename': 'MyOrgChart',-->
	'initCompleted': function () {
        var container = jQuery('#chart-container');
        container.scrollLeft((container[0].scrollWidth - container.width())/2);
      },
    'createNode': function($node, data) {
      var secondMenuIcon = jQuery('<i>', {
        'class': 'fa fa-info-circle second-menu-icon',
        click: function() {
          jQuery(this).siblings('.second-menu').toggle();
        }
      });
      var secondMenu = '<div class="second-menu">' + data.info + '</div>';
      $node.append(secondMenuIcon).append(secondMenu);
    }
  });
  
  jQuery('#chart_enable_pan').on('click', function() {
      // of course, oc.setOptions({ 'pan': this.checked }); is also OK.
      oc.setOptions('pan', this.checked);
  });
  
  jQuery('#chart_enable_zoom').on('click', function() {
	// of course, oc.setOptions({ 'zoom': this.checked }); is also OK.
	oc.setOptions('zoom', this.checked);
  });
  

});

})(jQuery);

/* Toggle Fullscreen */
jQuery('.chart_expand').on('click', function() {
	
  jQuery('#affwp-mla-charts-wrapper').toggleClass("fullscreen"); //you can list several class names 
  e.preventDefault();
  
});

/* Export */
jQuery('.chart_export').on('click', function() { 

	html2canvas(jQuery('#chart-container'), {
	  onrendered: function(canvas) {
		var img = canvas.toDataURL("image/png");
		download(img, "MyNetworkChart.png", "image/png");
	  }
	});
		
});

</script> 
  
<?php endif; ?>