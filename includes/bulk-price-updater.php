<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Bulk Price Update admin page.
 */
function rm_register_bulk_price_page() {

    add_submenu_page(
        // RM_MENU_SLUG,
        'edit.php?post_type=' . RM_MENU_ITEM_CPT,
        __( 'Bulk Price Update', 'restaurant-menu' ),
        __( 'Bulk Price Update', 'restaurant-menu' ),
        'manage_options',
        'rm-bulk-price-update',
        'rm_render_bulk_price_page'
    );
}

add_action(
    'admin_menu',
    'rm_register_bulk_price_page'
);


/**
 * Get menu categories.
 */
function rm_get_bulk_price_categories() {

    $categories = get_terms(
        array(
            'taxonomy'   => RM_MENU_CATEGORY_TAX,
            'hide_empty' => false,
        )
    );

    if ( is_wp_error( $categories ) ) {
        return array();
    }

    return $categories;
}


/**
 * Get menu items according to selected category.
 */
function rm_get_bulk_price_items( $category_id = 0 ) {

    $args = array(
        'post_type'      => RM_MENU_ITEM_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );

    if ( $category_id > 0 ) {

        $args['tax_query'] = array(
            array(
                'taxonomy' => RM_MENU_CATEGORY_TAX,
                'field'    => 'term_id',
                'terms'    => $category_id,
            ),
        );
    }

    return get_posts( $args );
}


/**
 * Calculate the new price.
 */
function rm_calculate_new_price(
    $current_price,
    $operation,
    $method,
    $value,
    $rounding
) {

    $current_price = (float) $current_price;
    $value         = (float) $value;

    if ( 'percentage' === $method ) {

        $change = $current_price * ( $value / 100 );

    } else {

        $change = $value;
    }

    if ( 'increase' === $operation ) {

        $new_price = $current_price + $change;

    } else {

        $new_price = $current_price - $change;
    }

    /*
     * Never allow a negative price.
     */
    $new_price = max( 0, $new_price );

    /*
     * Apply rounding.
     */
    switch ( $rounding ) {

        case 'nearest_5':
            $new_price = round( $new_price / 5 ) * 5;
            break;

        case 'nearest_10':
            $new_price = round( $new_price / 10 ) * 10;
            break;

        case 'up_5':
            $new_price = ceil( $new_price / 5 ) * 5;
            break;

        case 'up_10':
            $new_price = ceil( $new_price / 10 ) * 10;
            break;

        case 'down_5':
            $new_price = floor( $new_price / 5 ) * 5;
            break;

        case 'down_10':
            $new_price = floor( $new_price / 10 ) * 10;
            break;

        case 'none':
        default:
            break;
    }

    return $new_price;
}


/**
 * Format a price for display.
 */
function rm_format_bulk_price( $price ) {

    return number_format(
        (float) $price,
        2,
        '.',
        ''
    );
}


/**
 * Preview bulk price changes.
 */
function rm_get_bulk_price_preview( $category_id, $operation, $method, $value, $rounding ) {

    $items = rm_get_bulk_price_items( $category_id );

    $preview = array();

    foreach ( $items as $item ) {

        $current_price = get_field(
            'price',
            $item->ID
        );

        /*
         * Skip items without a valid price.
         */
        if ( '' === $current_price || null === $current_price ) {
            continue;
        }

        if ( ! is_numeric( $current_price ) ) {
            continue;
        }

        $new_price = rm_calculate_new_price(
            $current_price,
            $operation,
            $method,
            $value,
            $rounding
        );

        $preview[] = array(
            'id'            => $item->ID,
            'name'          => get_the_title( $item->ID ),
            'current_price' => (float) $current_price,
            'new_price'     => $new_price,
        );
    }

    return $preview;
}


/**
 * Save the previous prices so the last operation can be undone.
 */
function rm_save_bulk_price_backup( $changes ) {

    update_option(
        'rm_last_bulk_price_update',
        array(
            'time'    => current_time( 'mysql' ),
            'changes' => $changes,
        ),
        false
    );
}


/**
 * Apply bulk price changes.
 */
