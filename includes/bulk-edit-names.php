<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add "Bulk Edit Names" to the Menu Items bulk actions dropdown.
 */
add_filter(
    'bulk_actions-edit-' . RM_MENU_ITEM_CPT,
    'rm_add_bulk_edit_names_action'
);

function rm_add_bulk_edit_names_action( $actions ) {
    $actions['rm_bulk_edit_names'] = __( 'Bulk Edit Names', 'restaurant-menu' );

    return $actions;
}

/**
 * Handle the selected bulk action and open the editing page.
 */
add_filter(
    'handle_bulk_actions-edit-' . RM_MENU_ITEM_CPT,
    'rm_handle_bulk_edit_names_action',
    10,
    3
);

function rm_handle_bulk_edit_names_action1( $redirect_url, $action, $post_ids ) {
    if ( 'rm_bulk_edit_names' !== $action ) {
        return $redirect_url;
    }

    $post_ids = array_values(
        array_unique(
            array_filter(
                array_map( 'absint', (array) $post_ids )
            )
        )
    );

    $post_ids = array_filter(
        $post_ids,
        function ( $post_id ) {
            return RM_MENU_ITEM_CPT === get_post_type( $post_id )
                && current_user_can( 'edit_post', $post_id );
        }
    );

    if ( empty( $post_ids ) ) {
        return add_query_arg(
            'rm_bulk_names_error',
            'no_items',
            $redirect_url
        );
    }

    $url = add_query_arg(
        array(
            'page'     => 'rm-bulk-edit-names',
            'post_ids' => implode( ',', $post_ids ),
        ),
        admin_url( 'admin.php' )
    );

    return wp_nonce_url( $url, 'rm_open_bulk_edit_names' );
}

function rm_handle_bulk_edit_names_action( $redirect_url, $action, $post_ids ) {
    if ( 'rm_bulk_edit_names' !== $action ) {
        return $redirect_url;
    }

    $post_ids = array_values(
        array_unique(
            array_filter(
                array_map( 'absint', (array) $post_ids )
            )
        )
    );

    $post_ids = array_filter(
        $post_ids,
        function ( $post_id ) {
            return RM_MENU_ITEM_CPT === get_post_type( $post_id )
                && current_user_can( 'edit_post', $post_id );
        }
    );

    if ( empty( $post_ids ) ) {
        return add_query_arg(
            'rm_bulk_names_error',
            'no_items',
            $redirect_url
        );
    }

    $url = add_query_arg(
        array(
            'page'     => 'rm-bulk-edit-names',
            'post_ids' => implode( ',', $post_ids ),
        ),
        admin_url( 'admin.php' )
    );

    // Add the nonce without HTML-escaping the URL.
    $url = add_query_arg(
        '_wpnonce',
        wp_create_nonce( 'rm_open_bulk_edit_names' ),
        $url
    );

    return $url;
}


/**
 * Register the bulk editing page under Menu Items.
 */
add_action( 'admin_menu', 'rm_register_bulk_edit_names_page' );

function rm_register_bulk_edit_names_page() {
    add_submenu_page(
        RM_MENU_SLUG,
        __( 'Bulk Edit Names', 'restaurant-menu' ),
        __( 'Bulk Edit Names', 'restaurant-menu' ),
        'edit_posts',
        'rm-bulk-edit-names',
        'rm_render_bulk_edit_names_page'
    );
}

/**
 * Render the selected items and save Arabic and English names.
 */
