<?php

class AffiliateWP_MLA_Groups extends AffiliateWP_MLA_Common {

    public function __construct() {

        parent::__construct();

        add_filter('mla_matrix_data', array($this, 'mla_matrix_data'), 15, 1);

        add_filter('mla_team_leader_settings', array($this, 'mla_leader_settings'), 10, 1);
        add_filter('mla_steam_leader_settings', array($this, 'mla_leader_settings'), 11, 1);

    }

    /////// Main MLA Groups Processing ///////

    // Process the group matrix overrides
    public function mla_matrix_data($matrix_data) {

        //$default_level_affiliates = $matrix_data['matrix_level_affiliates'];
        //$default_referrals = $matrix_data['referrals'];

        $group_level_rates = array();

        if ( $this->groups_enabled() ) :

            $generate_affiliates = $this->process_groups_referrals($matrix_data);

            //update_option( 'groups_test', $generate_affiliates );

            if ( !empty($generate_affiliates) ) :

                foreach ($generate_affiliates as $level => $data) :

                    // Set in the new groups rates array to save later
                    $group_level_rates[$level] = $data;

                    $affiliate_id = $data['affiliate_id'];
                    $group_id = $data['group_id'];
                    $rate_type = $data['rate_type'];
                    $rate_value = $data['rate_value'];

                    $filter_vars = array(
                        'affiliate_id' => $affiliate_id,
                        'matrix_order_total' => $matrix_data['matrix_order_total'],
                        'products' => (!empty($matrix_data['products'])) ? $matrix_data['products'] : array(),
                        'existing_referral_totals' => (!empty($matrix_data['referrals'])) ? $matrix_data['referrals'] : array(),
                        'level_variables' => $matrix_data['matrix_level_vars'],
                        'level' => $level,
                        'group_id' => $data['group_id']
                    );
                    $referral_base_amount = apply_filters('mla_groups_referral_base_amount', $matrix_data['matrix_order_total'], $filter_vars);

                    $referral_amount = AffiliateWP_MLA_Referral::calculate_referral_amount($rate_type, $rate_value, $referral_base_amount);

                    $default_referral_vars = array(
                        'rate_type' => $rate_type,
                        'rate_value' => $rate_value,
                        'base_amount' => $referral_base_amount
                    );
                    $referral_amount = apply_filters('mla_referral_amount_groups', $referral_amount, $matrix_data, $filter_vars, $default_referral_vars);

                    if ( $level == 1 ) {

                        // If direct referral mode is disabled on this group, don't modify the referral
                        if ( $this->matrix_setting('direct_referral_mode', $group_id) != 'mla' ) {
                        } else {

                            // Modify the referrals array. Level 1 always exists
                            if ( $matrix_data['referrals'][$level]['referral_total'] != $referral_amount ) :

                                //$matrix_data['referrals'][$level]['affiliate_id'] = $affiliate_id;
                                $matrix_data['referrals'][$level]['referral_total'] = $referral_amount;
                                $matrix_data['referrals'][$level]['log'][] = __('Amount modified by MLA groups', 'affiliatewp-multi-level-affiliates') . ': ' . $referral_amount;

                            endif;

                            // Product based settings hooked here
                            $matrix_data['referrals'][$level] = apply_filters('mla_referral_groups', $matrix_data['referrals'][$level], $matrix_data, $filter_vars, $default_referral_vars);

                        }


                    } else {

                        // Does a referral already exist from the default array
                        if ( isset($matrix_data['referrals'][$level]) ) :

                            // Modify the referrals array
                            if ( $matrix_data['referrals'][$level]['referral_total'] != $referral_amount ) :

                                $matrix_data['referrals'][$level]['referral_total'] = $referral_amount;
                                $matrix_data['referrals'][$level]['log'][] = __('Amount modified by MLA groups', 'affiliatewp-multi-level-affiliates') . ': ' . $referral_amount;

                            endif;

                        else:

                            // Create new referral
                            $matrix_data['referrals'][$level] = array(
                                'affiliate_id' => $affiliate_id,
                                'referral_total' => $referral_amount,
                                'log' => array(__('Referral generated by MLA group matrix', 'affiliatewp-multi-level-affiliates') . ': ' . $referral_amount)
                            );


                        endif;

                        // Product based settings hooked here
                        $matrix_data['referrals'][$level] = apply_filters('mla_referral_groups', $matrix_data['referrals'][$level], $matrix_data, $filter_vars, $default_referral_vars);
                    }

                endforeach;

                // Remove ineligible affiliates from the referrals array unless their group uses the global rates
                $matrix_data['referrals'] = $this->remove_ineligible_affiliates($matrix_data['referrals'], $generate_affiliates);

                //update_option( 'groups_test_referrals', $matrix_data['referrals'] );

                /*if( empty($generate_affiliates['1']['group_id']) && $matrix_data['referrals']['1']['groups_mla_disabled'] == true) :
                    $matrix_data['referrals']['1']['referral_total'] = $matrix_data['args']['amount'];
                    $matrix_data['referrals']['1']['log'][] = __('Amount modified by MLA groups', 'affiliatewp-multi-level-affiliates') . ', ' . __('default group rate returned', 'affiliatewp-multi-level-affiliates') . ': ' . $matrix_data['args']['amount'];
                endif;*/

                // Save the default varibales before overriding
                //$matrix_data['default_referrals_backup'] = $default_referrals;

                // Save the new groups rates
                $matrix_data['groups_level_vars'] = $group_level_rates;

            endif;

        endif;

        return $matrix_data;

    }

