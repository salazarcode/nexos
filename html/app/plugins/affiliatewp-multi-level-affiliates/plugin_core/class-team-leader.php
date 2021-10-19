<?php

class AffiliateWP_MLA_Team_Leader extends AffiliateWP_MLA_Common {

  public function __construct() {

    parent::__construct();

    if ( apply_filters('mla_leaders_features_enabled', true) ) :

      //add_filter( 'affwp_settings_emails', array( $this, 'mla_affwp_settings_emails' ) , 10, 1);
      //add_filter( 'affwp_email_tags', array( $this, 'mla_affwp_email_tags' ), 10, 2 );

      // add fields to the add/edit affiliate forms
      add_action('affwp_new_affiliate_end', array($this, 'tl_affiliate_form'));
      add_action('affwp_edit_affiliate_end', array($this, 'tl_affiliate_form'), 99);
      add_action('affwp_new_affiliate_end', array($this, 'stl_affiliate_form'));
      add_action('affwp_edit_affiliate_end', array($this, 'stl_affiliate_form'), 99);

      //add_action( 'affwp_post_insert_affiliate', array( $this, 'process_tl_affiliate' ), 11);
      //add_action( 'affwp_post_update_affiliate', array( $this, 'process_tl_edit' ) );

      add_filter('mla_matrix_data_end', array($this, 'mla_matrix_data'), 20, 1);

    endif;

  }


  // Process the team leader referrals
  public function mla_matrix_data($matrix_data) {

    $affiliate_id = $matrix_data['direct_affiliate_id'];
    $settings = $matrix_data['default_matrix_settings'];

    // Process TL referral
    $matrix_data = $this->process_team_leader_referral($matrix_data, $settings, $affiliate_id);

    // Process STL referral
    $matrix_data = $this->process_super_team_leader_referral($matrix_data, $settings, $affiliate_id);

    return $matrix_data;

  }

  ///////////////////////////////// Team Leader Functions /////////////////////////////////

