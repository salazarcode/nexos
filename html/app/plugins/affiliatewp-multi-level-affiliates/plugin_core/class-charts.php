<?php

class AffiliateWP_MLA_Charts extends AffiliateWP_MLA_Common {

  public function __construct($affiliate_id) {

    parent::__construct();

    //$this->affiliate_id = (empty($affiliate_id)) ? affwp_get_affiliate_id( get_current_user_id() ) : $affiliate_id;
    $this->affiliate_id = $affiliate_id;

  }

  public function get_chart_settings($overrides = array()) {

    // Get default from plugin options
    $direction = 't2b';
    $pan = ($this->plugin_setting('enable_chart_pan') == 1) ? true : false;
    $zoom = ($this->plugin_setting('enable_chart_zoom') == 1) ? true : false;

    // Override for toolbar settings
    //$direction = ( isset($_POST['chart_direction']) && !empty($_POST['chart_direction']) ) ? $_POST['chart_direction'] : $direction;
    //$pan = ( isset($_POST['chart_enable_pan']) && $_POST['chart_enable_pan'] == 0 ) ? false : $pan;
    //$zoom = ( isset($_POST['chart_enable_zoom']) && $_POST['chart_enable_zoom'] == 0 ) ? false : $zoom;

    // Set the array
    $settings = array(
      'chart_depth' => $this->get_chart_depth(),
      'direction' => $direction,
      'pan' => $pan,
      'zoom' => $zoom,
    );

    // Override for shortcodes here
    //print_r($overrides);
    if ( !empty($overrides) ) {
      $settings = (object)array_merge((array)$settings, (array)$overrides);
      //print_r($settings);
    } else {
      $settings = (object)$settings;
    }

    //$settings = (object) $settings;

    // Override for toolbar settings
    if ( isset($_POST['mla_generate_chart']) ) :

      $settings->direction = (isset($_POST['chart_direction']) && !empty($_POST['chart_direction'])) ? $_POST['chart_direction'] : $settings->direction;

      if ( !empty($_POST['chart_enable_pan']) ) {
        if ( $_POST['chart_enable_pan'] == '1' ) {
          $settings->pan = true;
        } elseif ( $_POST['chart_enable_pan'] == '' ) {
          $settings->pan = false;
        }
      } else {
        $settings->pan = false;
      }


      if ( !empty($_POST['chart_enable_zoom']) ) {
        if ( $_POST['chart_enable_zoom'] == '1' ) {
          $settings->zoom = true;
        } elseif ( $_POST['chart_enable_zoom'] == '' ) {
          $settings->zoom = false;
        }
      } else {
        $settings->zoom = false;
      }

    endif;

    return (object)$settings;

  }

  public function get_chart_depth($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $chart_depth = $this->plugin_setting('dashboard_chart_levels');

    if ( !empty($chart_depth) ) {

      $network_depth = $chart_depth;

    } else {

      $affiliate_obj = new AffiliateWP_MLA_Affiliate($affiliate_id);

      $matrix_level_vars = $affiliate_obj->get_matrix_level_vars();
      //echo '<pre>'; print_r($matrix_level_vars); echo '</pre>';
      $matrix_total_levels = $matrix_level_vars['total_levels'];
      $network_depth = $affiliate_obj->get_affiliates_network_depth();

    }

    return $network_depth + 1;

  }

  /*public function generate_levels_data_array( $level_affiliates ) {

    foreach( $level_affiliates as $affiliate ) :

      $new_levels_array[] = $affiliate->affiliate_id;

    endforeach;

     return $new_levels_array;

  }*/

