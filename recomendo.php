<?php
/**
* Plugin Name: Recomendo
* Plugin URI: https://www.recomendo.ai
* Description: Make your website smart with Artificial Intelligence recommendations.
* Author: Recomendo
* Version: 1.0.4
* Requires at least: 4.7
* Tested up to: 4.9.8
* WC requires at least: 3.0
* WC tested up to: 3.5
* License: GPLv2
**/


//Security to limit direcct access to the plugin file
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Load Recomendo admin page
require_once( plugin_dir_path( __FILE__ ) . 'recomendo-admin.php');
// Load Recomendo plugin
require_once( plugin_dir_path( __FILE__ ) . 'recomendo-plugin.php');
// Load Recomendo client
require_once( plugin_dir_path( __FILE__ ) . 'recomendo-client.php');
// Load Plug-In widget file
require_once( plugin_dir_path( __FILE__ ) . 'recomendo-widget.php');

// Load libraries
require_once plugin_dir_path( __FILE__ ) . 'inc/recomendo-background-user-copy.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/recomendo-background-item-copy.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/recomendo-background-order-copy.php';


if ( class_exists ( 'Recomendo_Admin') ) {
    // activation
    register_activation_hook( __FILE__, array( 'Recomendo_Admin', 'activate' ) );
    // deactivation
    register_activation_hook( __FILE__, array( 'Recomendo_Admin', 'deactivate' ) );

    Recomendo_Admin::register();

    // Check Recomendo is Authorized and Configured to Continue
    if ( Recomendo_Admin::is_configured() ) {
		$options = get_option( 'recomendo_options' );
        if ( class_exists( 'Recomendo_Plugin' ) ) {
            // launch the plugin
            $recomendo = new Recomendo_Plugin( $options );

		}
	}
}