  // Process Team Leader referrals
  public function process_team_leader_referral($matrix_data, $settings, $affiliate_id) {

    // Group Leader settings hooked here
    $leader_settings = apply_filters('mla_team_leader_settings',
      array(
        'mode' => $settings['team_leader_mode'], /// new 'within_max_levels' or 'all_levels' or 'disabled'
        'team_leader_id' => $this->get_affiliates_team_leader($affiliate_id),
        'team_leader_level' => $this->get_affiliates_team_leader_level($affiliate_id),
        'rate_type' => $settings['team_leader_rate_type'],
        'min_level' => $settings['team_leader_min_level'],
        'max_level' => $settings['team_leader_max_level'],
        'rate_value' => $settings['team_leader_rate_value'],
        'single_only' => $settings['team_leader_single_only'], // 1 is checked
        'per_level_if_higher' => $settings['team_leader_rate_override'], // 1 is checked
        'matrix_data' => $matrix_data,
        'settings' => $settings,
        'affiliate_id' => $affiliate_id,
        'log' => array()
      )
    );

    $mode = $leader_settings['mode']; /// new 'within_max_levels' or 'all_levels' or 'disabled'
    $team_leader_id = $leader_settings['team_leader_id'];
    $team_leader_level = $leader_settings['team_leader_level'];
    $type = $leader_settings['rate_type'];
    $min_level = $leader_settings['min_level'];
    $max_level = $leader_settings['max_level'];
    $rate = $leader_settings['rate_value'];
    $single_only = $leader_settings['single_only']; // 1 is checked
    $per_level_if_higher = $leader_settings['per_level_if_higher']; // 1 is checked
    $log = $leader_settings['log'];

    //update_option( 'team_leader_debug1_5',  $leader_settings );

    // If team leader enabled
    if ( $mode != 'disabled' && !empty($team_leader_id) ) :

      //if( !empty($team_leader_id) ) :

      //$team_leader_level = $this->get_affiliates_team_leader_level( $affiliate_id );
      // Get the default Team leader settings
      //$type = $settings['team_leader_rate_type'];
      //$rate = $settings['team_leader_rate_value'];
      //$single_only = $settings['team_leader_single_only']; // 1 is checked
      //$per_level_if_higher = $settings['team_leader_rate_override']; // 1 is checked

      // Get the AffiliateWP type if required
      if ( $type == 'default' ) $type = affiliate_wp()->settings->get('referral_rate_type');

      // Maybe override the default rate with the Team Leaders rate
      if ( apply_filters('mla_allow_tl_rate', TRUE) ):
        $affiliate_rate = get_user_meta(affwp_get_affiliate_user_id($team_leader_id), 'tl_rate', true);
        $rate = (!empty($affiliate_rate)) ? $affiliate_rate : $rate;
      endif;

      // Get the order total
      $amount = $matrix_data['matrix_order_total'];
      $amount = apply_filters('mla_team_leader_referral_base_amount', $amount, array());

      // Calculate the amount
      if ( $type == 'percentage_remainder' ) {
        $referral_amount = $this->calculate_percentage_remainder($rate, $matrix_data);
      } else {
        //$real_type = ($type == 'percentage' || $type == 'percentage_remainder') ? 'percentage' : $type;
        $referral_amount = AffiliateWP_MLA_Referral::calculate_referral_amount($type, $rate, $amount);
      }

      // Referral Reference
      $referral_reference = __('Team Leader', 'affiliatewp-multi-level-affiliates');

      // Within max depth only
      if ( $mode == 'within_max_levels' ) {

        // Check if in max depth
        if ( $team_leader_level <= $settings['matrix_depth'] ) :

          // Single only
          if ( $single_only == 1 ) {

            // Override amount with per level rate if higher than the calculated one
            if ( $per_level_if_higher == 1 ) :

              if ( $matrix_data['referrals'][$team_leader_level]['referral_total'] > $referral_amount ) :

                $referral_amount = $matrix_data['referrals'][$team_leader_level]['referral_total'];

              endif;

            endif;

            // Modify the existing referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals'][$team_leader_level]['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals'][$team_leader_level]['referral_total'] = $referral_amount;
              $matrix_data['referrals'][$team_leader_level]['special_referral'] = $team_leader_level;
              $matrix_data['referrals'][$team_leader_level]['reference'] = $referral_reference;

              if ( !empty($log) ) :
                foreach ($log as $log_item) :
                  $matrix_data['referrals'][$team_leader_level]['log'][] = $log_item;
                endforeach;
              endif;
            endif;
            $matrix_data['referrals'][$team_leader_level]['log'][] = 'Amount modified by Team Leader: ' . $referral_amount;

            //not single only, create new referral
          } else {

            // add the new referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals']['team_leader']['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals']['team_leader']['referral_total'] = $referral_amount;
              $matrix_data['referrals']['team_leader']['special_referral'] = 'team_leader';
              $matrix_data['referrals']['team_leader']['reference'] = $referral_reference;
              $matrix_data['referrals']['team_leader']['log'] = $log;
            endif;

          }

        endif; // end check if within max depth

        // End max depth only
        // All levels. 95% the same as the above logic. Single only condition added to.
      } elseif ( $mode == 'all_levels' ) {

        // Single only
        if ( $single_only == 1 && $team_leader_level <= $settings['matrix_depth'] ) {

          // Override amount with per level rate if higher than the calculated one
          if ( $per_level_if_higher == 1 ) :

            if ( $matrix_data['referrals'][$team_leader_level]['referral_total'] > $referral_amount ) :

              $referral_amount = $matrix_data['referrals'][$team_leader_level]['referral_total'];

            endif;

          endif;

          // Modify the existing referral here
          if ( $referral_amount > 0 ) :
            $matrix_data['referrals'][$team_leader_level]['affiliate_id'] = $team_leader_id;
            $matrix_data['referrals'][$team_leader_level]['referral_total'] = $referral_amount;
            $matrix_data['referrals'][$team_leader_level]['special_referral'] = $team_leader_level;
            $matrix_data['referrals'][$team_leader_level]['reference'] = $referral_reference;

            if ( !empty($log) ) :
              foreach ($log as $log_item) :
                $matrix_data['referrals'][$team_leader_level]['log'][] = $log_item;
              endforeach;
            endif;
          endif;
          $matrix_data['referrals'][$team_leader_level]['log'][] = 'Amount modified by Team Leader: ' . $referral_amount;


        } else {

          // add the new referral here
          if ( $referral_amount > 0 ) :
            $matrix_data['referrals']['team_leader']['affiliate_id'] = $team_leader_id;
            $matrix_data['referrals']['team_leader']['referral_total'] = $referral_amount;
            $matrix_data['referrals']['team_leader']['special_referral'] = 'team_leader';
            $matrix_data['referrals']['team_leader']['reference'] = $referral_reference;
            $matrix_data['referrals']['team_leader']['log'] = $log;
          endif;

        }

      } elseif ( $mode == 'set_levels' ) {

        update_option('team_leader_debug1_8', $min_level . ' ' . $max_level . ' ' . $team_leader_level);
        //update_option( 'team_leader_debug1_9',  $this->set_levels_check( $min_level, $max_level, $team_leader_level));

        if ( $this->set_levels_check($min_level, $max_level, $team_leader_level) ) :
          //update_option( 'team_leader_debug1_8',  $min_level.' '.$max_level.' '.$team_leader_level );
          update_option('team_leader_debug1_9', $this->set_levels_check($min_level, $max_level, $team_leader_level));

          // Single only
          if ( $single_only == 1 && $team_leader_level <= $settings['matrix_depth'] ) {

            // Override amount with per level rate if higher than the calculated one
            if ( $per_level_if_higher == 1 ) :

              if ( $matrix_data['referrals'][$team_leader_level]['referral_total'] > $referral_amount ) :

                $referral_amount = $matrix_data['referrals'][$team_leader_level]['referral_total'];

              endif;

            endif;

            // Modify the existing referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals'][$team_leader_level]['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals'][$team_leader_level]['referral_total'] = $referral_amount;
              $matrix_data['referrals'][$team_leader_level]['special_referral'] = $team_leader_level;
              $matrix_data['referrals'][$team_leader_level]['reference'] = $referral_reference;

              if ( !empty($log) ) :
                foreach ($log as $log_item) :
                  $matrix_data['referrals'][$team_leader_level]['log'][] = $log_item;
                endforeach;
              endif;
            endif;
            $matrix_data['referrals'][$team_leader_level]['log'][] = 'Amount modified by Team Leader: ' . $referral_amount;


          } else {

            // add the new referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals']['team_leader']['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals']['team_leader']['referral_total'] = $referral_amount;
              $matrix_data['referrals']['team_leader']['special_referral'] = 'team_leader';
              $matrix_data['referrals']['team_leader']['reference'] = $referral_reference;
              $matrix_data['referrals']['team_leader']['log'] = $log;
            endif;

          }

        endif;

      }

      $matrix_data['team_leader_vars']['type'] = $type;
      $matrix_data['team_leader_vars']['rate'] = $rate;

      //endif; // end if team leader exists

    endif; // end if leader enabled

    return $matrix_data;

  }

