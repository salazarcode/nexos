<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class AffiliateWP_Multi_Level_Affiliates_Activation {
    public $plugin_name, $plugin_path, $plugin_file, $plugin_dependencies, $dependency_issues;
 
    public function __construct( $activation_config ) {
		
		// Set plugin directory
		$this->plugin_name = $activation_config['plugin_name'];
        
        // Set plugin directory
        $plugin_path = array_filter( explode( '/', $activation_config['plugin_path'] ) );
        $this->plugin_path = end( $plugin_path );
		
        // Set plugin file
        $this->plugin_file = $activation_config['plugin_file'];
		
		// Set plugin dependencies
		$this->plugin_dependencies = $activation_config['plugin_dependencies'];
		
		// Set dependency issues
		$this->dependency_issues = array();
        
    }
	
	// Check all dependencies
	public function check_dependencies() {

        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugins = get_plugins();
						
		foreach ($this->plugin_dependencies as $class => $data ) {

					//if(file_exists( ABSPATH . 'wp-content/plugins/' . $data['plugin_folder_file'] )) {
					if(file_exists( WP_CONTENT_DIR . '/plugins/' . $data['plugin_folder_file'] )) {	
			
							if(!is_plugin_active($data['plugin_folder_file'])) {
								$this->set_dependency_issue($data['name'], $class, $data['url'], 'activation');
							}
							
					} else {
						$this->set_dependency_issue($data['name'], $class, $data['url'], 'install');
					}
				
		}

		
		if(count($this->dependency_issues) >=1) {
			$this->process();
		} else {
			return TRUE;
		}
	}
	
	// Set dependency issues
	public function set_dependency_issue($plugin_name, $plugin_class, $plugin_url, $issue_type) {
		
		if($issue_type == 'activation') {
			$message = 'Please activate it to continue.'; 
		} elseif($issue_type == 'install') {
			$message =  'Please install it to continue.'; 
		}
		
		$this->dependency_issues[$plugin_class] = '<div class="error"><p> \'' .  $this->plugin_name .  '\' requires '. '<a href="'.$plugin_url.'" title="'.$plugin_name.'" target="_blank">'.$plugin_name.' </a>'.$message.'</p></div>';	
		
	}
	
  	// Process any issues
    public function process() {
        // We need plugin.php!
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
        // Deactivate this plugin
       //deactivate_plugins( $this->plugin_path . '/' . $this->plugin_file );
	   
        unset( $_GET['activate'] ); // required ?
        // Display notice
        add_action( 'admin_notices', array( $this, 'missing_affiliatewp_notice' ) );
    }

	// Display the notifications
    public function missing_affiliatewp_notice() {
        foreach($this->dependency_issues as $key => $message) {
			echo $message;
		}
    }
	
}