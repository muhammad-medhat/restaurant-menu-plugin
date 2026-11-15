<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Apply the native menu item order when the caller leaves sorting unspecified.
 */
add_action( 'pre_get_posts', 'rm_apply_frontend_item_order' );
function rm_apply_frontend_item_order( $query ) {
    if ( is_admin() || RM_MENU_ITEM_CPT !== $query->get( 'post_type' ) || $query->get( 'orderby' ) ) {
        return;
    }

    $query->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
}

add_filter( 'rest_' . RM_MENU_ITEM_CPT . '_query', 'rm_apply_rest_item_order', 10, 2 );
function rm_apply_rest_item_order( $args, $request ) {
    $params = $request->get_query_params();
    if ( ! isset( $params['orderby'] ) ) {
        $args['orderby'] = array(
            'menu_order' => ( isset( $params['order'] ) && 'desc' === strtolower( $params['order'] ) ) ? 'DESC' : 'ASC',
            'ID'         => 'ASC',
        );
    }

    return $args;
}

/**
 * Load published items for the selected category in their current order.
 */
function rm_get_ordered_category_items( $term_id ) {
    return new WP_Query( array(
        'post_type'      => RM_MENU_ITEM_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
        'tax_query'      => array(
            array(
                'taxonomy' => RM_MENU_CATEGORY_TAX,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ),
        ),
    ) );
}

add_action( 'admin_menu', 'rm_item_order_admin_menu' );
function rm_item_order_admin_menu() {
    $hook = add_submenu_page(
        RM_MENU_SLUG,
        __( 'Menu Item Order', 'restaurant-menu' ),
        __( 'Menu Item Order', 'restaurant-menu' ),
        'edit_posts',
        'rm-item-order',
        'rm_item_order_page'
    );

    if ( $hook ) {
        add_action( 'load-' . $hook, 'rm_item_order_load_page' );
    }
}

function rm_item_order_selected_category() {
    $term_id = isset( $_GET['category_id'] ) && is_string( $_GET['category_id'] )
        ? absint( $_GET['category_id'] )
        : 0;

    return $term_id ? get_term( $term_id, RM_MENU_CATEGORY_TAX ) : null;
}

function rm_item_order_load_page() {
    wp_enqueue_script(
        'rm-item-order',
        RM_URL . 'assets/js/item-order.js',
        array( 'jquery-ui-sortable' ),
        '1.0.0',
        true
    );
    wp_enqueue_style( 'rm-item-order', RM_URL . 'assets/css/item-order.css', array( 'dashicons' ), '1.0.0' );

    if ( ! isset( $_POST['rm_item_order'] ) ) {
        return;
    }

    $category = rm_item_order_selected_category();
    if ( ! $category instanceof WP_Term || ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'Invalid menu category.', 'restaurant-menu' ) );
    }

    check_admin_referer( 'rm_save_item_order_' . $category->term_id );
    $submitted = is_string( $_POST['rm_item_order'] ) ? wp_unslash( $_POST['rm_item_order'] ) : '';
    $ids = preg_match( '/\A[1-9][0-9]*(?:,[1-9][0-9]*)*\z/', $submitted )
        ? array_map( 'intval', explode( ',', $submitted ) )
        : array();

    $expected = array_map( 'intval', wp_list_pluck( rm_get_ordered_category_items( $category->term_id )->posts, 'ID' ) );
    $sorted_ids = $ids;
    sort( $expected );
    sort( $sorted_ids );

    if ( ! $ids || $expected !== $sorted_ids ) {
        wp_die( esc_html__( 'The category items have changed. Reload the order page and try again.', 'restaurant-menu' ) );
    }

    foreach ( $ids as $id ) {
        if ( ! current_user_can( 'edit_post', $id ) ) {
            wp_die( esc_html__( 'You need permission to edit all items in this category to reorder them.', 'restaurant-menu' ) );
        }
    }

    // Update all positions in one statement so readers see the complete order.
    global $wpdb;
    $positions = array();
    foreach ( $ids as $index => $id ) {
        $positions[] = $wpdb->prepare( 'WHEN %d THEN %d', $id, $index );
    }

    $cases = implode( ' ', $positions );
    $id_list = implode( ',', $ids );
    $saved = $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->posts} SET menu_order = CASE ID {$cases} END
        WHERE post_type = %s AND post_status = 'publish' AND ID IN ({$id_list})",
        RM_MENU_ITEM_CPT
    ) );

    if ( false === $saved ) {
        wp_die( esc_html__( 'The menu item order could not be saved.', 'restaurant-menu' ) );
    }

    foreach ( $ids as $id ) {
        clean_post_cache( $id );
    }

    wp_safe_redirect( add_query_arg(
        array( 'post_type' => RM_MENU_ITEM_CPT, 'page' => 'rm-item-order', 'category_id' => $category->term_id, 'order_saved' => '1' ),
        admin_url( 'edit.php' )
    ) );
    exit;
}

