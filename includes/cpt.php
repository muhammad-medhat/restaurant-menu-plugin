<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}/**
 * Register Menu Item CPT.
 */
function rm_register_menu_item_cpt() {

	$labels = array(
		'name'                  => __( 'Menu Items', 'restaurant-menu' ),
		'singular_name'         => __( 'Menu Item', 'restaurant-menu' ),
		'menu_name'             => __( 'Restaurant Menu', 'restaurant-menu' ),
		'name_admin_bar'        => __( 'Menu Item', 'restaurant-menu' ),
		'add_new'               => __( 'Add New', 'restaurant-menu' ),
		'add_new_item'          => __( 'Add New Menu Item', 'restaurant-menu' ),
		'new_item'              => __( 'New Menu Item', 'restaurant-menu' ),
		'edit_item'             => __( 'Edit Menu Item', 'restaurant-menu' ),
		'view_item'             => __( 'View Menu Item', 'restaurant-menu' ),
		'all_items'             => __( 'All Menu Items', 'restaurant-menu' ),
		'search_items'          => __( 'Search Menu Items', 'restaurant-menu' ),
		'not_found'             => __( 'No menu items found.', 'restaurant-menu' ),
		'not_found_in_trash'    => __( 'No menu items found in Trash.', 'restaurant-menu' ),
		'featured_image'        => __( 'Menu Item Image', 'restaurant-menu' ),
		'set_featured_image'    => __( 'Set menu item image', 'restaurant-menu' ),
		'remove_featured_image' => __( 'Remove menu item image', 'restaurant-menu' ),
		'use_featured_image'    => __( 'Use as menu item image', 'restaurant-menu' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_admin_bar'  => true,
		'show_in_rest'       => true,

		/**
		 * We will create our own frontend template later.
		 */
		'has_archive'        => false,

		'rewrite'            => array(
			'slug'       => 'menu-item',
			'with_front' => false,
		),

		/**
		 * We will use the title for now.
		 * ACF will later provide Arabic/English fields.
		 */
		'supports'   => array(
            'title',
			// 'editor',
			// 'thumbnail',
			'page-attributes',
		),

		/**
		 * Menu items can be organized using our custom category taxonomy.
		 */
		'taxonomies'         => array(
			RM_MENU_CATEGORY_TAX,
		),

		'menu_position'      => 20,
		'menu_icon'          => 'dashicons-food',

		'capability_type'    => 'post',
		'map_meta_cap'       => true,
	);

	register_post_type( RM_MENU_ITEM_CPT, $args );
}

add_action( 'init', 'rm_register_menu_item_cpt' );


/**
 * Register Menu Category taxonomy.
 */
function rm_register_menu_category_taxonomy() {

	$labels = array(
		'name'              => __( 'Menu Categories', 'restaurant-menu' ),
		'singular_name'     => __( 'Menu Category', 'restaurant-menu' ),
		'search_items'      => __( 'Search Menu Categories', 'restaurant-menu' ),
		'all_items'         => __( 'All Menu Categories', 'restaurant-menu' ),
		'parent_item'       => __( 'Parent Menu Category', 'restaurant-menu' ),
		'parent_item_colon' => __( 'Parent Menu Category:', 'restaurant-menu' ),
		'edit_item'         => __( 'Edit Menu Category', 'restaurant-menu' ),
		'update_item'       => __( 'Update Menu Category', 'restaurant-menu' ),
		'add_new_item'      => __( 'Add New Menu Category', 'restaurant-menu' ),
		'new_item_name'     => __( 'New Menu Category Name', 'restaurant-menu' ),
		'menu_name'        => __( 'Categories', 'restaurant-menu' ),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,

		/**
		 * Hierarchical = true makes it behave like WordPress Categories.
		 */
		'hierarchical'      => true,

		'rewrite'           => array(
			'slug'       => 'menu-category',
			'with_front' => false,
		),
	);

	register_taxonomy(
		RM_MENU_CATEGORY_TAX,
		array( RM_MENU_ITEM_CPT ),
		$args
	);
}

add_action( 'init', 'rm_register_menu_category_taxonomy' );