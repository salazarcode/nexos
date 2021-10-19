<?php

class AffiliateWP_MLA_Template_Loader extends Gamajo_Template_Loader_CS {
  
  protected $filter_prefix;

  protected $theme_template_directory;

  protected $plugin_directory;

  protected $plugin_template_directory;
  
  public function __construct( $vars = array() ) {
	  
	  	$sub_directory = ( isset($vars['sub_directory']) && !empty($vars['sub_directory']) ) ? '/'.$vars['sub_directory'] : '';
		
		/*
		if (defined('AFFWP_MLA_PLUGIN_CONFIG')) {
			$this->plugin_config = unserialize(AFFWP_MLA_PLUGIN_CONFIG);
		}else{
			$this->plugin_config = array();
		}
		*/
		$this->plugin_config = affiliate_wp_mla()->plugin_config;

		$this->filter_prefix = 'affiliatewp_mla';
		$this->theme_template_directory = 'affiliatewp-mla'.$sub_directory;
		$this->plugin_directory = $this->plugin_config['plugin_dir'];
		$this->plugin_template_directory = 'templates'.$sub_directory;
		
	}
	
	public function get_template_object( $template_part ) {
		
		$template_path = $this->get_template_part( $template_part, '', false );
		
		ob_start();
		
		load_template( $template_path );
		
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
		
	}
	
}

?>