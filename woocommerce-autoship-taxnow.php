<?php
/*
Plugin Name: WC Auto-Ship TaxNOW
Plugin URI: http://wooautoship.com
Description: Integrate WC Auto-Ship with TaxNOW for WooCommerce
Version: 1.0
Author: Patterns in the Cloud
Author URI: http://patternsinthecloud.com
License: Single-site
*/

define( 'WC_Autoship_TaxNOW_Version', '1.0.0' );

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active( 'woocommerce-autoship/woocommerce-autoship.php' ) && is_plugin_active( 'taxnow_woo/taxnow.php' ) ) {
	
	function wc_autoship_taxnow_install() {
	}
	register_activation_hook( __FILE__, 'wc_autoship_taxnow_install' );
	
	function wc_autoship_taxnow_deactivate() {
	
	}
	register_deactivation_hook( __FILE__, 'wc_autoship_taxnow_deactivate' );
	
	function wc_autoship_taxnow_uninstall() {

	}
	register_uninstall_hook( __FILE__, 'wc_autoship_taxnow_uninstall' );
}
