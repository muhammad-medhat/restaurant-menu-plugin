<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Flush rewrite rules when the plugin is activated.
 */
function rm_activate_plugin() {

	rm_register_menu_item_cpt();
	rm_register_menu_category_taxonomy();

	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'rm_activate_plugin' );


/**
 * Flush rewrite rules when the plugin is deactivated.
 */
function rm_deactivate_plugin() {

	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'rm_deactivate_plugin' );