function rm_apply_bulk_price_changes( $category_id, $operation, $method, $value, $rounding ) {

    $preview = rm_get_bulk_price_preview(
        $category_id,
        $operation,
        $method,
        $value,
        $rounding
    );

    if ( empty( $preview ) ) {
        return array();
    }

    $backup = array();

    foreach ( $preview as $item ) {

        $backup[] = array(
            'id'            => $item['id'],
            'old_price'     => $item['current_price'],
            'new_price'     => $item['new_price'],
        );

        update_field(
            'price',
            $item['new_price'],
            $item['id']
        );
    }

    rm_save_bulk_price_backup( $backup );

    return $backup;
}


/**
 * Undo the last bulk price update.
 */
function rm_undo_last_bulk_price_update() {

    $backup = get_option(
        'rm_last_bulk_price_update',
        array()
    );

    if (
        empty( $backup ) ||
        empty( $backup['changes'] )
    ) {
        return false;
    }

    foreach ( $backup['changes'] as $change ) {

        update_field(
            'price',
            $change['old_price'],
            $change['id']
        );
    }

    delete_option(
        'rm_last_bulk_price_update'
    );

    return true;
}


/**
 * Handle Apply / Undo actions.
 */
function rm_handle_bulk_price_actions() {

    if ( ! isset( $_POST['rm_bulk_price_action'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die(
            esc_html__(
                'You do not have permission to perform this action.',
                'restaurant-menu'
            )
        );
    }

    check_admin_referer(
        'rm_bulk_price_update',
        'rm_bulk_price_nonce'
    );

    $action = sanitize_key(
        wp_unslash(
            $_POST['rm_bulk_price_action']
        )
    );

    /*
     * Undo.
     */
    if ( 'undo' === $action ) {

        $result = rm_undo_last_bulk_price_update();

        $redirect = add_query_arg(
            array(
                'post_type'     => RM_MENU_ITEM_CPT,
                'page'              => 'rm-bulk-price-update',
                'rm_price_undo'     => $result ? 'success' : 'empty',
            ),
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /*
     * Apply.
     */
    if ( 'apply' !== $action ) {
        return;
    }

    $category_id = isset( $_POST['category_id'] )
        ? absint( $_POST['category_id'] )
        : 0;

    $operation = isset( $_POST['operation'] )
        ? sanitize_key(
            wp_unslash( $_POST['operation'] )
        )
        : 'increase';

    $method = isset( $_POST['method'] )
        ? sanitize_key(
            wp_unslash( $_POST['method'] )
        )
        : 'percentage';

    $value = isset( $_POST['value'] )
        ? (float) wp_unslash( $_POST['value'] )
        : 0;

    $rounding = isset( $_POST['rounding'] )
        ? sanitize_key(
            wp_unslash( $_POST['rounding'] )
        )
        : 'none';

    /*
     * Validate.
     */
    $allowed_operations = array(
        'increase',
        'decrease',
    );

    $allowed_methods = array(
        'percentage',
        'fixed',
    );

    $allowed_rounding = array(
        'none',
        'nearest_5',
        'nearest_10',
        'up_5',
        'up_10',
        'down_5',
        'down_10',
    );

    if ( ! in_array( $operation, $allowed_operations, true ) ) {
        $operation = 'increase';
    }

    if ( ! in_array( $method, $allowed_methods, true ) ) {
        $method = 'percentage';
    }

    if ( ! in_array( $rounding, $allowed_rounding, true ) ) {
        $rounding = 'none';
    }

    $value = max( 0, $value );

    if ( $value <= 0 ) {

        $redirect = add_query_arg(
            array(
                'post_type'     => RM_MENU_ITEM_CPT,
                'page'      => 'rm-bulk-price-update',
                'rm_error'  => 'invalid_value',
            ),
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    $changes = rm_apply_bulk_price_changes(
        $category_id,
        $operation,
        $method,
        $value,
        $rounding
    );

    $redirect = add_query_arg(
        array(
            'post_type'     => RM_MENU_ITEM_CPT,
            'page'          => 'rm-bulk-price-update',
            'rm_price_done' => count( $changes ),
        ),
        admin_url( 'edit.php' )
    );

    wp_safe_redirect( $redirect );
    exit;
}

add_action(
    'admin_init',
    'rm_handle_bulk_price_actions'
);


/**
 * Render the Bulk Price Update page.
 */
function rm_render_bulk_price_page() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $categories = rm_get_bulk_price_categories();

    $selected_category = isset( $_GET['category_id'] )
        ? absint( $_GET['category_id'] )
        : 0;

    $operation = isset( $_GET['operation'] )
        ? sanitize_key( wp_unslash( $_GET['operation'] ) )
        : 'increase';

    $method = isset( $_GET['method'] )
        ? sanitize_key( wp_unslash( $_GET['method'] ) )
        : 'percentage';

    $value = isset( $_GET['value'] )
        ? (float) $_GET['value']
        : 10;

    $rounding = isset( $_GET['rounding'] )
        ? sanitize_key( wp_unslash( $_GET['rounding'] ) )
        : 'none';

    $preview = array();

    /*
     * Preview is generated when the user submits Preview.
     */
    if (
        isset( $_GET['rm_preview'] ) &&
        '1' === $_GET['rm_preview']
    ) {

        $preview = rm_get_bulk_price_preview(
            $selected_category,
            $operation,
            $method,
            $value,
            $rounding
        );
    }

    $last_update = get_option(
        'rm_last_bulk_price_update',
        array()
    );

    ?>

<div class="wrap">

    <h1>
        <?php esc_html_e('Bulk Price Update', 'restaurant-menu');?>
    </h1>

    <p>
        <?php
            esc_html_e(
                'Update prices for multiple menu items at once. Always preview the changes before applying them.',
                'restaurant-menu'
            );
            ?>
    </p>

    <?php if ( isset( $_GET['rm_price_done'] ) ) : ?>

    <div class="notice notice-success is-dismissible">

        <p>
            <?php
                    printf(
                        esc_html__(
                            '%d menu item(s) updated successfully.',
                            'restaurant-menu'
                        ),
                        absint( $_GET['rm_price_done'] )
                    );
                    ?>
        </p>

    </div>

    <?php endif; ?>

    <?php if ( isset( $_GET['rm_price_undo'] ) ) : ?>

    <div class="notice notice-success is-dismissible">

        <p>
            <?php
                    if ( 'success' === $_GET['rm_price_undo'] ) {

                        esc_html_e(
                            'The last bulk price update has been undone.',
                            'restaurant-menu'
                        );

                    } else {

                        esc_html_e(
                            'There is no bulk price update available to undo.',
                            'restaurant-menu'
                        );
                    }
                    ?>
        </p>

    </div>

    <?php endif; ?>

    <?php if ( isset( $_GET['rm_error'] ) ) : ?>

    <div class="notice notice-error is-dismissible">

        <p>
            <?php
                    esc_html_e(
                        'Please enter a value greater than zero.',
                        'restaurant-menu'
                    );
                    ?>
        </p>

    </div>

    <?php endif; ?>


    <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">

        <h2>
            <?php
                esc_html_e(
                    'Price Update',
                    'restaurant-menu'
                );
                ?>
        </h2>

        <form method="get">

            <input type="hidden" name="page" value="rm-bulk-price-update">
            <input type="hidden" name="post_type" value="<?php echo esc_attr( RM_MENU_ITEM_CPT ); ?>">

            <table class="form-table">

                <tr>

                    <th scope="row">
                        <label for="category_id">
                            <?php
                                esc_html_e(
                                    'Menu Items',
                                    'restaurant-menu'
                                );
                                ?>
                        </label>
                    </th>

                    <td>

                        <select name="category_id" id="category_id">

                            <option value="0">

                                <?php
                                    esc_html_e(
                                        'All Menu Items',
                                        'restaurant-menu'
                                    );
                                    ?>

                            </option>

                            <?php foreach ( $categories as $category ) : ?>

                            <option value="<?php echo esc_attr( $category->term_id ); ?>"
                                <?php selected( $selected_category, $category->term_id ); ?>>

                                <?php echo esc_html( $category->name ); ?>

                            </option>

                            <?php endforeach; ?>

                        </select>

                    </td>

                </tr>


                <tr>

                    <th scope="row">
                        <?php
                            esc_html_e(
                                'Operation',
                                'restaurant-menu'
                            );
                            ?>
                    </th>

                    <td>

                        <label>

                            <input type="radio" name="operation" value="increase"
                                <?php checked( $operation, 'increase' ); ?>>

                            <?php
                                esc_html_e(
                                    'Increase',
                                    'restaurant-menu'
                                );
                                ?>

                        </label>

                        <br>

                        <label>

                            <input type="radio" name="operation" value="decrease"
                                <?php checked( $operation, 'decrease' ); ?>>

                            <?php
                                esc_html_e(
                                    'Decrease',
                                    'restaurant-menu'
                                );
                                ?>

                        </label>

                    </td>

                </tr>


                <tr>

                    <th scope="row">

                        <label for="method">

                            <?php
                                esc_html_e(
                                    'Update By',
                                    'restaurant-menu'
                                );
                                ?>

                        </label>

                    </th>

                    <td>

                        <select name="method" id="method">

                            <option value="percentage" <?php selected( $method, 'percentage' ); ?>>
                                <?php
                                    esc_html_e(
                                        'Percentage (%)',
                                        'restaurant-menu'
                                    );
                                    ?>
                            </option>

                            <option value="fixed" <?php selected( $method, 'fixed' ); ?>>
                                <?php
                                    esc_html_e(
                                        'Fixed Amount',
                                        'restaurant-menu'
                                    );
                                    ?>
                            </option>

                        </select>

                    </td>

                </tr>


                <tr>

                    <th scope="row">

                        <label for="value">

                            <?php
                                esc_html_e(
                                    'Value',
                                    'restaurant-menu'
                                );
                                ?>

                        </label>

                    </th>

                    <td>

                        <input type="number" name="value" id="value" value="<?php echo esc_attr( $value ); ?>" min="0"
                            step="0.01" class="small-text">

                    </td>

                </tr>


                <tr>

                    <th scope="row">

                        <label for="rounding">

                            <?php
                                esc_html_e(
                                    'Rounding',
                                    'restaurant-menu'
                                );
                                ?>

                        </label>

                    </th>

                    <td>

                        <select name="rounding" id="rounding">

                            <option value="none" <?php selected( $rounding, 'none' ); ?>>
                                <?php esc_html_e( 'No Rounding', 'restaurant-menu' ); ?>
                            </option>

                            <option value="nearest_5" <?php selected( $rounding, 'nearest_5' ); ?>>
                                <?php esc_html_e( 'Nearest 5', 'restaurant-menu' ); ?>
                            </option>

                            <option value="nearest_10" <?php selected( $rounding, 'nearest_10' ); ?>>
                                <?php esc_html_e( 'Nearest 10', 'restaurant-menu' ); ?>
                            </option>

                            <option value="up_5" <?php selected( $rounding, 'up_5' ); ?>>
                                <?php esc_html_e( 'Round Up to 5', 'restaurant-menu' ); ?>
                            </option>

                            <option value="up_10" <?php selected( $rounding, 'up_10' ); ?>>
                                <?php esc_html_e( 'Round Up to 10', 'restaurant-menu' ); ?>
                            </option>

                            <option value="down_5" <?php selected( $rounding, 'down_5' ); ?>>
                                <?php esc_html_e( 'Round Down to 5', 'restaurant-menu' ); ?>
                            </option>

                            <option value="down_10" <?php selected( $rounding, 'down_10' ); ?>>
                                <?php esc_html_e( 'Round Down to 10', 'restaurant-menu' ); ?>
                            </option>

                        </select>

                    </td>

                </tr>

            </table>


            <p class="submit">

                <button type="submit" name="rm_preview" value="1" class="button button-secondary">
                    <?php
                        esc_html_e(
                            'Preview Changes',
                            'restaurant-menu'
                        );
                        ?>
                </button>

            </p>

        </form>

    </div>


    <?php if ( ! empty( $preview ) ) : ?>

    <div class="card" style="max-width: 1000px; padding: 20px; margin-top: 20px;">

        <h2>
            <?php esc_html_e('Price Changes Preview', 'restaurant-menu' );?>
        </h2>

        <table class="widefat striped">

            <thead>

                <tr>

                    <th>
                        <?php esc_html_e( 'Menu Item', 'restaurant-menu' ); ?>
                    </th>

                    <th>
                        <?php esc_html_e( 'Current Price', 'restaurant-menu' ); ?>
                    </th>

                    <th>
                        <?php esc_html_e( 'New Price', 'restaurant-menu' ); ?>
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ( $preview as $item ) : ?>

                <tr>

                    <td>
                        <?php echo esc_html( $item['name'] ); ?>
                    </td>

                    <td>
                        <?php
                                    echo esc_html(
                                        rm_format_bulk_price(
                                            $item['current_price']
                                        )
                                    );
                                    ?>
                    </td>

                    <td>
                        <strong>
                            <?php
                                        echo esc_html(
                                            rm_format_bulk_price(
                                                $item['new_price']
                                            )
                                        );
                                        ?>
                        </strong>
                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>


        <form method="post" style="margin-top:20px;">
            <?php wp_nonce_field('rm_bulk_price_update', 'rm_bulk_price_nonce');?>

            <input type="hidden" name="rm_bulk_price_action" value="apply">
            <input type="hidden" name="category_id" value="<?php echo esc_attr( $selected_category ); ?>">
            <input type="hidden" name="operation" value="<?php echo esc_attr( $operation ); ?>">
            <input type="hidden" name="method" value="<?php echo esc_attr( $method ); ?>">
            <input type="hidden" name="value" value="<?php echo esc_attr( $value ); ?>">
            <input type="hidden" name="rounding" value="<?php echo esc_attr( $rounding ); ?>">

            <button type="submit" class="button button-primary"
                onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to apply these price changes?', 'restaurant-menu' ) ); ?>');">

                <?php
                        esc_html_e(
                            'Apply Price Changes',
                            'restaurant-menu'
                        );
                        ?>

            </button>

        </form>

    </div>

    <?php elseif ( isset( $_GET['rm_preview'] ) ) : ?>

    <div class="notice notice-warning">

        <p>
            <?php
                    esc_html_e(
                        'No menu items with valid prices were found.',
                        'restaurant-menu'
                    );
                    ?>
        </p>

    </div>

    <?php endif; ?>


    <?php if ( ! empty( $last_update['changes'] ) ) : ?>

    <div class="card" style="max-width:800px;padding:20px;margin-top:20px;">

        <h2>
            <?php
                    esc_html_e(
                        'Last Bulk Update',
                        'restaurant-menu'
                    );
                    ?>
        </h2>

        <p>

            <?php
                    printf(
                        esc_html__(
                            '%d item(s) were changed in the last bulk update.',
                            'restaurant-menu'
                        ),
                        count( $last_update['changes'] )
                    );
                    ?>

        </p>

        <?php if ( ! empty( $last_update['time'] ) ) : ?>

        <p>

            <strong>
                <?php
                            esc_html_e(
                                'Date:',
                                'restaurant-menu'
                            );
                            ?>
            </strong>

            <?php
                        echo esc_html(
                            $last_update['time']
                        );
                        ?>

        </p>

        <?php endif; ?>


        <form method="post">
            <?php
                    wp_nonce_field(
                        'rm_bulk_price_update',
                        'rm_bulk_price_nonce'
                    );
                    ?>

            <input type="hidden" name="rm_bulk_price_action" value="undo">

            <button type="submit" class="button"
                onclick="return confirm('<?php echo esc_js( __( 'Undo the last bulk price update?', 'restaurant-menu' ) ); ?>');">

                <?php esc_html_e('Undo Last Update', 'restaurant-menu'); ?>

            </button>

        </form>

    </div>

    <?php endif; ?>

</div>

<?php
}