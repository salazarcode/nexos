<?php

// Yet to complete
class AffiliateWP_MLA_Edd extends AffiliateWP_MLA_Common {

  protected $plugin_settings;
  protected $plugin_config;

  public function __construct() {

    /*
    if (defined('AFFWP_MLA_PLUGIN_CONFIG')) {
      $this->plugin_config = unserialize(AFFWP_MLA_PLUGIN_CONFIG);
    }else{
      $this->plugin_config = array();
    }
    if (defined('AFFWP_MLA_PLUGIN_SETTINGS')) {
      $this->plugin_settings = unserialize(AFFWP_MLA_PLUGIN_SETTINGS);
    }else{
      $this->plugin_settings = array();
    }
    */
    $affwp_mla = affiliate_wp_mla();
    $this->plugin_config = $affwp_mla->plugin_config;
    $this->plugin_settings = $affwp_mla->plugin_settings;

    $this->context = 'edd';

    // Per product settings
    //add_action( 'edd_meta_box_settings_fields', array( $this, 'mla_product_rates_meta_box_content' ), 100 );
    add_action('add_meta_boxes', array($this, 'downloads_meta_box'));
    add_filter('edd_metabox_fields_save', array($this, 'download_save_fields'));

    // Filter the referral amount
    //add_filter( 'mla_referral_amount', array( $this, 'mla_referral_amount' ) , 15, 4 );

    // Filter the referral amount
    //add_filter( 'mla_referral_amount', array( $this, 'mla_referral_amount' ) , 15, 4 );

    // Filter the referral
    add_filter('mla_referral', array($this, 'mla_referral'), 15, 4);

    // Filter the groups referral amount
    //add_filter( 'mla_referral_amount_groups', array( $this, 'mla_referral_amount_groups' ) , 15, 4 );

    // Filter the groups referral
    add_filter('mla_referral_groups', array($this, 'mla_referral_groups'), 15, 4);

    // Per product multipliers
    add_filter('mla_product_referral_amount_edd', array($this, 'mla_product_referral_amount_edd'), 10, 2);

  }

  // Add the downloads MLA meta box
  public function downloads_meta_box() {

    $screens = array('download');

    foreach ($screens as $screen) {
      add_meta_box(
        'mla-product-rates',
        __('MLA Product Rates', 'affiliatewp-multi-level-affiliates'),
        array($this, 'mla_product_rates_meta_box_content'),
        $screen
      );
    }
  }

