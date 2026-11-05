<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Configuration for the editable menu item fields.
 */
function rm_bulk_acf_fields() {
    return array(
        'slug' => array( 'label' => __( 'Slug', 'restaurant-menu' ), 'type' => 'slug', 'dir' => 'ltr' ),
        'name_ar' => array( 'label' => __( 'Arabic Name', 'restaurant-menu' ), 'type' => 'text', 'dir' => 'rtl' ),
        'name_en' => array( 'label' => __( 'English Name', 'restaurant-menu' ), 'type' => 'text', 'dir' => 'ltr' ),
        'desc_ar' => array( 'label' => __( 'Arabic Description', 'restaurant-menu' ), 'type' => 'textarea', 'dir' => 'rtl' ),
        'desc_en' => array( 'label' => __( 'English Description', 'restaurant-menu' ), 'type' => 'textarea', 'dir' => 'ltr' ),
        'item_compact' => array( 'label' => __( 'Compact Item', 'restaurant-menu' ), 'type' => 'checkbox' ),
    );
}

/**
 * Return unique, valid, editable menu item IDs.
 */
function rm_bulk_acf_editable_ids( $ids ) {
    $ids = array_unique( array_filter( array_map( 'absint', (array) $ids ) ) );

    return array_values( array_filter( $ids, function ( $id ) {
        return RM_MENU_ITEM_CPT === get_post_type( $id ) && current_user_can( 'edit_post', $id );
    } ) );
}

/**
 * Build a nonce-protected URL for a selection.
 */
function rm_bulk_acf_page_url( $ids, $extra = array() ) {
    $url = add_query_arg(
        array_merge(
            array( 'page' => 'rm-bulk-edit-names', 'post_ids' => implode( ',', $ids ) ),
            $extra
        ),
        admin_url( 'admin.php' )
    );

    return add_query_arg( '_wpnonce', wp_create_nonce( 'rm_open_bulk_edit_names' ), $url );
}

add_filter( 'bulk_actions-edit-' . RM_MENU_ITEM_CPT, 'rm_add_bulk_edit_names_action' );
function rm_add_bulk_edit_names_action( $actions ) {
    $actions['rm_bulk_edit_names'] = __( 'Bulk Edit ACF...', 'restaurant-menu' );
    return $actions;
}

add_filter( 'handle_bulk_actions-edit-' . RM_MENU_ITEM_CPT, 'rm_handle_bulk_edit_names_action', 10, 3 );
function rm_handle_bulk_edit_names_action( $redirect_url, $action, $post_ids ) {
    if ( 'rm_bulk_edit_names' !== $action ) {
        return $redirect_url;
    }

    $ids = rm_bulk_acf_editable_ids( $post_ids );
    if ( ! $ids ) {
        return add_query_arg( 'rm_bulk_names_error', 'no_items', $redirect_url );
    }

    return rm_bulk_acf_page_url( $ids );
}

add_action( 'admin_menu', 'rm_register_bulk_edit_names_page' );
function rm_register_bulk_edit_names_page() {
    add_submenu_page(
        RM_MENU_SLUG,
        __( 'Bulk Edit Menu Fields', 'restaurant-menu' ),
        __( 'Bulk Edit Menu Fields', 'restaurant-menu' ),
        'edit_posts',
        'rm-bulk-edit-names',
        'rm_render_bulk_edit_names_page'
    );
}

/**
 * Persist only configured fields for items in the nonce-protected selection.
 * Empty submitted strings intentionally clear existing values.
 */
function rm_bulk_acf_save( $selected_ids ) {
    check_admin_referer( 'rm_save_bulk_names_action', 'rm_bulk_names_nonce' );

    $submitted_ids = isset( $_POST['item_ids'] ) && is_array( $_POST['item_ids'] )
        ? array_unique( array_filter( array_map( 'absint', wp_unslash( $_POST['item_ids'] ) ) ) )
        : array();

    $allowed_ids = array_flip( $selected_ids );
    $fields      = rm_bulk_acf_fields();
    $submitted   = array();
    $updated     = 0;
    $skipped     = 0;

    foreach ( $fields as $key => $config ) {
        $submitted[ $key ] = isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] )
            ? wp_unslash( $_POST[ $key ] )
            : array();
    }

    foreach ( $submitted_ids as $post_id ) {
        if ( ! isset( $allowed_ids[ $post_id ] ) || ! current_user_can( 'edit_post', $post_id ) || RM_MENU_ITEM_CPT !== get_post_type( $post_id ) ) {
            ++$skipped;
            continue;
        }

        foreach ( $fields as $key => $config ) {
            if ( ! array_key_exists( $post_id, $submitted[ $key ] ) || ! is_scalar( $submitted[ $key ][ $post_id ] ) ) {
                continue;
            }

            $raw = (string) $submitted[ $key ][ $post_id ];
            if ( 'checkbox' === $config['type'] ) {
                $value = '1' === $raw ? 1 : 0;
            } elseif ( 'textarea' === $config['type'] ) {
                $value = sanitize_textarea_field( $raw );
            } else {
                $value = sanitize_text_field( $raw );
            }

            if ( 'slug' === $config['type'] ) {
                // Post slugs are stored in wp_posts.post_name, not ACF.
                $slug = sanitize_title( $raw );
                if ( '' !== $slug ) {
                    wp_update_post( array(
                        'ID'        => $post_id,
                        'post_name' => $slug,
                    ) );
                }
            } elseif ( function_exists( 'update_field' ) ) {
                update_field( $key, $value, $post_id );
            } else {
                update_post_meta( $post_id, $key, $value );
            }
        }

        ++$updated;
    }

    wp_safe_redirect( rm_bulk_acf_page_url( $selected_ids, array(
        'rm_updated' => $updated,
        'rm_skipped' => $skipped,
    ) ) );
    exit;
}