    // Get the level affiliates and rate data
    public function process_groups_referrals($matrix_data) {

        $affiliates = array();

        if ( isset($matrix_data['matrix_level_affiliates'][1]) && !empty($matrix_data['matrix_level_affiliates'][1]) ) : // recently added

            $direct_affiliate_id = $matrix_data['matrix_level_affiliates'][1];

            // Get the maximum number of levels for all active groups
            $max_levels = $this->get_max_group_levels();

            // Loop through the parents and find the affiliates
            $loop_affiliate = $direct_affiliate_id;
            for ($level = 1; $level <= $max_levels; $level++) :

                $loop_affiliate_obj = new AffiliateWP_MLA_Affiliate($loop_affiliate);

                // Get affiliates groups and check that the current level is available
                $affiliates_groups = get_affiliates_active_groups($loop_affiliate);
                $approved_groups = array();

                if ( !empty($affiliates_groups) ) :

                    $affiliate_group_max = 0;
                    foreach ($affiliates_groups as $group_id => $args) :

                        if ( get_affiliate_group_setting('mla_mode', $group_id) != 'disabled' ) :

                            $group_max_levels = $this->matrix_setting('matrix_depth', $group_id);
                            if ( !empty($group_max_levels) )  :

                                if ( $group_max_levels >= $affiliate_group_max ) :

                                    $affiliate_group_max = $group_max_levels;
                                    array_push($approved_groups, $group_id);

                                endif;

                            endif;

                        endif;

                    endforeach;

                    // If level available for the affiliate
                    if ( $affiliate_group_max >= $level ) :

                        // Level is available, add the data
                        $rates = $this->get_best_group_rates($loop_affiliate, $approved_groups, $level, $matrix_data);

                        $affiliates[$level]['affiliate_id'] = $loop_affiliate;
                        $affiliates[$level]['group_id'] = (!empty($rates['group_id'])) ? $rates['group_id'] : '';
                        $affiliates[$level]['rate_type'] = (!empty($rates['rate_type'])) ? $rates['rate_type'] : '';
                        $affiliates[$level]['rate_value'] = (!empty($rates['rate_value'])) ? $rates['rate_value'] : '';

                    endif;

                endif;

                // Get the parent and continue loop
                $loop_affiliate = $loop_affiliate_obj->get_parent_affiliate_id();
                if ( empty($loop_affiliate) ) break;

            endfor;

            //update_option( 'groups_test', $affiliates );

        endif;

        return $affiliates;

    }

    // Get the maximum number of levels for all active MLA groups
    public function get_max_group_levels() {

        //$max_levels = $this->matrix_setting( 'matrix_depth', 'default' );
        $max_levels = 0;

        $active_groups = get_active_affiliate_groups();

        foreach ($active_groups as $group_id => $args) :

            $mla_mode = get_affiliate_group_setting('mla_mode', $group_id);

            if ( $mla_mode == 'enabled_extended' ) {

                $group_max_levels = $this->matrix_setting('matrix_depth', $group_id);

                if ( !empty($group_max_levels) ) :

                    if ( $group_max_levels > $max_levels ) $max_levels = $group_max_levels;

                endif;

            }

        endforeach;

        return $max_levels;

    }