  // Get the affialites team leader
  public function get_affiliates_team_leader($affiliate_id) {

    // Check if the direct affiliate is a TL
    if ( $this->is_a_team_leader($affiliate_id) ) :

      return $affiliate_id;

    else:

      $parent_id = 1; // temporary until first loop

      while (!empty($parent_id)) :
        $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
        $parent_id = $affiliate->get_parent_affiliate_id();

        if ( !empty($parent_id) && $this->is_a_team_leader($parent_id) ) :

          return $parent_id;

        endif;

        $affiliate_id = $parent_id;

      endwhile;

    endif;

    return '';

  }

  // Get the affiliates team leader level
  public function get_affiliates_team_leader_level($affiliate_id) {

    $parent_id = 1; // temporary until first loop

    $level = 1;
    while (!empty($parent_id)) :

      $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);

      $parent_id = $affiliate->get_parent_affiliate_id();

      $level++;

      if ( !empty($parent_id) && $this->is_a_team_leader($parent_id) ) :

        return $level;

      endif;

      $affiliate_id = $parent_id;

    endwhile;

    return '';

  }

  // Check if an affiliate is a team leader
  public function is_a_team_leader($affiliate_id) {

    $user_id = affwp_get_affiliate_user_id($affiliate_id);
    $tl_affiliate = get_user_meta($user_id, 'tl_affiliate', true);

    if ( $tl_affiliate == '1' ) {
      return (bool)TRUE;
    } else {
      return (bool)FALSE;
    }

  }

  public function tl_affiliate_form($affiliate = '') {

    $tl_affiliate = '';
    $tl_rate = '';
    $tl_title = '';

    // If edit affiliate form
    if ( !empty($affiliate) ) :

      $affiliate_id = $affiliate->affiliate_id;
      $user_id = affwp_get_affiliate_user_id($affiliate_id);

      $tl_affiliate = get_user_meta($user_id, 'tl_affiliate', true);
      $tl_rate = get_user_meta($user_id, 'tl_rate', true);
      $tl_title = get_user_meta($user_id, 'tl_title', true);

    endif;

    ob_start(); ?>

    <table class="form-table">
      <tbody>

      <tr class="form-row">

        <th scope="row">
          <label for="rate"><?php _e('Team Leader', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <select name="tl_affiliate" id="tl_affiliate">
            <option value="0">No</option>
            <option value="1" <?php selected('1', $tl_affiliate); ?>>Yes</option>
          </select>
          <!--<p class="description"></p>-->
        </td>

      </tr>

      <tr class="form-row">

        <th scope="row">
          <label for="rate"><?php _e('Team Leader Rate', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <input name="tl_rate" type="text" id="tl_rate" value="<?php echo $tl_rate; ?>">
          <span class="description"> Optional - Will override the Team Leader default rate value</span>
        </td>

      </tr>

      <!--<th scope="row">
              <label for="rate"><?php _e('Team Leader Title', 'affiliatewp-multi-level-affiliates'); ?></label>
         </th>
                
            <td>
				<input name="tl_title" type="text" id="pa_title" value="<?php echo $tl_title; ?>">
			</td>
            
         </tr>-->

      </tbody>
    </table>

    <?php
    $content = ob_get_contents();
    ob_end_clean();
    echo $content;

  }

  ///////////////////////////////// Super Team Leader Functions /////////////////////////////////

  // Process Super Team Leader referrals
  public function process_super_team_leader_referral($matrix_data, $settings, $affiliate_id) {

    $leader_settings = apply_filters('mla_steam_leader_settings',
      array(
        'mode' => $settings['steam_leader_mode'], /// new 'within_max_levels' or 'all_levels' or 'disabled'
        'steam_leader_id' => $this->get_affiliates_super_team_leader($affiliate_id),
        'steam_leader_level' => $this->get_affiliates_super_team_leader_level($affiliate_id),
        'rate_type' => $settings['steam_leader_rate_type'],
        'min_level' => $settings['steam_leader_min_level'],
        'max_level' => $settings['steam_leader_max_level'],
        'rate_value' => $settings['steam_leader_rate_value'],
        'single_only' => $settings['steam_leader_single_only'], // 1 is checked
        'per_level_if_higher' => $settings['steam_leader_rate_override'], // 1 is checked
        'matrix_data' => $matrix_data,
        'settings' => $settings,
        'affiliate_id' => $affiliate_id,
        'log' => array()
      )
    );

    $mode = $leader_settings['mode']; /// new 'within_max_levels' or 'all_levels' or 'disabled'
    $team_leader_id = $leader_settings['steam_leader_id'];
    $team_leader_level = $leader_settings['steam_leader_level'];
    $type = $leader_settings['rate_type'];
    $min_level = $leader_settings['min_level'];
    $max_level = $leader_settings['max_level'];
    $rate = $leader_settings['rate_value'];
    $single_only = $leader_settings['single_only']; // 1 is checked
    $per_level_if_higher = $leader_settings['per_level_if_higher']; // 1 is checked
    $log = $leader_settings['log'];

    // If team leader enabled
    if ( $mode != 'disabled' && !empty($team_leader_id) ) :

      //if( !empty($team_leader_id) ) :

      //$team_leader_level = $this->get_affiliates_super_team_leader_level( $affiliate_id );

      // Get the default Super Team leader settings
      //$type = $settings['steam_leader_rate_type'];
      //$rate = $settings['steam_leader_rate_value'];
      //$single_only = $settings['steam_leader_single_only']; // 1 is checked
      //$per_level_if_higher = $settings['steam_leader_rate_override']; // 1 is checked

      // Get the AffiliateWP type if required
      if ( $type == 'default' ) $type = affiliate_wp()->settings->get('referral_rate_type');

      // Maybe override the default rate with the affiliates rate
      if ( apply_filters('mla_allow_stl_rate', TRUE) ):
        $affiliate_rate = get_user_meta(affwp_get_affiliate_user_id($team_leader_id), 'stl_rate', true);
        $rate = (!empty($affiliate_rate)) ? $affiliate_rate : $rate;
      endif;

      // Get the order total
      $amount = $matrix_data['matrix_order_total'];
      $amount = apply_filters('mla_super_team_leader_referral_base_amount', $amount, array());

      // Calculate the amount
      if ( $type == 'percentage_remainder' ) {
        $referral_amount = $this->calculate_percentage_remainder($rate, $matrix_data);
      } else {
        //$real_type = ($type == 'percentage' || $type == 'percentage_remainder') ? 'percentage' : $type;
        $referral_amount = AffiliateWP_MLA_Referral::calculate_referral_amount($type, $rate, $amount);
      }

      // Referral Reference
      $referral_reference = __('Super Team Leader', 'affiliatewp-multi-level-affiliates');

      // Within max depth only
      if ( $mode == 'within_max_levels' ) {
        // Check if in max depth
        if ( $team_leader_level <= $settings['matrix_depth'] ) :

          // Single only
          if ( $single_only == 1 ) {

            // Override amount with per level rate if higher than the calculated one
            if ( $per_level_if_higher == 1 ) :

              if ( $matrix_data['referrals'][$team_leader_level]['referral_total'] > $referral_amount ) :

                $referral_amount = $matrix_data['referrals'][$team_leader_level]['referral_total'];

              endif;

            endif;

            // Modify the existing referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals'][$team_leader_level]['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals'][$team_leader_level]['referral_total'] = $referral_amount;
              $matrix_data['referrals'][$team_leader_level]['special_referral'] = $team_leader_level;
              $matrix_data['referrals'][$team_leader_level]['reference'] = 'Super Team Leader';

              if ( !empty($log) ) :
                foreach ($log as $log_item) :
                  $matrix_data['referrals'][$team_leader_level]['log'][] = $log_item;
                endforeach;
              endif;
            endif;
            $matrix_data['referrals'][$team_leader_level]['log'][] = 'Amount modified by Super Team Leader: ' . $referral_amount;

            //not single only, create new referral
          } else {

            // add the new referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals']['steam_leader']['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals']['steam_leader']['referral_total'] = $referral_amount;
              $matrix_data['referrals']['steam_leader']['special_referral'] = 'steam_leader';
              $matrix_data['referrals']['steam_leader']['reference'] = 'Super Team Leader';
              $matrix_data['referrals']['steam_leader']['log'] = $log;
            endif;

          }

        endif; // end check if within max depth

        // End max depth only, all levels. 95% the same as the above logic. Single only condition added to.
      } elseif ( $mode == 'all_levels' ) {

        // Single only
        if ( $single_only == 1 && $team_leader_level <= $settings['matrix_depth'] ) {

          // Override amount with per level rate if higher than the calculated one
          if ( $per_level_if_higher == 1 ) :

            if ( $matrix_data['referrals'][$team_leader_level]['referral_total'] > $referral_amount ) :

              $referral_amount = $matrix_data['referrals'][$team_leader_level]['referral_total'];

            endif;

          endif;

          // Modify the existing referral here
          if ( $referral_amount > 0 ) :
            $matrix_data['referrals'][$team_leader_level]['affiliate_id'] = $team_leader_id;
            $matrix_data['referrals'][$team_leader_level]['referral_total'] = $referral_amount;
            $matrix_data['referrals'][$team_leader_level]['special_referral'] = $team_leader_level;
            $matrix_data['referrals'][$team_leader_level]['reference'] = 'Super Team Leader';

            if ( !empty($log) ) :
              foreach ($log as $log_item) :
                $matrix_data['referrals'][$team_leader_level]['log'][] = $log_item;
              endforeach;
            endif;
            $matrix_data['referrals'][$team_leader_level]['log'][] = 'Amount modified by Super Team Leader: ' . $referral_amount;

          endif;


        } else {

          // add the new referral here
          if ( $referral_amount > 0 ) :
            $matrix_data['referrals']['steam_leader']['affiliate_id'] = $team_leader_id;
            $matrix_data['referrals']['steam_leader']['referral_total'] = $referral_amount;
            $matrix_data['referrals']['steam_leader']['special_referral'] = 'steam_leader';
            $matrix_data['referrals']['steam_leader']['reference'] = 'Super Team Leader';
            $matrix_data['referrals']['steam_leader']['log'] = $log;
          endif;

        }
      } elseif ( $mode == 'set_levels' ) {

        update_option('team_leader_debug1_10', $min_level . ' ' . $max_level . ' ' . $team_leader_level);

        if ( $this->set_levels_check($min_level, $max_level, $team_leader_level) ) :

          // Single only
          if ( $single_only == 1 && $team_leader_level <= $settings['matrix_depth'] ) {

            // Override amount with per level rate if higher than the calculated one
            if ( $per_level_if_higher == 1 ) :

              if ( $matrix_data['referrals'][$team_leader_level]['referral_total'] > $referral_amount ) :

                $referral_amount = $matrix_data['referrals'][$team_leader_level]['referral_total'];

              endif;

            endif;

            // Modify the existing referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals'][$team_leader_level]['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals'][$team_leader_level]['referral_total'] = $referral_amount;
              $matrix_data['referrals'][$team_leader_level]['special_referral'] = $team_leader_level;
              $matrix_data['referrals'][$team_leader_level]['reference'] = 'Super Team Leader';

              if ( !empty($log) ) :
                foreach ($log as $log_item) :
                  $matrix_data['referrals'][$team_leader_level]['log'][] = $log_item;
                endforeach;
              endif;
              $matrix_data['referrals'][$team_leader_level]['log'][] = 'Amount modified by Super Team Leader: ' . $referral_amount;

            endif;


          } else {

            // add the new referral here
            if ( $referral_amount > 0 ) :
              $matrix_data['referrals']['steam_leader']['affiliate_id'] = $team_leader_id;
              $matrix_data['referrals']['steam_leader']['referral_total'] = $referral_amount;
              $matrix_data['referrals']['steam_leader']['special_referral'] = 'steam_leader';
              $matrix_data['referrals']['steam_leader']['reference'] = 'Super Team Leader';
              $matrix_data['referrals']['steam_leader']['log'] = $log;
            endif;

          }

        endif;

      }


      $matrix_data['steam_leader_vars']['type'] = $type;
      $matrix_data['steam_leader_vars']['rate'] = $rate;

      //endif; // end if team leader exists

    endif; // end if leader enabled

    return $matrix_data;

  }

  // Get the affialites super team leader
  public function get_affiliates_super_team_leader($affiliate_id) {

    // Check if the direct affiliate is a STL
    if ( $this->is_a_super_team_leader($affiliate_id) ) :

      return $affiliate_id;

    else:

      $parent_id = 1; // temporary until first loop

      while (!empty($parent_id)) :
        $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
        $parent_id = $affiliate->get_parent_affiliate_id();

        if ( !empty($parent_id) && $this->is_a_super_team_leader($parent_id) ) :

          return $parent_id;

        endif;

        $affiliate_id = $parent_id;

      endwhile;

    endif;

    return '';

  }

  // Get the affialites super team leader level
  public function get_affiliates_super_team_leader_level($affiliate_id) {

    $parent_id = 1; // temporary until first loop

    $level = 1;
    while (!empty($parent_id)) :

      $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);

      $parent_id = $affiliate->get_parent_affiliate_id();

      $level++;

      if ( !empty($parent_id) && $this->is_a_super_team_leader($parent_id) ) :

        return $level;

      endif;

      $affiliate_id = $parent_id;

    endwhile;

    return '';

  }

  // Check if an affiliate is a team leader
  public function is_a_super_team_leader($affiliate_id) {

    $user_id = affwp_get_affiliate_user_id($affiliate_id);
    $tl_affiliate = get_user_meta($user_id, 'stl_affiliate', true);

    if ( $tl_affiliate == '1' ) {
      return (bool)TRUE;
    } else {
      return (bool)FALSE;
    }

  }

  public function stl_affiliate_form($affiliate = '') {

    $stl_affiliate = '';
    $stl_rate = '';
    $stl_title = '';

    // If edit affiliate form
    if ( !empty($affiliate) ) :

      $affiliate_id = $affiliate->affiliate_id;
      $user_id = affwp_get_affiliate_user_id($affiliate_id);

      $stl_affiliate = get_user_meta($user_id, 'stl_affiliate', true);
      $stl_rate = get_user_meta($user_id, 'stl_rate', true);
      $stl_title = get_user_meta($user_id, 'stl_title', true);

    endif;

    ob_start(); ?>

    <table class="form-table">
      <tbody>

      <tr class="form-row">

        <th scope="row">
          <label for="rate"><?php _e('Super Team Leader', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <select name="stl_affiliate" id="stl_affiliate">
            <option value="0">No</option>
            <option value="1" <?php selected('1', $stl_affiliate); ?>>Yes</option>
          </select>
          <!--<p class="description"></p>-->
        </td>

      </tr>

      <tr class="form-row">

        <th scope="row">
          <label for="rate"><?php _e('Super Team Leader Rate', 'affiliatewp-multi-level-affiliates'); ?></label>
        </th>

        <td>
          <input name="stl_rate" type="text" id="stl_rate" value="<?php echo $stl_rate; ?>">
          <span class="description"> Optional - Will override the default Super Team Leader rate value</span>
        </td>

      </tr>


      <!--<th scope="row">
              <label for="rate"><?php _e('Team Leader Title', 'affiliatewp-multi-level-affiliates'); ?></label>
         </th>
                
            <td>
				<input name="stl_title" type="text" id="stl_title" value="<?php echo $stl_title; ?>">
			</td>
            
         </tr>-->

      </tbody>
    </table>

    <?php
    $content = ob_get_contents();
    ob_end_clean();
    echo $content;

  }

  ///////////// Helpers //////////////
  public function set_levels_check($min_level = '', $max_level = '', $team_leader_level) {

    if ( !empty($min_level) && ($team_leader_level < $min_level) ) :

      return false;

    endif;

    if ( !empty($max_level) && ($team_leader_level > $max_level) ) :

      return false;

    endif;

    return true;

  }

  /// Now in common
  /*public function calculate_percentage_remainder( $max_percentage, $matrix_data ) {

      $commission_total = $this->get_commissions_total($matrix_data);
      $max_commission = AffiliateWP_MLA_Referral::calculate_referral_amount( 'percentage', $max_percentage, $matrix_data['matrix_order_total'] );

      if( $max_commission > $commission_total ) {

          $percentage_remainder = ($max_commission - $commission_total);
          return $percentage_remainder;

      }else {

          return 0;

      }

  }*/

} // end of class
?>