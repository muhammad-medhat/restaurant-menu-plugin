<?php
/**
 * Quick Edit support for Restaurant Menu Categories.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add a Quick Edit link to the category row actions.
 */
function rm_category_quick_edit_link( $actions, $tag ) {

    if (
        ! $tag instanceof WP_Term ||
        RM_MENU_CATEGORY_TAX !== $tag->taxonomy
    ) {
        return $actions;
    }

    $url = wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'rm_edit_category',
                'tag_ID' => $tag->term_id,
            ),
            admin_url( 'term.php' )
        ),
        'rm_edit_category_' . $tag->term_id
    );

    $actions['rm_quick_edit'] = sprintf(
        '<a href="%s" class="rm-category-quick-edit" data-term-id="%d">%s</a>',
        esc_url( $url ),
        (int) $tag->term_id,
        esc_html__( 'Quick Edit', 'restaurant-menu' )
    );

    return $actions;
}
add_filter( RM_MENU_CATEGORY_TAX . '_row_actions','rm_category_quick_edit_link',  10,  2);