function rm_item_order_page() {
    $categories = get_terms( array( 'taxonomy' => RM_MENU_CATEGORY_TAX, 'hide_empty' => false ) );
    $category = rm_item_order_selected_category();
    ?>
    <div class="wrap rm-item-order">
        <h1><?php esc_html_e( 'Menu Item Order', 'restaurant-menu' ); ?></h1>
        <?php if ( is_wp_error( $categories ) || ! $categories ) : ?>
            <p><?php esc_html_e( 'No menu categories found.', 'restaurant-menu' ); ?></p>
        <?php else : ?>
            <form method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
                <input type="hidden" name="post_type" value="<?php echo esc_attr( RM_MENU_ITEM_CPT ); ?>">
                <input type="hidden" name="page" value="rm-item-order">
                <label for="rm-order-category"><?php esc_html_e( 'Category', 'restaurant-menu' ); ?></label>
                <select name="category_id" id="rm-order-category" required>
                    <option value=""><?php esc_html_e( 'Select a category', 'restaurant-menu' ); ?></option>
                    <?php foreach ( $categories as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $category instanceof WP_Term ? $category->term_id : 0, $term->term_id ); ?>>
                            <?php echo esc_html( $term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button"><?php esc_html_e( 'Load Items', 'restaurant-menu' ); ?></button>
            </form>
        <?php endif; ?>

        <?php if ( $category instanceof WP_Term ) : ?>
            <?php $items = rm_get_ordered_category_items( $category->term_id )->posts; ?>
            <?php if ( isset( $_GET['order_saved'] ) && '1' === $_GET['order_saved'] ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Menu item order saved.', 'restaurant-menu' ); ?></p></div>
            <?php endif; ?>
            <?php if ( $items ) : ?>
                <p><?php esc_html_e( 'Drag the items or use the Up and Down buttons to set their order on the menu.', 'restaurant-menu' ); ?></p>
                <form method="post" id="rm-item-order-form">
                    <?php wp_nonce_field( 'rm_save_item_order_' . $category->term_id ); ?>
                    <input type="hidden" name="rm_item_order" value="<?php echo esc_attr( implode( ',', wp_list_pluck( $items, 'ID' ) ) ); ?>">
                    <ol id="rm-item-order-list">
                        <?php foreach ( $items as $item ) : ?>
                            <li data-item-id="<?php echo esc_attr( $item->ID ); ?>">
                                <span class="rm-item-order-handle dashicons dashicons-menu" aria-hidden="true"></span>
                                <span class="rm-item-order-name"><?php echo esc_html( get_the_title( $item ) ?: '#' . $item->ID ); ?></span>
                                <button type="button" class="button rm-item-order-up"><?php esc_html_e( 'Up', 'restaurant-menu' ); ?></button>
                                <button type="button" class="button rm-item-order-down"><?php esc_html_e( 'Down', 'restaurant-menu' ); ?></button>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Menu Item Order', 'restaurant-menu' ); ?></button>
                </form>
            <?php else : ?>
                <p><?php esc_html_e( 'This category has no published menu items.', 'restaurant-menu' ); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}
