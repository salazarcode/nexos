<?php
$template_loader = new AffiliateWP_MLA_Template_Loader( array() );
$template_loader->set_template_data( (array) $data );
	
$chart = ( isset($_POST['chart']) ) ? $_POST['chart'] : 'network';
?>

<div id="affwp-mla-charts-wrapper" class=""> 
	<div id="affwp-mla-charts-content">
		<?php
        echo $template_loader->get_template_object( 'dashboard-charts-toolbar' );
    
        switch ($chart){
        case 'network':
    
                echo $template_loader->get_template_object( 'dashboard-network-chart' );
            
            break;
        }
        ?>
    </div>
</div>