    // Get the best rate (from MLA tab matrix settings) from the approved list of groups
    public function get_best_group_rates($affiliate_id, $approved_groups, $level, $matrix_data, $return = 'array') {

        //$best_group = '';
        $return_rates = array();

        $affiliate_obj = new AffiliateWP_MLA_Affiliate($affiliate_id);
        // Only using the group ID here, ignores the groups default rate
        $raw_group_rates = $affiliate_obj->get_available_group_matrix_rates('', $level);

        if ( !empty($raw_group_rates) ) :

            $filtered_group_rates = $raw_group_rates;

            if ( !empty($filtered_group_rates) ) :

                foreach ($raw_group_rates as $group_id => $data) :

                    if ( !in_array($group_id, $approved_groups) ) {

                        unset($filtered_group_rates[$group_id]);

                    }

                endforeach;

                //$best_group = $affiliate_obj->get_best_affiliate_group( $filtered_group_rates );
                $best_group = $this->get_best_group($filtered_group_rates, $matrix_data);

            endif;

        endif;

        if ( !empty($best_group) ) :

            $rate_type = ($this->matrix_setting('rate_type', $best_group) == 'default') ? affiliate_wp()->settings->get('referral_rate_type') : $this->matrix_setting('rate_type', $best_group);

            $return_rates['group_id'] = $best_group;
            $return_rates['rate_type'] = $rate_type;
            $return_rates['rate_value'] = $this->matrix_setting('level_' . $level . '_rate', $best_group);

        endif;

        return $return_rates;

    }

    // Find best group from rates and order total
    public function get_best_group($active_groups, $matrix_data) {

        $groups = array();
        $order_total = $matrix_data['matrix_order_total'];

        foreach ($active_groups as $key => $data) {
            $rate_type = $data['rate_type'];
            $rate = $data['rate'];

            if ( $rate_type == 'percentage' ) {
                $groups[$key] = ((($rate / 100) * $order_total));
            } elseif ( $rate_type == 'flat' ) {
                $groups[$key] = $rate;
            }/*elseif($rate_type == 'percentage_remainder') { // Only used in leader functions
			    $groups[$key] = $this->calculate_percentage_remainder( $rate, $matrix_data );
			}*/

        }

        arsort($groups);
        return key($groups);

    }

    // Remove any referrals for affiliate who have a group that is ineligible (based on level and MLA mode)
    // Bascially unless they were found to have eligible group MLA rates, remove them unless they are part of a group that uses the global matrix
    // The global rates remain in tact if no eligible group exists and the global product rates filter would have already been applied
    public function remove_ineligible_affiliates($referrals, $generate_affiliates) {

        $approved_affiliates = array();
        foreach ($generate_affiliates as $level => $data) :

            $approved_affiliates[] = $data['affiliate_id'];

        endforeach;

        // Loop all generated referrals
        foreach ($referrals as $level2 => $data2) :

            // If the affiliate is in a group but not in the array of approved affilates, remove them (the level)
            $referral_affiliate_id = $data2['affiliate_id'];
            $groups = get_affiliates_active_groups($referral_affiliate_id);
            if ( !empty($groups) ) :

                // Check if any use the global matrix
                //$group_disabled = true;
                foreach ($groups as $group_id => $group_name) :

                    // Ignore this check if any group uses the global matrix.
                    $mla_mode = get_affiliate_group_setting('mla_mode', $group_id);
                    //$mla_group_mode = get_affiliate_group_setting( 'mla_mode', $group_id);

                    //if( $mla_group_mode != 'disabled' ) $group_disabled = false;

                    //if( ($mla_mode == 'enabled') && $mla_group_mode != 'disabled') break;

                    if ( !in_array($referral_affiliate_id, $approved_affiliates) && $mla_mode != 'enabled' ) :

                        //unset( $referrals[$level2] );
                        //$referrals[$level2]['referral_total'] = 0;

                        $referrals[$level2]['referral_total'] = 0;
                        $referrals[$level2]['log'][] = __('Amount modified by MLA groups', 'affiliatewp-multi-level-affiliates') . ' (' . __('level not available', 'affiliatewp-multi-level-affiliates') . '): ' . '0';

                    endif;

                    // check for disabled. just used by the calling function to return the default group rate
                    /*if( in_array( $referral_affiliate_id, $approved_affiliates) && $level2==1 && $mla_mode == 'disabled' && count($groups) == 1) :

                        if( $mla_mode == 'disabled' && count($groups) == 1 && $level2==1) :

                            $referrals[$level2]['groups_mla_disabled'] = true;

                        endif;

                    endif;*/

                endforeach;

            endif;

        endforeach;

        return $referrals;

    }

    /////// Leader Features Processing ///////