  // Get network level stats
  public function get_network_data($affiliate_id = '', $level = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $affiliate_obj = new AffiliateWP_MLA_Affiliate($affiliate_id);

    //$matrix_total_levels = $matrix_level_vars['total_levels'];
    $network_depth = $this->get_chart_depth($affiliate_id) - 1;
    //$network_depth = 3;
    //echo $network_depth;
    //echo 'depth'.$network_depth;

    //$data = array();

    $levels_array = array($affiliate_id);

    for ($level = $network_depth; $level >= 1; $level--) :

      $level_data[$level] = array();
      //$level_affiliates = $affiliate_obj->get_level_affiliates( '', $level);
      $level_affiliates = $affiliate_obj->get_chart_level_affiliates('', $level);

      //$level_affiliates = $affiliate_obj->get_chart_level_affiliates( $levels_array);
      //echo '<pre>'; print_r($level_affiliates); echo '</pre>';
      //$levels_array = $this->generate_levels_data_array( $level_affiliates );
      //echo '<pre>'; print_r($levels_array); echo '</pre>';

      /*$level_stats[$level]['affiliate_count'] = count($level_affiliates);
      $level_stats[$level]['earnings'] = 0.00;
      $level_stats[$level]['unpaid_earnings'] = 0.00;
      $level_stats[$level]['referrals'] = 0;
      $level_stats[$level]['unpaid_referrals'] = 0;*/

      // calculate the earnings per level
      foreach ($level_affiliates as $affiliate) :

        /*$level_stats[$level]['earnings'] += affwp_get_affiliate_earnings( $affiliate->affiliate_id );
        $level_stats[$level]['unpaid_earnings'] += affwp_get_affiliate_unpaid_earnings( $affiliate->affiliate_id );

        $level_stats[$level]['referrals'] += affwp_count_referrals( $affiliate->affiliate_id, 'paid' );
        $level_stats[$level]['unpaid_referrals'] += affwp_count_referrals( $affiliate->affiliate_id, 'unpaid' );*/

        $node = $this->get_node_attributes($affiliate->affiliate_id);
        //$level_data[$level][] = $node;
        //array_push($level_data[$level],$node);
        $level_data[$level][$affiliate->affiliate_id] = $node;
        //$data[] = $node;

        $level_data[$level][$affiliate->affiliate_id]['info'] = $this->get_node_info($affiliate->affiliate_id);


        if ( isset($level_data[$level + 1]) ) :

          $level_data[$level][$affiliate->affiliate_id]['children'] = array();

          //if( !isset($level_data[$level][$affiliate->affiliate_id]['children']) ) $level_data[$level][$affiliate->affiliate_id]['children'] = array();

          foreach ($level_data[$level + 1] as $key => $data) :

            //if( !isset($level_data[$level][$affiliate->affiliate_id]['children']) ) $level_data[$level][$affiliate->affiliate_id]['children'] = array();

            if ( $data['parent_id'] == $affiliate->affiliate_id ) :

              if ( !empty($data) ) :
                array_push($level_data[$level][$affiliate->affiliate_id]['children'], $data);
              endif;

            endif;

          endforeach;

        endif;


      endforeach;

    endfor;

    //echo '<pre>'; print_r($levels_array); echo '</pre>';

    $return_array = array();

    if ( !empty($level_data[1]) ) :
      foreach ($level_data[1] as $key => $top_level_affiliate) :

        $return_array[] = $top_level_affiliate;

      endforeach;
    endif;

    //echo '<pre>';
    //echo json_encode($return_array);
    //echo print_r($return_array);
    //echo '</pre>';

    return $return_array;

  }

  // Get the node title )affiliate's name)
  public function get_node_title($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    //$chart_depth = $this->plugin_setting( 'dashboard_chart_levels' );

    $title = affwp_get_affiliate_name($affiliate_id);

    if ( empty($title) ) :

      $title = affwp_get_affiliate_username($affiliate_id);

    endif;

    return apply_filters('mla_chart_node_title', $title, $affiliate_id);

  }

  // Get the node title )affiliate's name)
  public function get_node_content($affiliate_id = '') {

    $content = affwp_get_affiliate_earnings($affiliate_id, TRUE);

    return apply_filters('mla_chart_node_content', $content, $affiliate_id);

  }

  // Gets a node's data
  public function get_node_attributes($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $affiliate_obj = new AffiliateWP_MLA_Affiliate($affiliate_id);
    //$parent_id = $affiliate_obj->get_parent_affiliate_id();
    //$name = affwp_get_affiliate_name( $affiliate->affiliate_id );
    //$info = $this->get_node_info( $affiliate );
    $team_leader_class = (mla_is_affiliate_team_leader($affiliate_id)) ? ' team_leader' : '';
    $super_team_leader_class = (mla_is_affiliate_super_team_leader($affiliate_id)) ? ' super_team_leader' : '';

    $class_name = affwp_get_affiliate_status($affiliate_id) . $team_leader_class . $super_team_leader_class;

    $node_data = array(
      'id' => $affiliate_id,
      'parent_id' => $affiliate_obj->get_parent_affiliate_id(),
      'name' => $this->get_node_title($affiliate_id),
      'className' => $class_name,
      'title' => $this->get_node_content($affiliate_id) // content/earnings
    );

    // deprecated
    $node_data = apply_filters('mla_dashboard_chart_node_data', $node_data, $affiliate_id);

    $chart_location = (is_admin()) ? 'admin_area' : 'front_end';
    $node_data = apply_filters('mla_chart_node_attributes', $node_data, $affiliate_id, $chart_location);

    return $node_data;

  }

