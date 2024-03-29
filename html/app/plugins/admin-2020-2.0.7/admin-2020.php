<?php
/*
Plugin Name: Admin 2020 Pro
Plugin URI: https://admintwentytwenty.com
Description: Powerful WordPress admin extension with a streamlined dashboard, Google Analytics & WooCommerce Integration, intuitive media library, dark mode and much more.
Version: 2.0.7
Author: Admin 2020
Text Domain: admin2020
Domain Path: /admin/languages
Author URI: https://admintwentytwenty.com
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

////PRODUCT ID
$productid = "5ebc6198701f5d0a0812c618";
////VERSION
$version = '2.0.7'; 
////NAME
$pluginName = 'Admin 2020 Pro';
///PATH
$plugin_path = plugin_dir_url( __FILE__ );

require plugin_dir_path( __FILE__ ) . 'admin/inlcudes/class-admin-2020.php';

$plugin = new Admin_2020($productid,$version,$pluginName,$plugin_path);
$plugin->run();

/// SHOW ERRORS
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
