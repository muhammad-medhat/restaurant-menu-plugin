<?php
/**
 * Category image for the Restaurant Menu plugin.
 * Requires RM_MENU_CATEGORY_TAX and RM_URL to be defined by the main plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add an Image column to the Menu Categories admin table.
 */
function rm_add_category_image_column( $columns ) {
    $new_columns = array();

    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;

        // Place the image after the category name.
        if ( 'cb' === $key ) {
            $new_columns['rm_category_image'] = 'Image';
        }
    }
    return $new_columns;
}


add_filter( 'manage_edit-' . RM_MENU_CATEGORY_TAX . '_columns', 'rm_add_category_image_column');


/**
 * Display the category PNG in the Image column.
 */
function rm_render_category_image_column( $content, $column_name, $term_id ) {
    if ( 'rm_category_image' !== $column_name ) {
        return $content;
    }

    $img_url = get_field( 'category_image', RM_MENU_CATEGORY_TAX . '_' . $term_id);


    if (
        ! isset( $img_url) ||
        empty( $img_url )
    ) {
        return '<span style="color:#999;">—</span>';
    }

    
    return sprintf(
        '<img src="%s" alt="" width="36" height="36" style="display:block;object-fit:contain;" />',
        esc_url( $img_url )
    );
}

add_filter('manage_' . RM_MENU_CATEGORY_TAX . '_custom_column', 'rm_render_category_image_column', 10, 3);