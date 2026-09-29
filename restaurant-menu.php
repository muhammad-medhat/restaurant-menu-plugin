<?php
/**
 * Plugin Name: Restaurant Menu
 * Plugin URI:  https://example.com/
 * Description: Custom post type and taxonomy for managing a bilingual restaurant menu.
 * Version:     1.0.0
 * Author:      Muhammad Medhat
 * License:     GPL-2.0-or-later
 * Text Domain: restaurant-menu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// die;

// constants
define( 'RM_PATH', plugin_dir_path(__FILE__) );
define( 'RM_MENU_ITEM_CPT', 'rm_menu_item' );
define( 'RM_MENU_CATEGORY_TAX', 'rm_menu_category' );
define( 'RM_MENU_SLUG', 'edit.php?post_type=' . RM_MENU_ITEM_CPT);

/**
 * Includes
 */

require_once RM_PATH .'/includes/cpt.php';
require_once RM_PATH .'/includes/activation.php';


/**
 * Restaurant Menu Shortcode.
 */
function rm_render_menu_shortcode() {

	ob_start();

	$template = plugin_dir_path( __FILE__ ) . 'templates/menu-page.php';

	if ( file_exists( $template ) ) {
		include $template;
	} else {
		echo '<p>' . esc_html__( 'Menu template not found.', 'restaurant-menu' ) . '</p>';
	}

	return ob_get_clean();
}

add_shortcode( 'restaurant_menu', 'rm_render_menu_shortcode' );

/**
 * Enqueue frontend assets.
 */
function rm_enqueue_frontend_assets() {

	if ( ! is_page( 'menu' ) ) {
		return;
	}

	wp_enqueue_style(
		'restaurant-menu',
		plugin_dir_url( __FILE__ ) . 'assets/css/menu.css',
		array(),
		'1.0.0'
	);
}

add_action( 'wp_enqueue_scripts', 'rm_enqueue_frontend_assets' );


//includes 
// require_once RM_PATH . 'includes/frontend.php';

// require_once RM_PATH . 'includes/importer.php';
require_once RM_PATH . 'includes/bulk-price-updater.php';