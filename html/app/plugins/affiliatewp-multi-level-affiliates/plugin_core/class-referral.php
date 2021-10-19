<?php

class AffiliateWP_MLA_Referral extends AffiliateWP_MLA_Common {

  public function __construct($referral_id) {

    parent::__construct();

    $this->referral_id = $referral_id;
    $this->referral = affwp_get_referral($referral_id);

  }

  // Get the custom MLA data or specific key. Complete. Tested
  public function mla_data($key = '') {

    if ( !empty($this->referral->custom) ) {

      $custom_data = maybe_unserialize($this->referral->custom);

      if ( !is_array($custom_data) ) {

        return '';

      } else {

        $mla_data = $custom_data['mla'];

        if ( empty($key) ) {
          return $mla_data;
        } else {
          return $mla_data[$key];
        }

      }

    } else {

      return '';

    }

  }

  // Outputs referral custom data
  public function display_mla_data($key = '') {

    $mla_data = $this->mla_data();

    if ( !empty($mla_data) ) :

      if ( !empty($key) ) :

        if ( $key == 'referrals' ) :

          $mla_rerrerals = (!empty($mla_data['referrals'])) ? $mla_data['referrals'] : array();
          echo '<pre>';
          print_r($mla_rerrerals);
          echo '</pre>';

        endif;

      else:

        echo '<pre>';
        print_r($mla_data);
        echo '</pre>';

      endif;

    endif;

  }

  // Process the indirect referrals. Complete. Tested
  public function process_indirect_referrals() {

    $indirect_reference = $this->referral->reference;
    //$indirect_context = $this->referral->context;

    $generated_referral_ids = array();

    $mla_data = $this->mla_data();

    if ( !empty($mla_data) ) {

      $mla_rerrerals = $mla_data['referrals'];

      if ( !empty($mla_rerrerals) ) :

        foreach ($mla_rerrerals as $level => $level_vars) {

          $referral_id = '';

          // Add the referral but ignore the first level as that's already dealt with
          if ( $level != 1 ) {

            if ( $level == 1 ) $direct_affiliate = $level_vars['affiliate_id'];

            $reference = (isset($level_vars['reference']) && !empty($level_vars['reference'])) ? $indirect_reference . ' - ' . $level_vars['reference'] : $indirect_reference . ' - ' . __('Level', 'affiliatewp-multi-level-affiliates') . ' ' . $level;
            $reference = apply_filters('mla_referral_reference', $reference, array('level' => $level, 'mla_data' => $mla_data));

            //$description = ( isset($level_vars['description']) && !empty($level_vars['description'])) ? $level_vars['description'] : __( 'Indirect referral from', 'affiliatewp-multi-level-affiliates' ).' '.$indirect_reference;
            $description = (isset($level_vars['description']) && !empty($level_vars['description'])) ? $level_vars['description'] : $mla_data['args']['description'];
            $description = apply_filters('mla_referral_description', $description, array('level' => $level, 'indirect_reference' => $indirect_reference, 'mla_data' => $mla_data));
            //$description = (!empty($special_description)) ? $special_description : 'Custom Description '.$level;

            $type = (isset($mla_data['args']['type']) && !empty($mla_data['args']['type'])) ? $mla_data['args']['type'] : 'sale';
            $type = apply_filters('mla_referral_type', $type, array('level' => $level, 'type' => $type, 'mla_data' => $mla_data));

            $custom = array('mla' => array('parent_referral' => $this->referral_id, 'level' => $level));
            $custom = maybe_serialize($custom);  // Add the direct affiliate info

            // Round the final amount
            $amount = $level_vars['referral_total'];

            $args = array(
              'affiliate_id' => $level_vars['affiliate_id'],
              'amount' => $amount,
              'description' => $description,
              'type' => $type,
              'reference' => $reference,
              'context' => 'mla',
              'custom' => $custom,
              'status' => 'pending',
            );

            $args = apply_filters('mla_indirect_referral_data', $args, $level);

            //if( !empty($args['affiliate_id']) && affwp_is_active_affiliate($args['affiliate_id']) ) :
            if ( !empty($args['affiliate_id']) && apply_filters('mla_referral_is_active_affiliate', affwp_is_active_affiliate($args['affiliate_id']), $args) ) :

              if ( apply_filters('mla_indirect_referral_add', TRUE, $args, $level) ) :

                // Ignore if AffiliateWP configured to ignore zero value referrals
                if ( $args['amount'] == 0 && affiliate_wp()->settings->get('ignore_zero_referrals') ) :

                else:

                  // Round the final amount
                  $args['amount'] = round($args['amount'], affwp_get_decimal_count());

                  $referral_id = affiliate_wp()->referrals->add($args);

                endif;

              endif;

            endif;

            if ( !empty($referral_id) ) :

              //if(empty($special_referral)) {

              $generated_referral_ids[$level] = $referral_id;

              //}else {

              //$generated_referral_ids[$special_referral] = $referral_id;

              //}

              do_action('mla_insert_pending_referral', $referral_id, $level_vars['affiliate_id'], $amount, $args);

            endif;
          }

        }

      endif;

    }

    if ( !empty($generated_referral_ids) ) :

      $this->save_generated_referral_ids($generated_referral_ids);

      if ( !empty($direct_affiliate) ) :

        do_action('mla_after_referrals_generated', $generated_referral_ids, $direct_affiliate);

      endif;

    endif;

  }