  // Add the settings to the downloads MLA meta box
  public function mla_product_rates_meta_box_content($download_id = '') {

    $download_id = $_GET['post'];

    $global_disabled = $this->get_product_setting($download_id, 'referrals_disabled', 'default');
    $global_rate_type = $this->get_product_setting($download_id, 'product_rate_type', 'default');

    ?>
    <div class="affwp_mla_product_settings edd">
      <div class="options_group">

        <!--Global Settings-->
        <h4 class="matrix_group_heading"><?php _e('Global MLA Settings', 'affiliatewp-multi-level-affiliates'); ?></h4>
        <p>
          <label for="affwp_mla_edd_referrals_disabled_default"> <?php _e('Disable referrals', 'affiliatewp-multi-level-affiliates'); ?></label>
          <input type="checkbox" name="_affwp_mla_edd_referrals_disabled_default" id="affwp_mla_edd_referrals_disabled_default" value="1"<?php checked($global_disabled, true); ?> />
        </p>

        <p>
          <label for="_affwp_mla_edd_product_rate_type_default"><?php _e('Rate Type', 'affiliatewp-multi-level-affiliates'); ?></label>
          <select name="_affwp_mla_edd_product_rate_type_default" id="_affwp_mla_edd_product_rate_type_default">
            <option value=""><?php _e('Matrix Default', 'affiliatewp-multi-level-affiliates'); ?></option>
            <?php foreach (affwp_get_affiliate_rate_types() as $key => $type) : ?>
              <option value="<?php echo esc_attr($key); ?>"<?php selected($global_rate_type, $key); ?>><?php echo esc_html($type); ?></option>
            <?php endforeach; ?>
          </select>
        </p>

        <?php
        $global_levels = $this->matrix_setting('matrix_depth', 'default');
        for ($x = 1; $x <= $global_levels; $x++) :
          $level_value = $this->get_product_setting($download_id, 'product_rate', 'default', $x);
          ?>

          <p>
            <label for="_affwp_mla_edd_product_rate_default_level_<?php echo $x; ?>"><?php _e('Level', 'affiliatewp-multi-level-affiliates');
              echo ' ' . $x; ?></label>
            <input type="text" name="_affwp_mla_edd_product_rate_default_level_<?php echo $x; ?>" id="_affwp_mla_edd_product_rate_default_level_<?php echo $x; ?>" class=""
                   value="<?php echo esc_attr($level_value); ?>"/>
          </p>

        <?php
        endfor;
        ?>

        <?php
        /* Groups Settings */
        if ( $this->groups_enabled() ) :

          $groups = get_active_affiliate_groups();
          foreach ($groups as $id => $group_data) :
            if ( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :

              $group_disabled = $this->get_product_setting($download_id, 'referrals_disabled', $id);
              $group_rate_type = $this->get_product_setting($download_id, 'product_rate_type', $id);
              ?>

              <h4 class="matrix_group_heading"><?php echo $group_data['name'] . ' ' . __('MLA Settings', 'affiliatewp-multi-level-affiliates'); ?></h4>
              <p>
                <label for="affwp_mla_edd_referrals_disabled_<?php echo $id; ?>"><?php _e('Disable referrals', 'affiliatewp-multi-level-affiliates'); ?></label>
                <input type="checkbox" name="_affwp_mla_edd_referrals_disabled_<?php echo $id; ?>" id="affwp_mla_edd_referrals_disabled_<?php echo $id; ?>"
                       value="1"<?php checked($group_disabled, true); ?> />
              </p>

              <p>
                <label for="_affwp_mla_edd_product_rate_type_<?php echo $id; ?>"><?php _e('Rate Type', 'affiliatewp-multi-level-affiliates'); ?></label>
                <select name="_affwp_mla_edd_product_rate_type_<?php echo $id; ?>" id="_affwp_mla_edd_product_rate_type_<?php echo $id; ?>">
                  <option value=""><?php _e('Matrix Default', 'affiliatewp-multi-level-affiliates'); ?></option>
                  <?php foreach (affwp_get_affiliate_rate_types() as $key => $type) : ?>
                    <option value="<?php echo esc_attr($key); ?>"<?php selected($group_rate_type, $key); ?>><?php echo esc_html($type); ?></option>
                  <?php endforeach; ?>
                </select>
              </p>

              <?php
              $group_levels = $this->matrix_setting('matrix_depth', $id);
              for ($x = 1; $x <= $group_levels; $x++) :
                $level_value = $this->get_product_setting($download_id, 'product_rate', $id, $x);
                ?>
                <p>
                  <label for="_affwp_mla_edd_product_rate_<?php echo $id; ?>_level_<?php echo $x; ?>"><?php _e('Level', 'affiliatewp-multi-level-affiliates');
                    echo ' ' . $x; ?></label>
                  <input type="text" name="_affwp_mla_edd_product_rate_<?php echo $id; ?>_level_<?php echo $x; ?>" id="_affwp_mla_edd_product_rate_<?php echo $id; ?>_level_<?php echo $x; ?>" class=""
                         value="<?php echo esc_attr($level_value); ?>"/>
                </p>
              <?php

              endfor;

            endif;
          endforeach;

        endif;

        ?>
      </div>
    </div>

    <?php
  }

  // Save the settings
  public function download_save_fields($fields = array()) {

    $fields[] = '_affwp_mla_edd_referrals_disabled_default';
    $fields[] = '_affwp_mla_edd_product_rate_type_default';

    $global_levels = $this->matrix_setting('matrix_depth', 'default');
    for ($x = 1; $x <= $global_levels; $x++) :

      $fields[] = '_affwp_mla_edd_product_rate_default_level_' . $x;

    endfor;

    // Groups
    if ( $this->groups_enabled() ) :

      $groups = get_active_affiliate_groups();
      foreach ($groups as $id => $group_data) :

        if ( get_affiliate_group_setting('mla_mode', $id) == 'enabled_extended' ) :

          $fields[] = '_affwp_mla_edd_referrals_disabled_' . $id;
          $fields[] = '_affwp_mla_edd_product_rate_type_' . $id;

          $group_levels = $this->matrix_setting('matrix_depth', $id);
          for ($x = 1; $x <= $group_levels; $x++) :

            $fields[] = '_affwp_mla_edd_product_rate_' . $id . '_level_' . $x;

          endfor;

        endif;

      endforeach;

    endif;

    return $fields;
  }

  // Global product rules
  // Same in all integrations
  /*
  public function mla_referral_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars ) {

    $context = $matrix_data['args']['context'];
    if( $context != $this->context )  :
      return $referral_amount;
    endif;

    $commission = $this->get_commission_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars );

    return $commission;

  }
  */

  public function mla_referral($referral, $matrix_data, $filter_vars, $default_referral_vars) {

    //$referral_amount = $referral['referral_total'];

    $context = $matrix_data['args']['context'];
    if ( $context != $this->context )  :
      return $referral;
    endif;

    $new_referral_data = $this->get_referral_data($referral, $matrix_data, $filter_vars, $default_referral_vars);
    $new_referral_amount = $new_referral_data['referral_total'];
    $new_referral_log = $new_referral_data['log'];

    if ( $new_referral_data['referral_total'] != $referral['referral_total'] ) :

      $referral['referral_total'] = $new_referral_data['referral_total'];
      $referral['log'][] = __('Amount modified by MLA global product rates', 'affiliatewp-multi-level-affiliates') . ': ' . $new_referral_amount;

    endif;

    if ( !empty($new_referral_data['log']) ) $referral['log']['per_product'] = $new_referral_data['log'];

    return $referral;

  }


  // Group product rules. Filters each referral amount at each level found by the groups class
  // Loop through all of an affiliate's active groups and return the highest commission (multiple groups per affiliate)
  // Same in all integrations
  /*
  public function mla_referral_amount_groups( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars ) {

    $context = $matrix_data['args']['context'];
    if( $context != $this->context )  :
      return $referral_amount;
    endif;

    $commission = 0;

    $affiliate_id = $filter_vars['affiliate_id'];

    $groups = array();
    if( !empty($affiliate_id) ) $groups = get_affiliates_active_groups( $affiliate_id );

    if( !empty($groups) ) :

      foreach ( $groups as $group_id => $group_name ) :

        $group_commission = 0;

        $filter_vars['group_id'] = $group_id;
        $group_commission = $this->get_commission_amount( $referral_amount, $matrix_data, $filter_vars, $default_referral_vars );

        if( $group_commission > $commission ) $commission = $group_commission;

      endforeach;

    endif;

    return $commission;

  }
  */
  public function mla_referral_groups($referral, $matrix_data, $filter_vars, $default_referral_vars) {

    //$referral_amount = $referral['referral_total'];

    $context = $matrix_data['args']['context'];
    if ( $context != $this->context )  :
      return $referral;
    endif;

    $new_referral_amount = 0;
    $new_referral_log = array();

    $affiliate_id = $filter_vars['affiliate_id'];

    // Loop through all affiliate's groups and find the best
    $groups = array();
    if ( !empty($affiliate_id) ) $groups = get_affiliates_active_groups($affiliate_id);

    if ( !empty($groups) ) :

      foreach ($groups as $group_id => $group_name) :

        $group_referral_amount = 0;
        $group_referral_log = array();

        $filter_vars['group_id'] = $group_id;

        $group_referral_data = $this->get_referral_data($referral, $matrix_data, $filter_vars, $default_referral_vars);
        $group_referral_amount = $group_referral_data['referral_total'];
        $group_referral_log = $group_referral_data['log'];

        if ( $group_referral_data['referral_total'] > $new_referral_amount ) :

          $new_referral_amount = $group_referral_data['referral_total'];
          $new_referral_log = $group_referral_data['log'];

        endif;

      endforeach;

    endif;

    if ( $new_referral_amount != $referral['referral_total'] ) :

      $referral['referral_total'] = $new_referral_amount;
      $referral['log'][] = __('Amount modified by MLA group product rates', 'affiliatewp-multi-level-affiliates') . ': ' . $new_referral_amount;

    endif;

    if ( !empty($new_referral_log) ) $referral['log']['groups_per_product'] = $new_referral_log;

    return $referral;

  }

  // Regenerate the referral amount and log based on product settings
  // Same in all integrations
  public function get_referral_data($referral, $matrix_data, $referral_filter_vars, $default_referral_vars) {

    //$referral_amount = $referral['referral_total'];

    //$order_id = $matrix_data['args']['reference'];
    $level = $referral_filter_vars['level'];

    $group_id = (isset($referral_filter_vars['group_id']) && !empty($referral_filter_vars['group_id'])) ? $referral_filter_vars['group_id'] : 'default';

    $product_order_data = $this->get_product_order_data($matrix_data, $group_id);

    //$return_data = array( 'referral_total' => 0, 'log' => array( 'products' => array() ) );
    $return_data = array('referral_total' => 0, 'log' => array());

    foreach ($product_order_data['products'] as $product_id => $product_data) :

      $per_product_log = array();

      $product_order_total = $product_data['total'];
      $rate_type = $this->get_product_setting($product_id, 'product_rate_type', $group_id, $level);
      $rate_value = $this->get_product_setting($product_id, 'product_rate', $group_id, $level);

      // Fallback to standard per level rates
      $product_order_total = (isset($product_order_total)) ? $product_order_total : $default_referral_vars['base_amount'];
      $rate_type = (!empty($rate_type)) ? $rate_type : $default_referral_vars['rate_type'];
      $rate_value = (!empty($rate_value) || $rate_value == '0') ? $rate_value : $default_referral_vars['rate_value'];

      $product_order_total = apply_filters('mla_product_referral_order_total_' . $this->context, $product_order_total, array('product_id' => $product_id, 'product_data' => $product_data), $matrix_data);

      if ( !empty($product_order_total) && !empty($rate_type) && !empty($rate_value) ) :

        $product_referral_amount = AffiliateWP_MLA_Referral::calculate_referral_amount($rate_type, $rate_value, $product_order_total);

        $product_filter_vars = array(
          'rate_type' => $rate_type,
          'rate_value' => $rate_value,
          'base_amount' => $product_order_total,
          'product_id' => $product_id,
          'product_data' => $product_data,
          'matrix_data' => $matrix_data,
          'referral_filter_vars' => $referral_filter_vars
        );
        $product_referral_amount = apply_filters('mla_product_referral_amount_' . $this->context, $product_referral_amount, $product_filter_vars);

        // add the amount to the per product log
        $per_product_log['referral_amount'] = $product_referral_amount;

        $product_referral = array(
          'product_referral_amount' => $product_referral_amount,
          'product_referral_log' => $per_product_log
        );
        $product_referral = apply_filters('mla_product_referral_' . $this->context, $product_referral, $product_filter_vars);

        // Add the return data - total
        $return_data['referral_total'] += $product_referral['product_referral_amount'];

        // Add the return data -log
        if ( !empty($product_referral['product_referral_log']) ) :
          $return_data['log']['products'][$product_id] = $product_referral['product_referral_log'];
        endif;

        //$product_commission_amount = apply_filters( 'mla_product_referral_amount_'.$this->context, $product_commission_amount, $product_filter_vars );
        //$return_data['referral_total'] += $product_commission_amount;

      endif;

    endforeach;

    return $return_data;

  }

  // Get order details
  public function get_product_order_data($matrix_data, $group_id = 'default') {

    $products = $matrix_data['products'];
    if ( !empty($products) ) :

      foreach ($products as $key => $product) {

        // Check if product disabled
        if ( !$this->get_product_setting($product['id'], 'referrals_disabled', $group_id) ) :

          $order_data['products'][$product['id']]['total'] = $product['price'];
          $order_data['total'] += $product['price'];

        endif;

      }

    endif;

    return $order_data;

  }

  // Get product setting
  // keys: referrals_disabled, product_rate_type, product_rate (requires level)
  public function get_product_setting($product_id, $key, $group_id = 'default', $level = '', $fallback_to_parent = true) {

    $get_key = '_affwp_mla_' . $this->context . '_' . $key . '_' . $group_id;
    $get_key = (!empty($level) && $key == 'product_rate') ? $get_key . '_level_' . $level : $get_key;

    $setting = get_post_meta($product_id, $get_key, true);

    return $setting;

  }

  function mla_product_referral_amount_edd($product_commission_amount, $product_filter_vars) {

    $payment_id = $product_filter_vars['matrix_data']['args']['reference'];
    $product_id = $product_filter_vars['product_id'];
    //$rate_type = $product_filter_vars['rate_type'];

    // Flat rate multiplier
    if ( $product_filter_vars['rate_type'] == 'flat' ) :

    //$order = new WC_Order($order_id);
    $payment = new EDD_Payment($payment_id);
    //$items = $order->get_items();
    $cart_items = $payment->cart_details;

    //foreach ( $items as $product ) :
    foreach ($cart_items as $key => $cart_item) :

      //$line_product_id = ( !empty($product['variation_id']) ) ? $product['variation_id'] : $product['product_id'];
      $item_id = $cart_item['id'];

      if ( $item_id == $product_id ) :

        return $product_commission_amount * $cart_item['quantity'];

      endif;

    endforeach;

    endif;

    return $product_commission_amount;

  }


} // end class

?>