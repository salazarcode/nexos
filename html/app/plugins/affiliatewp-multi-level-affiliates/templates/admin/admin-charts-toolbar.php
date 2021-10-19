<?php
$data = (isset($data)) ? $data : array();
$charts_affiliate_id = (empty($_POST['affiliate_id'])) ? '' : $_POST['affiliate_id'];

$network_chart = new AffiliateWP_MLA_Charts($charts_affiliate_id);
$chart_settings = $network_chart->get_chart_settings($data);

$ps = new AffiliateWP_Multi_Level_Affiliates_Settings();
$all_affiliates = $ps->get_affiliates_dropdown();
//echo '<pre>'; print_r($all_affiliates); echo '</pre>';

$network_chart->get_chart_dependencies();
?>

<div id="affwp-mla-reports-toolbar" class="affwp-mla-reports-toolbar admin">

  <form method="post">

    <input name="page" type="hidden" id="page" value="affiliate-wp-reports">
    <input name="tab" type="hidden" id="tab" value="mla_charts">

    <div>

      <table class="affwp-tab-table" width="100%" cellpadding="5" cellspacing="5">
        <tbody>
        <tr>
          <td width="20%">
            <select name="chart" id="chart">
              <option value="network"><?php _e('Affiliate Network', 'affiliatewp-multi-level-affiliates'); ?></option>
            </select>
          </td>
          <td width="35%">
            <select name="affiliate_id" id="parent_affiliate_id">
              <option value=""><?php _e('Select Affiliate', 'affiliatewp-multi-level-affiliates'); ?></option>
              <?php
              foreach ($all_affiliates as $affiliate_id => $name) :
                ?>
                <option value="<?php echo $affiliate_id; ?>"
                        <?php if ($charts_affiliate_id == $affiliate_id) { ?>selected="selected"<?php }; ?>><?php echo $name; ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <input type="submit" name="mla_generate_chart" class="mla_generate_chart" value="Generate Chart">
          </td>
          <td>
            <div class="mla_chart_toolbar_actions" style="text-align:right">
              <span class="chart_expand fas fa-expand-arrows-alt"></span>
              <span class="chart_export fas fa-download"></span>
            </div>
          </td>
        </tr>
        <tr>
          <td>
            <input name="chart_enable_pan" type="checkbox" id="chart_enable_pan"
                   value="1" <?php if ( isset($chart_settings->pan) && $chart_settings->pan ) echo 'checked'; ?>>
            <label for="chart_enable_pan" class="mla_chart_toolbar_settings"><?php _e('Enable Pan', 'affiliatewp-multi-level-affiliates'); ?></label>
          </td>
          <td>
            <input name="chart_enable_zoom" type="checkbox" id="chart_enable_zoom"
                   value="1" <?php if ( isset($chart_settings->zoom) && $chart_settings->zoom ) echo 'checked'; ?>>
            <label for="chart_enable_zoom" class="mla_chart_toolbar_settings"><?php _e('Enable Zoom', 'affiliatewp-multi-level-affiliates'); ?></label>
          </td>
          <td>
          </td>
          <td>
          </td>
        </tr>
        </tbody>
      </table>

    </div>

  </form>

</div>