function rm_render_bulk_edit_names_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to edit menu items.', 'restaurant-menu' ) );
    }

    $post_ids = isset( $_GET['post_ids'] )
        ? array_values(
            array_unique(
                array_filter(
                    array_map(
                        'absint',
                        explode( ',', sanitize_text_field( wp_unslash( $_GET['post_ids'] ) ) )
                    )
                )
            )
        )
        : array();

    /*
     * Verify the nonce when opening the page from the bulk action.
     */
    if ( isset( $_GET['_wpnonce'] ) ) {
        check_admin_referer( 'rm_open_bulk_edit_names' );
    }

    $post_ids = array_values(
        array_filter(
            $post_ids,
            function ( $post_id ) {
                return RM_MENU_ITEM_CPT === get_post_type( $post_id )
                    && current_user_can( 'edit_post', $post_id );
            }
        )
    );

    /*
     * Save submitted names.
     */
    if (
        isset( $_POST['rm_save_bulk_names'] )
        && '1' === sanitize_text_field( wp_unslash( $_POST['rm_save_bulk_names'] ) )
    ) {
        check_admin_referer( 'rm_save_bulk_names_action', 'rm_bulk_names_nonce' );

        $submitted_ids = isset( $_POST['item_ids'] )
            ? array_values(
                array_unique(
                    array_filter(
                        array_map( 'absint', (array) wp_unslash( $_POST['item_ids'] ) )
                    )
                )
            )
            : array();

        $names_ar = isset( $_POST['name_ar'] ) && is_array( $_POST['name_ar'] )
            ? wp_unslash( $_POST['name_ar'] )
            : array();

        $names_en = isset( $_POST['name_en'] ) && is_array( $_POST['name_en'] )
            ? wp_unslash( $_POST['name_en'] )
            : array();

        $updated = 0;
        $skipped = 0;

        foreach ( $submitted_ids as $post_id ) {
            if (
                RM_MENU_ITEM_CPT !== get_post_type( $post_id )
                || ! current_user_can( 'edit_post', $post_id )
            ) {
                $skipped++;
                continue;
            }

            /*
             * Update only fields actually submitted.
             * Empty values are allowed to clear a name.
             */
            if ( array_key_exists( $post_id, $names_ar ) ) {
                $value_ar = sanitize_text_field( $names_ar[ $post_id ] );

                if ( function_exists( 'update_field' ) ) {
                    update_field( 'name_ar', $value_ar, $post_id );
                } else {
                    update_post_meta( $post_id, 'name_ar', $value_ar );
                }
            }

            if ( array_key_exists( $post_id, $names_en ) ) {
                $value_en = sanitize_text_field( $names_en[ $post_id ] );

                if ( function_exists( 'update_field' ) ) {
                    update_field( 'name_en', $value_en, $post_id );
                } else {
                    update_post_meta( $post_id, 'name_en', $value_en );
                }
            }

            $updated++;
        }

        $return_url = add_query_arg(
            array(
                'page'       => 'rm-bulk-edit-names',
                'post_ids'   => implode( ',', $submitted_ids ),
                'rm_updated' => $updated,
                'rm_skipped' => $skipped,
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $return_url );
        exit;
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Bulk Edit Menu Names', 'restaurant-menu' ) . '</h1>';

    if ( isset( $_GET['rm_updated'] ) ) {
        $updated = absint( $_GET['rm_updated'] );
        $skipped = isset( $_GET['rm_skipped'] ) ? absint( $_GET['rm_skipped'] ) : 0;

        echo '<div class="notice notice-success is-dismissible"><p>';
        printf(
            esc_html__( 'Updated %1$d menu item(s). Skipped: %2$d.', 'restaurant-menu' ),
            $updated,
            $skipped
        );
        echo '</p></div>';
    }

    if ( empty( $post_ids ) ) {
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__( 'No editable menu items were selected. Return to Menu Items, select items, and choose Bulk Edit Names.', 'restaurant-menu' );
        echo '</p></div>';
        echo '<p><a class="button button-primary" href="' .
            esc_url( admin_url( RM_MENU_SLUG ) ) . '">' .
            esc_html__( 'Back to Menu Items', 'restaurant-menu' ) .
            '</a></p>';
        echo '</div>';
        return;
    }

    echo '<p>' . esc_html__( 'Edit the Arabic and English names below. Only these two fields will be changed.', 'restaurant-menu' ) . '</p>';

    echo '<form method="post">';
    wp_nonce_field( 'rm_save_bulk_names_action', 'rm_bulk_names_nonce' );
    echo '<input type="hidden" name="rm_save_bulk_names" value="1">';

    echo '<table class="widefat striped">';
    echo '<thead><tr>';
    echo '<th>' . esc_html__( 'Menu Item', 'restaurant-menu' ) . '</th>';
    echo '<th>' . esc_html__( 'Arabic Name', 'restaurant-menu' ) . '</th>';
    echo '<th>' . esc_html__( 'English Name', 'restaurant-menu' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $post_ids as $post_id ) {
        $name_ar = function_exists( 'get_field' )
            ? get_field( 'name_ar', $post_id )
            : get_post_meta( $post_id, 'name_ar', true );

        $name_en = function_exists( 'get_field' )
            ? get_field( 'name_en', $post_id )
            : get_post_meta( $post_id, 'name_en', true );

        echo '<tr>';

        echo '<td>';
        echo '<strong>' . esc_html( get_the_title( $post_id ) ) . '</strong>';
        echo '<input type="hidden" name="item_ids[]" value="' . esc_attr( $post_id ) . '">';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" class="large-text" dir="rtl" name="name_ar[' . esc_attr( $post_id ) . ']" value="' . esc_attr( $name_ar ) . '">';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" class="large-text" dir="ltr" name="name_en[' . esc_attr( $post_id ) . ']" value="' . esc_attr( $name_en ) . '">';
        echo '</td>';

        echo '</tr>';
    }

    echo '</tbody></table>';

    echo '<p class="submit">';
    echo '<button type="submit" class="button button-primary">';
    echo esc_html__( 'Save Name Changes', 'restaurant-menu' );
    echo '</button> ';
    echo '<a class="button" href="' . esc_url( admin_url( RM_MENU_SLUG ) ) . '">';
    echo esc_html__( 'Cancel', 'restaurant-menu' );
    echo '</a>';
    echo '</p>';

    echo '</form>';
    echo '</div>';
}