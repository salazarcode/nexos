<?php

class AffiliateWP_MLA_Statistics extends AffiliateWP_MLA_Common {

  public function __construct($affiliate_id = '') {

    parent::__construct();

    $this->affiliate_id = (empty($affiliate_id)) ? affwp_get_affiliate_id(get_current_user_id()) : $affiliate_id;

  }

  // Get network level stats
  public function get_network_level_stats($affiliate_id = '', $level = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $affiliate_obj = new AffiliateWP_MLA_Affiliate($affiliate_id);

    $matrix_level_vars = $affiliate_obj->get_matrix_level_vars();
    $matrix_total_levels = $matrix_level_vars['total_levels'];
    //$network_depth = $affiliate_obj->get_affiliates_network_depth();

    $level_stats = array();

    for ($level = 1; $level <= $matrix_total_levels; $level++) :

      $level_affiliates = $affiliate_obj->get_level_affiliates('', $level);

      $level_stats[$level]['affiliate_count'] = count($level_affiliates);
      $level_stats[$level]['earnings'] = 0.00;
      $level_stats[$level]['unpaid_earnings'] = 0.00;
      $level_stats[$level]['referrals'] = 0;
      $level_stats[$level]['unpaid_referrals'] = 0;

      // calculate the earnings per level
      foreach ($level_affiliates as $affiliate) :

        $level_stats[$level]['earnings'] += affwp_get_affiliate_earnings($affiliate->affiliate_id);
        $level_stats[$level]['unpaid_earnings'] += affwp_get_affiliate_unpaid_earnings($affiliate->affiliate_id);

        $level_stats[$level]['referrals'] += affwp_count_referrals($affiliate->affiliate_id, 'paid');
        $level_stats[$level]['unpaid_referrals'] += affwp_count_referrals($affiliate->affiliate_id, 'unpaid');

      endforeach;

    endfor;

    return $level_stats;

  }

  // Display the affiliate's best rate/s
  public function get_best_display_rates($affiliate_id = '') {

    if ( empty($affiliate_id) ) $affiliate_id = $this->affiliate_id;

    $affiliate_obj = new AffiliateWP_MLA_Affiliate($affiliate_id);
    $rates = $affiliate_obj->get_available_group_matrix_rates('', '1');

    //print_r($rates);

    $highest_flat_rate_group = $this->determine_highest_rate_and_group($rates, 'flat');
    $flat_key = (!empty($highest_flat_rate_group['key'])) ? $highest_flat_rate_group['key'] : '';
    $highest_percentage_rate_group = $this->determine_highest_rate_and_group($rates, 'percentage');
    $percentage_key = (!empty($highest_percentage_rate_group['key'])) ? $highest_percentage_rate_group['key'] : '';

    $network_stats = $this->get_network_level_stats();
    $network_depth = count($network_stats);

    $level_data = array();

    // new direct display
    $direct_group_id = 'default';
    if ( $this->groups_enabled() ) : // replace with filter

      $groups = get_affiliates_active_groups($affiliate_id);
      if ( !empty($groups) ) :
        $direct_group_id = key($groups);

      endif;

    endif;

    $direct_enabled = $this->matrix_setting('enable_direct_referral', $direct_group_id);
    if ( $direct_enabled ) :

      $direct_rate_type = $this->matrix_setting('direct_referral_rate_type', $direct_group_id);
      // Get the AffiliateWP type if required
      if ( $direct_rate_type == 'default' ) $direct_rate_type = affiliate_wp()->settings->get('referral_rate_type');

      $direct_rate_value = $this->matrix_setting('direct_referral_rate', $direct_group_id);

      //$referral_amount = AffiliateWP_MLA_Referral::calculate_referral_amount($direct_rate_type, $direct_rate_value, $matrix_data['matrix_order_total']);

      /*$matrix_data['referrals']['direct_referral'] = array(
        'affiliate_id' => $direct_affiliate_id,
        'referral_total' => $referral_amount,
        'special_referral' => 'direct_referral',
        'reference' => 'Direct Referral'
      );*/

      $level_data['Direct']['string'] = ($direct_rate_type = 'percentage') ? $direct_rate_value.'%' : affwp_currency_filter($direct_rate_value);

    endif;


    for ($level = 1; $level <= $network_depth; $level++) :

      $flat_rates = $affiliate_obj->get_available_group_matrix_rates('', $level);
      $level_data[$level]['flat'] = (!empty($flat_rates[$flat_key]['rate'])) ? $flat_rates[$flat_key]['rate'] : '';

      $percentage_rates = $affiliate_obj->get_available_group_matrix_rates('', $level);
      $level_data[$level]['percentage'] = (!empty($percentage_rates[$percentage_key]['rate'])) ? $percentage_rates[$percentage_key]['rate'] : '';

      // Set the display strings
      if ( !empty($level_data[$level]['flat']) && !empty($level_data[$level]['percentage']) ) {
        if ( $level == '1' ) {
          $level_data[$level]['string'] = (empty($level_data[$level]['flat'])) ? $level_data[$level]['percentage'] : 'Higher of ' . affwp_currency_filter($level_data[$level]['flat']) . ' or ' . $level_data[$level]['percentage'] . '%';
          $level_data[$level]['commission_note'] = '* either flat rate or percentage depending on which is paid to level 1';
        } else {
          $level_data[$level]['string'] = (empty($level_data[$level]['flat'])) ? $level_data[$level]['percentage'] : affwp_currency_filter($level_data[$level]['flat']) . ' or ' . $level_data[$level]['percentage'] . '% *';
        }
      } else {
        $level_data[$level]['string'] = (!empty($level_data[$level]['flat'])) ? affwp_currency_filter($level_data[$level]['flat']) : $level_data[$level]['percentage'] . '%';
      }

    endfor;

    return $level_data;

  }

  public function determine_highest_rate_and_group($rates, $type) {

    $rate_data = array();

    foreach ($rates as $key => $data) :

      if ( !empty($data['rate_type']) ) :

        if ( $data['rate_type'] == $type ) :

          if ( isset($rate_data['key']) ) {

            if ( $data['rate'] > $rate_data['value'] ) :

              $rate_data['key'] = $key;
              $rate_data['value'] = $data['rate'];

            endif;

          } else {

            $rate_data['key'] = $key;
            $rate_data['value'] = $data['rate'];

          }

        endif;

      endif;

    endforeach;

    return $rate_data;
  }

}

?>