  // Save generated referral ids into the parents custom data. Complete. Tested
  public function save_generated_referral_ids($generated_referral_ids) {

    /*$existing_custom_data = $this->referral->custom;
    if( !empty($existing_custom_data) && is_array($existing_custom_data) ) {
      $custom_data = maybe_unserialize( $existing_custom_data );
    }else{
      $custom_data = array();
    }*/

    $custom_data = maybe_unserialize($this->referral->custom);

    //update_option( 'mla_debug_12', $custom_data );

    foreach ($generated_referral_ids as $level => $referral_id) :
      $custom_data['mla']['referrals'][$level]['referral_id'] = $referral_id;
    endforeach;

    $custom_data = maybe_serialize($custom_data);

    $data = array('custom' => $custom_data);

    //update_option( 'mla_debug_13', $data );

    affiliate_wp()->referrals->update($this->referral_id, $data, '', 'referral');

    // Regenerate the referral data. Required ?
    $this->referral = affwp_get_referral($this->referral_id);

  }

  // Complete referrals. Complete. Tested
  public function process_complete_referrals() {

    $mla_data = $this->mla_data('referrals');

    //update_option( 'mla_debug_14', $mla_data );

    //echo '<pre>'; print_r($mla_data); echo '</pre>';

    if ( !empty($mla_data) ) {
      foreach ($mla_data as $level => $level_vars) {
        if ( $level != 1 ) { // Ignore the direct referral
          affwp_set_referral_status($level_vars['referral_id'], 'unpaid');
          do_action('mla_complete_referral', $level_vars['referral_id']);
        }
      }
    }
  }

  // Reject / Refunded / Deleted / Voided. Requires AffiliateWP 1.8. Complete. Tested
  public function process_rejected_referral($new_status, $old_status) {

    // Process unpaid rejected, order refunds, order deleted, order voided
    if ( $old_status != 'paid' && $new_status == 'rejected' ) {

      $mla_data = $this->mla_data('referrals');

      if ( !empty($mla_data) ) {
        foreach ($mla_data as $level => $level_vars) {

          if ( $level != 1 ) { // Ignore the direct referral
            affwp_set_referral_status($level_vars['referral_id'], 'rejected');
            do_action('mla_reject_referral', $level_vars['referral_id']);
          }

        }
      }

    }

  }

  // Calculate the referral total. Complete. Tested
  public static function calculate_referral_amount($type, $rate, $amount) {

    //$this->store_debug_data( array('type' => $type, 'rate' => $rate, 'amount' => $amount ) );
    //if($type == 'default') $type = affiliate_wp()->settings->get( 'referral_rate_type' );

    if ( $type == 'percentage' ) {

      //if ( $rate >= 1 ) {
      $rate = $rate / 100;
      //}

      $referral_amount = ($amount * $rate);

    } elseif ( $type == 'flat' ) {

      $referral_amount = $rate;

    }

    return $referral_amount;
  }

  // Regenerate WooCommerce
  /*public function regenerate_referrals_woocommerce( $order_id ) {

  $order = new WC_Order($order_id);

  // referral object
  $referral = affiliate_wp()->referrals->get_by( 'reference', $order_id, 'woocommerce' );
  $referral_id = $referral->referral_id;
  $affiliate_id = $referral->affiliate_id;

  if ( !empty($referral_id) ) {

    // Get the order total

    $items = $order->get_items();
    $amount = 0.00;
    $order_total = 0.00;
    $products = array();
    $product_key = 0;

    foreach ( $items as $product ) {

      if ( get_post_meta( $product['product_id'], '_affwp_' .'woocommerce'. '_referrals_disabled', true ) ) {
        continue; // Referrals are disabled on this product
      }

      // The order discount has to be divided across the items
      $product_total = $product['line_total'];
      $shipping      = 0;

      if ( $cart_shipping > 0 && ! affiliate_wp()->settings->get( 'exclude_shipping' ) ) {
        $shipping       = $cart_shipping / count( $items );
        $product_total += $shipping;
      }

      if ( ! affiliate_wp()->settings->get( 'exclude_tax' ) ) {
        $product_total += $product['line_tax'];
      }

     $order_total += $product_total;

     $products[$product_key]['name'] = $product['name'];
         $products[$product_key]['id'] = $product['product_id'];
         $products[$product_key]['price'] = $product_total;

     $product_key++;

    }

    $this->regenerate_referrals( $referral, $order_total, $products );

  }

  }*/

  // Regenerate referrals
  /*public function regenerate_referrals( $referral, $order_total, $products = array() ) {

    $referral_id = $referral->referral_id;
    $custom = maybe_unserialize( $referral->custom );
    $mla_data = $custom['mla'];
    $rate_type = $mla_data['matrix_level_vars']['type'];

    foreach( $mla_data['referrals'] as $key => $data) :

      $level_referral_id = ($key == 1) ? $referral_id : $data['referral_id'];

      $rate = $mla_data['matrix_level_vars']['level_rates'][$key];

      $amount = $this->calculate_referral_amount($rate_type, $rate, $order_total);

      // Update the previously created referral
      affiliate_wp()->referrals->update_referral( $level_referral_id, array(
        'amount'       => $amount,
        //'reference'    => $order->ID,
        //'description'  => $description,
        //'campaign'     => affiliate_wp()->tracking->get_campaign(),
        //'affiliate_id' => $affiliate_id,
        //'visit_id'     => $visit_id,
        //'products'     => $this->get_products(),
        //'context'      => $this->context
      ) );

      $custom['mla']['referrals'][$key]['referral_total'] = $amount;

    endforeach;

    // Update the main referrals custom data
    $custom['mla']['products'] = $products;
    $custom['mla']['matrix_order_total'] = $order_total;

    $custom = maybe_serialize($custom);

    affiliate_wp()->referrals->update_referral( $referral_id, array(
        'custom'    => $custom,
      ) );

  }*/


}

?>