    /// Wrapper for the two leaders
    public function mla_leader_settings($leader_settings) {

        if ( $this->groups_enabled() ) :

            if ( isset($leader_settings['team_leader_id']) ) :

                return $this->process_leader_settings($leader_settings, 'team_leader');

            elseif ( isset($leader_settings['steam_leader_id']) ):

                return $this->process_leader_settings($leader_settings, 'steam_leader');

            endif;

        endif;

        return $leader_settings;

    }

    // Find best group from rates and order total
    public function get_best_leader_group($active_groups, $leader_settings, $type) {

        $matrix_data = $leader_settings['matrix_data'];

        $groups = array();
        $order_total = $matrix_data['matrix_order_total'];

        //update_option( $type . '_leader_debug_10', $order_total);

        foreach ($active_groups as $key => $data) {
            $rate_type = $data['rate_type'];
            $rate = $data['rate_value'];

            $g_comm = 0;

            if ( $rate_type == 'percentage' ) {
                $g_comm = ((($rate / 100) * $order_total));
            } elseif ( $rate_type == 'flat' ) {
                $g_comm = $rate;
            } elseif ( $rate_type == 'percentage_remainder' ) { // Only used in leader functions
                $g_comm = $this->calculate_percentage_remainder($rate, $matrix_data);
            }

            if ( !empty($g_comm) ) {
                $groups[$key] = $g_comm;
            }

            // check the per level commission is higher (if required)
            $single_only = $this->matrix_setting($type . '_single_only', $key);
            $per_level_higher = $this->matrix_setting($type . '_rate_override', $key);
            if ( $single_only && $per_level_higher ) {

                $leader_level = $leader_settings['team_leader_level'];
                $per_level_comm = $matrix_data['referrals'][$leader_level]['referral_total'];

                if ( $per_level_comm > $g_comm ) {

                    $groups[$key] = $per_level_comm;

                    // Log data
                    $leader_settings['log'][] = 'Best group \'Per Level\' override: ' . key($groups);

                }
            }

        }

        arsort($groups);
        //return key($groups);
        return array('group_id' => key($groups), 'leader_settings' => $leader_settings);

    }

    public function process_leader_settings($leader_settings, $type) {

        //update_option( 'leader_debug', $leader_settings );
        //update_option( $type.'_leader_debug', $leader_settings );

        $affiliates_groups = get_affiliates_active_groups($leader_settings[$type . '_id']);

        if ( !empty($affiliates_groups) ) :

            // Add default settings as a group ???

            $active_groups = array();
            foreach ($affiliates_groups as $group_id => $args) :

                if ( $this->matrix_setting($type . '_mode', $group_id) != 'disabled' ) :

                    $active_groups[$group_id]['rate_type'] = $this->matrix_setting($type . '_rate_type', $group_id);
                    $active_groups[$group_id]['rate_value'] = $this->matrix_setting($type . '_rate_value', $group_id);

                endif;

            endforeach;

            if ( !empty($active_groups) ) :

                $best_group_data = $this->get_best_leader_group($active_groups, $leader_settings, $type);
                $best_group_id = $best_group_data['group_id'];
                $leader_settings = $best_group_data['leader_settings']; // might have added log data

            endif;

        endif;

        if ( !empty($best_group_id) ) :

            $leader_settings['mode'] = $this->matrix_setting($type . '_mode', $best_group_id);
            $leader_settings['rate_type'] = $this->matrix_setting($type . '_rate_type', $best_group_id);
            $leader_settings['min_level'] = $this->matrix_setting($type . '_min_level', $best_group_id);
            $leader_settings['max_level'] = $this->matrix_setting($type . '_max_level', $best_group_id);
            $leader_settings['rate_value'] = $this->matrix_setting($type . '_rate_value', $best_group_id);
            $leader_settings['single_only'] = $this->matrix_setting($type . '_single_only', $best_group_id);
            $leader_settings['per_level_if_higher'] = $this->matrix_setting($type . '_rate_override', $best_group_id);

            // Log data
            $leader_settings['log']['leader_groups_settings'] = array(
                'mode' => $leader_settings['mode'],
                'rate_type' => $leader_settings['rate_type'],
                'min_level' => $leader_settings['min_level'],
                'max_level' => $leader_settings['max_level'],
                'rate_value' => $leader_settings['rate_value'],
                'single_only' => $leader_settings['single_only'],
                'per_level_if_higher' => $leader_settings['per_level_if_higher']
            );
            if ( !empty($best_group_id) ) $leader_settings['log']['leader_groups_settings']['best_group'] = $best_group_id;

        endif;

        return $leader_settings;

    }


} // end of class

?>