function rm_render_bulk_edit_names_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'You do not have permission to edit menu items.', 'restaurant-menu' ) );
    }

    $raw_ids = isset( $_GET['post_ids'] ) && is_string( $_GET['post_ids'] )
        ? explode( ',', sanitize_text_field( wp_unslash( $_GET['post_ids'] ) ) )
        : array();

    // A selection must originate from a valid bulk-action URL, including when saving.
    if ( ! empty( $raw_ids ) ) {
        check_admin_referer( 'rm_open_bulk_edit_names' );
    }

    $post_ids = rm_bulk_acf_editable_ids( $raw_ids );

    if ( isset( $_POST['rm_save_bulk_names'] ) && '1' === $_POST['rm_save_bulk_names'] ) {
        rm_bulk_acf_save( $post_ids );
    }

    $fields = rm_bulk_acf_fields();
    echo '<div class="wrap"><h1>' . esc_html__( 'Bulk Edit Menu Fields', 'restaurant-menu' ) . '</h1>';

    if ( isset( $_GET['rm_updated'] ) ) {
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html( sprintf(
                __( 'Processed %1$d menu item(s). Skipped: %2$d.', 'restaurant-menu' ),
                absint( $_GET['rm_updated'] ),
                isset( $_GET['rm_skipped'] ) ? absint( $_GET['rm_skipped'] ) : 0
            ) )
        );
    }

    if ( ! $post_ids ) {
        echo '<div class="notice notice-warning"><p>' . esc_html__( 'Select editable items from the Menu Items list, then choose Bulk Edit ACF.', 'restaurant-menu' ) . '</p></div>';
        echo '</div>';
        return;
    }

    echo '<p>' . esc_html__( 'Edit Arabic and English names, descriptions, compact display setting, and slugs for the selected menu items.', 'restaurant-menu' ) . '</p>';
    echo '<form method="post">';
    wp_nonce_field( 'rm_save_bulk_names_action', 'rm_bulk_names_nonce' );
    echo '<input type="hidden" name="rm_save_bulk_names" value="1">';
    echo '<div style="overflow-x:auto"><table class="widefat striped"><thead><tr>';
    echo'<th>Price</th>';

    foreach ( $fields as $config ) {
        echo '<th>' . esc_html( $config['label'] ) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ( $post_ids as $post_id ) {
        $price=get_field( 'price', $post_id );
        // echo '<tr><td><strong>' . esc_html( get_the_title( $post_id ) ) . '</strong>';
        echo '<tr><td><strong>' . esc_html(  $price  ) . '</strong>';
        echo '<input type="hidden" name="item_ids[]" value="' . esc_attr( $post_id ) . '"></td>';

        foreach ( $fields as $key => $config ) {
            $value = 'slug' === $config['type']
                ? get_post_field( 'post_name', $post_id )
                : ( function_exists( 'get_field' )
                    ? get_field( $key, $post_id )
                    : get_post_meta( $post_id, $key, true ) );
            $value = is_scalar( $value ) ? (string) $value : '';
            $name  = $key . '[' . $post_id . ']';

            echo '<td>';
            if ( 'checkbox' === $config['type'] ) {
                // Hidden value ensures unchecking an existing true value saves false.
                echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
                echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( $value, '1', false ) . '>';
            } elseif ( 'textarea' === $config['type'] ) {
                echo '<textarea cols="33" rows="5" class="large-text" dir="' . esc_attr( $config['dir'] ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
            } elseif ('text' === $config['type']) {
                echo '<input type="text" class="large-text" dir="' . esc_attr( $config['dir'] ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
            } else {
                echo '<input type="text" class="large-text" dir="' . esc_attr( $config['dir'] ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
                echo '<br><a href="' . esc_url( get_edit_post_link( $post_id ) ) . '" target="_blank">' . esc_html__( 'Edit more...', 'restaurant-menu' ) . '</a>';
            }
            echo '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table></div>';
    echo '<p class="submit"><button type="submit" class="button button-primary">' . esc_html__( 'Save Changes', 'restaurant-menu' ) . '</button> ';
    echo '<a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=' . RM_MENU_ITEM_CPT ) ) . '">' . esc_html__( 'Cancel', 'restaurant-menu' ) . '</a></p>';
    echo '</form></div>';
}