  // Get a sub affiliates expanded view info
  public function get_node_info($affiliate_id = '') {

    //$affiliate_id = $affiliate->affiliate_id;
    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $chart_info = array(
      __('Affiliate ID', 'affiliatewp-multi-level-affiliates') => $affiliate_id,
      __('Status', 'affiliatewp-multi-level-affiliates') => affwp_get_affiliate_status($affiliate_id),
      __('Paid', 'affiliatewp-multi-level-affiliates') => affwp_get_affiliate_earnings($affiliate_id, true),
      __('Unpaid', 'affiliatewp-multi-level-affiliates') => affwp_get_affiliate_unpaid_earnings($affiliate_id, true)
    );

    // deprecated
    $chart_info = apply_filters('mla_dashboard_chart_modal_data', $chart_info, $this->mla_mode(), $affiliate_id);

    $chart_location = (is_admin()) ? 'admin_area' : 'front_end';
    $chart_info = apply_filters('mla_chart_node_info', $chart_info, $affiliate_id, $chart_location);

    ob_start();

    ?>
    <div class="affiliate_info_container">
      <div class="info-box-title"><?php echo '<strong>' . affwp_get_affiliate_name($affiliate_id) . '</strong>'; ?></div>

      <div class="info-rows">
        <?php
        foreach ($chart_info as $title => $info) :
          $title = (!empty($title)) ? '<strong>' . $title . ': </strong>' : '';
          ?>
          <div class="aff_info_row"><?php echo $title . $info; ?></div>
        <?php endforeach; ?>
        <?php do_action('mla_dashboard_chart_add_info_row', $affiliate_id); ?>
      </div>
      <?php do_action('mla_dashboard_chart_after_info_rows', $affiliate_id); ?>

      <div class="chart_affiliate_actions">
        <?php if (is_admin()) { ?>
        <span><a target="_blank" href="<?php echo get_site_url('', '/wp-admin/admin.php?page=affiliate-wp-affiliates&action=edit_affiliate&affiliate_id=' . $affiliate_id); ?>">Edit</a><span>
    	<?php } else { ?>
      <?php } ?>
      <?php do_action('mla_dashboard_chart_add_actions', $affiliate_id); ?>
      </div>
      <?php do_action('mla_dashboard_chart_after_actions', $affiliate_id); ?>

    </div>
    <?php

    $html = ob_get_contents();
    ob_end_clean();
    return $html;

  }

  // Get chart dependencies
  public function get_chart_dependencies() {

    ob_start();

    ?>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/solid.css" integrity="sha384-v2Tw72dyUXeU3y4aM2Y0tBJQkGfplr39mxZqlTBDUZAb9BGoC40+rdFCG0m10lXk" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/fontawesome.css" integrity="sha384-q3jl8XQu1OpdLgGFvNRnPdj5VIlCvgsDQTQB6owSOHWlAurxul7f+JpUOVdAiJ5P"
          crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo plugins_url(); ?>/affiliatewp-multi-level-affiliates/plugin_core/includes/js/lib/OrgChart-master/dist/css/jquery.orgchart.css">
    <link rel="stylesheet" href="<?php echo plugins_url(); ?>/affiliatewp-multi-level-affiliates/plugin_core/includes/css/affwp_mla_charts.css">
    <style type="text/css">
      <?php if( !apply_filters( 'mla_dashboard_chart_show_group_icon', true, $this->affiliate_id ) ) :?>
      .orgchart .fa-group:before, .orgchart .fa-users:before {
        content: "" !important;
      }

      <?php endif;?>
    </style>
    <!--<script type="text/javascript" src="https://code.jquery.com/jquery-3.1.0.min.js"></script>-->
    <script type="text/javascript" src="<?php echo plugins_url(); ?>/affiliatewp-multi-level-affiliates/plugin_core/includes/js/lib/OrgChart-master/dist/js/jquery.orgchart.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.js"></script>
    <script type="text/javascript" src="<?php echo plugins_url(); ?>/affiliatewp-multi-level-affiliates/plugin_core/includes/js/lib/download-master/download.min.js"></script>
    <?php

    $html = ob_get_contents();

    ob_end_clean();
    echo $html;

  }

}

?>