<?php

class AffiliateWP_MLA_Notifications extends AffiliateWP_MLA_Common {

  public function __construct() {

    parent::__construct();

    add_filter('affwp_settings_emails', array($this, 'mla_affwp_settings_emails'), 10, 1);
    add_filter('affwp_email_tags', array($this, 'mla_affwp_email_tags'), 10, 2);
    //add_action( 'mla_binary_after_order_linking', array( $this, 'affwp_notify_on_new_order' ), 10, 2);

    // Called at the end of the set parent function
    //add_filter( 'mla_set_parent_affiliate', array( $this, 'registration_notifications' ), 10, 2 );
    //do_action( 'mla_set_parent_affiliate', $affiliate_id, $parent_id, $this->connection_type );

    // Called after 2 registration hooks
    add_filter('mla_after_registration', array($this, 'registration_notifications'));

    // Called at the end of the generate indirect referrals function
    add_action('mla_after_referrals_generated', array($this, 'referral_notifications'), 10, 2);

  }

  // Add the notification settings
  public function mla_affwp_settings_emails($settings) {

    // if( $this->mla_mode == 'binary' ) :

    $settings['mla_reg_affiliate_subject'] = array(
      'name' => __('MLA Registration (Affiliate) Subject', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliatewp-multi-level-affiliates' )
    );

    $settings['mla_reg_affiliate_enable'] = array(
      'name' => __('Enable MLA Registration (Affiliate)', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'checkbox',
      //'std' => __( 'Referral Awarded!', 'affiliatewp-multi-level-affiliates' )
    );

    $settings['mla_reg_affiliate_email'] = array(
      'name' => __('MLA Registration (Affiliate) Content', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates') . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliatewp-multi-level-affiliates' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliatewp-multi-level-affiliates' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliatewp-multi-level-affiliates' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliatewp-multi-level-affiliates' ) . ' {login_url}'
    );

    $settings['mla_reg_parent_subject'] = array(
      'name' => __('MLA Registration (Referrer) Subject', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliatewp-multi-level-affiliates' )
    );

    $settings['mla_reg_parent_enable'] = array(
      'name' => __('Enable MLA Registration (Referrer)', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliate-wp'),
      'type' => 'checkbox',
      //'std' => __( 'Referral Awarded!', 'affiliatewp-multi-level-affiliates' )
    );

    $settings['mla_reg_parent_email'] = array(
      'name' => __('MLA Registration (Referrer) Content', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates') . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    $settings['mla_reg_tl_subject'] = array(
      'name' => __('MLA Registration (Team Leader) Subject', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_reg_tl_enable'] = array(
      'name' => __('Enable MLA Registration (Team Leader)', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'checkbox',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_reg_tl_email'] = array(
      'name' => __('MLA Registration (Team Leader) Content', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates') . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    $settings['mla_reg_stl_subject'] = array(
      'name' => __('MLA Registration (Super Team Leader) Subject', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_reg_stl_enable'] = array(
      'name' => __('Enable MLA Registration (Super Team Leader)', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'checkbox',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_reg_stl_email'] = array(
      'name' => __('MLA Registration (Super Team Leader) Content', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates') . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    $settings['mla_ref_tl_subject'] = array(
      'name' => __('MLA Referral (Team Leader) Subject', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_ref_tl_enable'] = array(
      'name' => __('Enable MLA Referral (Team Leader)', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'checkbox',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_ref_tl_email'] = array(
      'name' => __('MLA Referral (Team Leader) Content', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates') . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    $settings['mla_ref_stl_subject'] = array(
      'name' => __('MLA Referral (Super Team Leader) Subject', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_ref_stl_enable'] = array(
      'name' => __('Enable MLA Referral (Super Team Leader)', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates'),
      'type' => 'checkbox',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_ref_stl_email'] = array(
      'name' => __('MLA Referral (Super Team Leader) Content', 'affiliatewp-multi-level-affiliates'),
      'desc' => __('', 'affiliatewp-multi-level-affiliates') . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    /*$settings['mla_order_subject'] = array(
      'name' => __( 'New Affiliate Order Email Subject', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_order_email'] = array(
      'name' => __( 'New Affiliate Order Email Content', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ) . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    $settings['mla_inactivity_subject'] = array(
      'name' => __( 'Inactivity Warning Email Subject', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_inactivity_email'] = array(
      'name' => __( 'Inactivity Warning Email Content', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ) . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    $settings['mla_inactivity_subject_admin'] = array(
      'name' => __( 'Admin Inactivity Warning Email Subject', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_inactivity_address_admin'] = array(
      'name' => __( 'Admin Inactivity Warning Email', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_inactivity_email_admin'] = array(
      'name' => __( 'Admin Inactivity Warning Email Content', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ) . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );

    $settings['mla_inactivity_subject_parent'] = array(
      'name' => __( 'Parent Inactivity Warning Email Subject', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ),
      'type' => 'text',
      //'std' => __( 'Referral Awarded!', 'affiliate-wp' )
    );

    $settings['mla_inactivity_email_parent'] = array(
      'name' => __( 'Parent Inactivity Warning Email Content', 'affiliate-wp' ),
      'desc' => __( '', 'affiliate-wp' ) . '<br />' . affwp_get_emails_tags_list(),
      'type' => 'rich_editor',
      //'std' => __( 'Congratulations {name}!', 'affiliate-wp' ) . "\n\n" . __( 'You have been awarded a new referral of', 'affiliate-wp' ) . ' {amount} ' . sprintf( __( 'on %s!', 'affiliate-wp' ), home_url() ) . "\n\n" . __( 'Log into your affiliate area to view your earnings or disable these notifications:', 'affiliate-wp' ) . ' {login_url}'
    );*/

    //endif;

    return $settings;

  }

  // Add new tags
  public function mla_affwp_email_tags($tags, $email_obj) {

    /*$tags[] = array(
        'tag'         => 'mla_affiliate_first_name',
        'description' => __( 'MLA Affiliate first name', 'affiliate-wp' ),
        'function'    => 'AffiliateWP_MLA_Notifications::mla_affiliate_first_name'
      );

    $tags[] = array(
        'tag'         => 'mla_affiliate_last_name',
        'description' => __( 'MLA affiliate last name', 'affiliate-wp' ),
        'function'    => 'AffiliateWP_MLA_Notifications::mla_affiliate_last_name'
      );*/

    $tags[] = array(
      'tag' => 'mla_affiliate_full_name',
      'description' => __('MLA affiliate full name', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_affiliate_full_name'
    );

    $tags[] = array(
      'tag' => 'mla_affiliate_contact_details',
      'description' => __('MLA affiliate contact details', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_affiliate_contact_details'
    );

    $tags[] = array(
      'tag' => 'mla_parent_first_name',
      'description' => __('MLA Parent first name', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_parent_first_name'
    );

    $tags[] = array(
      'tag' => 'mla_parent_last_name',
      'description' => __('MLA Parent last name', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_parent_last_name'
    );

    $tags[] = array(
      'tag' => 'mla_parent_full_name',
      'description' => __('MLA Parent full name', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_parent_full_name'
    );

    $tags[] = array(
      'tag' => 'mla_parent_contact_details',
      'description' => __('MLA Parent contact details', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_parent_contact_details'
    );

    $tags[] = array(
      'tag' => 'mla_team_leader_first_name',
      'description' => __('MLA Team Leader first name', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_team_leader_first_name'
    );

    $tags[] = array(
      'tag' => 'mla_team_leader_last_name',
      'description' => __('MLA Team Leader last name', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_team_leader_last_name'
    );

    $tags[] = array(
      'tag' => 'mla_team_leader_full_name',
      'description' => __('MLA Team Leader full name', 'affiliate-wp'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_team_leader_full_name'
    );

    $tags[] = array(
      'tag' => 'mla_team_leader_contact_details',
      'description' => __('MLA Team Leader contact details', 'affiliatewp-multi-level-affiliates'),
      'function' => 'AffiliateWP_MLA_Notifications::mla_team_leader_contact_details'
    );

    return $tags;

  }

  // Tag functions

  // Parse tag function - First Name
  public static function mla_affiliate_first_name($affiliate_id, $registering_affiliate_id = '') {

    $user_id = affwp_get_affiliate_user_id($registering_affiliate_id);

    $user_info = get_userdata($user_id);

    $first_name = $user_info->first_name;
    //$last_name = $user_info->last_name;
    $first_name = get_user_meta($user_id, 'first_name', true);

    return $first_name;

  }

  // Parse tag function - Last Name
  public static function mla_affiliate_last_name($affiliate_id, $registering_affiliate_id = '') {

    $user_id = affwp_get_affiliate_user_id($registering_affiliate_id);

    $user_info = get_userdata($user_id);

    //$first_name = $user_info->first_name;
    $last_name = $user_info->last_name;

    //$user_meta = get_user_meta( $user_info->ID );
    //return serialize($user_meta);
    return $last_name;

  }

  // Parse tag function - Full Name
  public static function mla_affiliate_full_name($affiliate_id, $registering_affiliate_id = '') {

    //$user_id = affwp_get_affiliate_user_id( $registering_affiliate_id );

    //$user_info = get_userdata( $user_id );

    //$first_name = $user_info->first_name;
    //$last_name = $user_info->last_name;

    //return $first_name.' '.$last_name;

    return affiliate_wp()->affiliates->get_affiliate_name($registering_affiliate_id);

  }

  // Parse tag function - Contact Details
  public static function mla_affiliate_contact_details($affiliate_id, $registering_affiliate_id = '') {

    $user_id = affwp_get_affiliate_user_id($registering_affiliate_id);

    $user_info = get_userdata($user_id);

    $contact_details = array(
      'Username' => $user_info->user_login,
      'Email' => $user_info->user_email,
    );
    $contact_details = apply_filters('mla_tag_parent_contact_details', $contact_details, $user_id, $user_info);
    $output = '';
    foreach ($contact_details as $key => $value) :

      $output .= $key . ': ' . $value . '<br>';

    endforeach;

    return $output;
  }

  // Parse tag function - Parent First Name
  public static function mla_parent_first_name($affiliate_id, $registering_affiliate_id = '') {

    $affiliate = new AffiliateWP_MLA_Affiliate($registering_affiliate_id);
    $parent_id = $affiliate->get_parent_affiliate_id();

    if ( !empty($parent_id) ) :

      $user_id = affwp_get_affiliate_user_id($parent_id);

      $user_info = get_userdata($user_id);

      $first_name = $user_info->first_name;
      //$last_name = $user_info->last_name;

      return $first_name;

    endif;

  }

  // Parse tag function - Parent Last Name
  public static function mla_parent_last_name($affiliate_id, $registering_affiliate_id = '') {

    $affiliate = new AffiliateWP_MLA_Affiliate($registering_affiliate_id);
    $parent_id = $affiliate->get_parent_affiliate_id();

    if ( !empty($parent_id) ) :

      $user_id = affwp_get_affiliate_user_id($parent_id);

      $user_info = get_userdata($user_id);

      //$first_name = $user_info->first_name;
      $last_name = $user_info->last_name;

      return $last_name;

    endif;

  }

  // Parse tag function - Parent Full Name
  public static function mla_parent_full_name($affiliate_id, $registering_affiliate_id = '') {

    $affiliate = new AffiliateWP_MLA_Affiliate($registering_affiliate_id);
    $parent_id = $affiliate->get_parent_affiliate_id();

    if ( !empty($parent_id) ) :

      $user_id = affwp_get_affiliate_user_id($parent_id);

      $user_info = get_userdata($user_id);

      $first_name = $user_info->first_name;
      $last_name = $user_info->last_name;

      return $first_name . ' ' . $last_name;

    endif;
  }

  // Parse tag function - Parent Contact Details
  public static function mla_parent_contact_details($affiliate_id, $registering_affiliate_id = '') {

    $affiliate = new AffiliateWP_MLA_Affiliate($registering_affiliate_id);
    $parent_id = $affiliate->get_parent_affiliate_id();

    if ( !empty($parent_id) ) :

      $user_id = affwp_get_affiliate_user_id($parent_id);

      $user_info = get_userdata($user_id);

      $contact_details = array(
        'Username' => $user_info->user_login,
        'Email' => $user_info->user_email,
      );
      $contact_details = apply_filters('mla_tag_parent_contact_details', $contact_details, $user_id, $user_info);
      $output = '';
      foreach ($contact_details as $key => $value) :

        $output .= $key . ': ' . $value . '<br>';

      endforeach;

      return $output;

    endif;
  }

  // Parse tag function - Team Leader First Name
  public static function mla_team_leader_first_name($affiliate_id, $registering_affiliate_id = '') {

    $tl = new AffiliateWP_MLA_Team_Leader;
    $team_leader_aff_id = $tl->get_affiliates_team_leader($registering_affiliate_id);

    if ( !empty($team_leader_aff_id) ) :

      $user_id = affwp_get_affiliate_user_id($team_leader_aff_id);

      $user_info = get_userdata($user_id);

      $first_name = $user_info->first_name;
      //$last_name = $user_info->last_name;

      return $first_name;

    endif;

  }

  // Parse tag function - Team Leader Last Name
  public static function mla_team_leader_last_name($affiliate_id, $registering_affiliate_id = '') {

    $tl = new AffiliateWP_MLA_Team_Leader;
    $team_leader_aff_id = $tl->get_affiliates_team_leader($registering_affiliate_id);

    if ( !empty($team_leader_aff_id) ) :

      $user_id = affwp_get_affiliate_user_id($team_leader_aff_id);

      $user_info = get_userdata($user_id);

      //$first_name = $user_info->first_name;
      $last_name = $user_info->last_name;

      return $last_name;

    endif;

  }

  // Parse tag function - Team Leader Full Name
  public static function mla_team_leader_full_name($affiliate_id, $registering_affiliate_id = '') {

    $tl = new AffiliateWP_MLA_Team_Leader;
    $team_leader_aff_id = $tl->get_affiliates_team_leader($registering_affiliate_id);

    if ( !empty($team_leader_aff_id) ) :

      $user_id = affwp_get_affiliate_user_id($team_leader_aff_id);

      $user_info = get_userdata($user_id);

      $first_name = $user_info->first_name;
      $last_name = $user_info->last_name;

      return $first_name . ' ' . $last_name;

    endif;
  }

  // Parse tag function - Team Leader Contact Details
  public static function mla_team_leader_contact_details($affiliate_id, $registering_affiliate_id = '') {

    $tl = new AffiliateWP_MLA_Team_Leader;
    $team_leader_aff_id = $tl->get_affiliates_team_leader($registering_affiliate_id);

    if ( !empty($team_leader_aff_id) ) :

      $user_id = affwp_get_affiliate_user_id($team_leader_aff_id);

      $user_info = get_userdata($user_id);

      $contact_details = array(
        'Username' => $user_info->user_login,
        'Email' => $user_info->user_email,
      );
      $contact_details = apply_filters('mla_tag_parent_contact_details', $contact_details, $user_id, $user_info);
      $output = '';
      foreach ($contact_details as $key => $value) :

        $output .= $key . ': ' . $value . '<br>';

      endforeach;

      return $output;

    endif;
  }

  // Allows the MLA notifications to be sent regardless of the main AffiliateWP checkbox setting 
  public function affiliatewp_switch($notification, $section) {

    //$affiliatewp_switch = affiliate_wp()->settings->get( 'disable_all_emails', false );
    $this->affiliatewp_switch = apply_filters('mla_notifications_affwp_switch', false, $notification);

    if ( $section == 'before' ) :

      add_action('affwp_disable_all_emails', array($this, 'mla_notifications_affwp_switch'));

    endif;

    if ( $section == 'after' ) :

      remove_action('affwp_disable_all_emails', array($this, 'mla_notifications_affwp_switch'));

    endif;

  }

  // Filter to enable the AffiliateWP emails
  public function mla_notifications_affwp_switch($switch) {

    return $this->affiliatewp_switch;

  }

  // The notifications
  public function registration_notifications($affiliate_id) {

    // Add the registering affiliate
    $this->affwp_notify_new_mla_affiliate($affiliate_id); // The Affiliate
    $this->affwp_notify_new_sub_affiliate($affiliate_id); // The Referrer
    $this->affwp_notify_new_team_affiliate($affiliate_id); // The Team Leader
    $this->affwp_notify_new_steam_affiliate($affiliate_id); // The Super Team Leader

  }

  // The notifications
  public function referral_notifications($generated_referral_ids, $affiliate_id) {

    // Add the registering affiliate
    $this->affwp_notify_new_team_referral($affiliate_id); // The Team Leader
    $this->affwp_notify_new_steam_referral($affiliate_id); // The Super Team Leader

  }

  // New sub affiliate registration
  public function affwp_notify_new_mla_affiliate($affiliate_id) {

    $enabled = affiliate_wp()->settings->get('mla_reg_affiliate_enable', false);

    if ( $enabled == 1 ) :
      $this->affiliatewp_switch('affwp_notify_new_mla_affiliate', 'before');

      if ( !empty($affiliate_id) ) :
        //$user_id = affwp_get_affiliate_user_id( $affiliate_id );

        $emails = new Affiliate_WP_Emails;
        $emails->__set('affiliate_id', $affiliate_id);

        // Referral will be registering affiliate so the parameter can be passed to the tag function
        $emails->__set('referral', $affiliate_id);

        $email = affwp_get_affiliate_email($affiliate_id);
        $subject = affiliate_wp()->settings->get('mla_reg_affiliate_subject', __('', 'affiliatewp-multi-level-affiliates'));
        $message = affiliate_wp()->settings->get('mla_reg_affiliate_email', false);
        $emails->send($email, $subject, $message);

      endif;

      $this->affiliatewp_switch('affwp_notify_new_mla_affiliate', 'after');
    endif;

  }

  // New sub affiliate (parent) registration
  public function affwp_notify_new_sub_affiliate($affiliate_id) {

    $enabled = affiliate_wp()->settings->get('mla_reg_parent_enable', false);

    if ( $enabled == 1 ) :
      $this->affiliatewp_switch('affwp_notify_new_sub_affiliate', 'before');

      if ( !empty($affiliate_id) ) :

        $affiliate = new AffiliateWP_MLA_Affiliate($affiliate_id);
        $parent_id = $affiliate->get_parent_affiliate_id();

        if ( !empty($parent_id) ) :

          $emails = new Affiliate_WP_Emails;
          $emails->__set('affiliate_id', $parent_id);

          // Referral will be registering affiliate so the parameter can be passed to the tag function
          $emails->__set('referral', $affiliate_id);

          $email = affwp_get_affiliate_email($parent_id);
          $subject = affiliate_wp()->settings->get('mla_reg_parent_subject', __('', 'affiliatewp-multi-level-affiliates'));
          $message = affiliate_wp()->settings->get('mla_reg_parent_email', false);
          $emails->send($email, $subject, $message);

        endif;

      endif;

      $this->affiliatewp_switch('affwp_notify_new_sub_affiliate', 'after');
    endif;

  }

  // New sub affiliate (team leader) registration
  public function affwp_notify_new_team_affiliate($affiliate_id) {

    $enabled = affiliate_wp()->settings->get('mla_reg_tl_enable', false);

    if ( $enabled == 1 ) :
      $this->affiliatewp_switch('affwp_notify_new_team_affiliate', 'before');

      if ( !empty($affiliate_id) ) :
        //$user_id = affwp_get_affiliate_user_id( $affiliate_id );

        $tl = new AffiliateWP_MLA_Team_Leader;
        $team_leader_aff_id = $tl->get_affiliates_team_leader($affiliate_id);

        if ( !empty($team_leader_aff_id) ) :

          $emails = new Affiliate_WP_Emails;
          $emails->__set('affiliate_id', $team_leader_aff_id);

          // Referral will be registering affiliate so the parameter can be passed to the tag function
          $emails->__set('referral', $affiliate_id);

          $email = affwp_get_affiliate_email($team_leader_aff_id);
          $subject = affiliate_wp()->settings->get('mla_reg_tl_subject', __('', 'affiliatewp-multi-level-affiliates'));
          $message = affiliate_wp()->settings->get('mla_reg_tl_email', false);
          $emails->send($email, $subject, $message);

        endif;

      endif;

      $this->affiliatewp_switch('affwp_notify_new_team_affiliate', 'after');
    endif;

  }

  // New sub affiliate (super team leader) registration
  public function affwp_notify_new_steam_affiliate($affiliate_id) {

    $enabled = affiliate_wp()->settings->get('mla_reg_stl_enable', false);

    if ( $enabled == 1 ) :
      $this->affiliatewp_switch('affwp_notify_new_steam_affiliate', 'before');

      if ( !empty($affiliate_id) ) :
        //$user_id = affwp_get_affiliate_user_id( $affiliate_id );

        $tl = new AffiliateWP_MLA_Team_Leader;
        $steam_leader_aff_id = $tl->get_affiliates_super_team_leader($affiliate_id);

        if ( !empty($steam_leader_aff_id) ) :

          $emails = new Affiliate_WP_Emails;
          $emails->__set('affiliate_id', $steam_leader_aff_id);

          // Referral will be registering affiliate so the parameter can be passed to the tag function
          $emails->__set('referral', $affiliate_id);

          $email = affwp_get_affiliate_email($steam_leader_aff_id);
          $subject = affiliate_wp()->settings->get('mla_reg_stl_subject', __('', 'affiliatewp-multi-level-affiliates'));
          $message = affiliate_wp()->settings->get('mla_reg_stl_email', false);
          $emails->send($email, $subject, $message);

        endif;

      endif;

      $this->affiliatewp_switch('affwp_notify_new_steam_affiliate', 'after');
    endif;

  }

  // Parent referral not required as it's just the standard AffiliateWP referral notification

  // New sub affiliate (team leader) referral
  public function affwp_notify_new_team_referral($affiliate_id) {

    $enabled = affiliate_wp()->settings->get('mla_ref_tl_enable', false);

    if ( $enabled == 1 ) :
      $this->affiliatewp_switch('affwp_notify_new_team_referral', 'before');

      if ( !empty($affiliate_id) ) :
        //$user_id = affwp_get_affiliate_user_id( $affiliate_id );

        $tl = new AffiliateWP_MLA_Team_Leader;
        $team_leader_aff_id = $tl->get_affiliates_team_leader($affiliate_id);

        if ( !empty($team_leader_aff_id) ) :

          $emails = new Affiliate_WP_Emails;
          $emails->__set('affiliate_id', $team_leader_aff_id);

          // Referral will be registering affiliate so the parameter can be passed to the tag function
          $emails->__set('referral', $affiliate_id);

          $email = affwp_get_affiliate_email($team_leader_aff_id);
          $subject = affiliate_wp()->settings->get('mla_ref_tl_subject', __('', 'affiliatewp-multi-level-affiliates'));
          $message = affiliate_wp()->settings->get('mla_ref_tl_email', false);
          $emails->send($email, $subject, $message);

        endif;

      endif;

      $this->affiliatewp_switch('affwp_notify_new_team_referral', 'after');
    endif;

  }

  // New sub affiliate (super team leader) referral
  public function affwp_notify_new_steam_referral($affiliate_id) {

    $enabled = affiliate_wp()->settings->get('mla_ref_stl_enable', false);

    if ( $enabled == 1 ) :
      $this->affiliatewp_switch('affwp_notify_new_steam_referral', 'before');

      if ( !empty($affiliate_id) ) :
        //$user_id = affwp_get_affiliate_user_id( $affiliate_id );

        $tl = new AffiliateWP_MLA_Team_Leader;
        $steam_leader_aff_id = $tl->get_affiliates_super_team_leader($affiliate_id);

        if ( !empty($steam_leader_aff_id) ) :

          $emails = new Affiliate_WP_Emails;
          $emails->__set('affiliate_id', $steam_leader_aff_id);

          // Referral will be registering affiliate so the parameter can be passed to the tag function
          $emails->__set('referral', $affiliate_id);

          $email = affwp_get_affiliate_email($steam_leader_aff_id);
          $subject = affiliate_wp()->settings->get('mla_ref_stl_subject', __('', 'affiliatewp-multi-level-affiliates'));
          $message = affiliate_wp()->settings->get('mla_ref_stl_email', false);
          $emails->send($email, $subject, $message);

        endif;

      endif;

      $this->affiliatewp_switch('affwp_notify_new_steam_referral', 'after');
    endif;

  }

}

?>