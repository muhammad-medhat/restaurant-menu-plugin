<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * =========================================================
 * ADMIN MENU
 * =========================================================
 */

function rm_category_order_admin_menu() {

    add_submenu_page(
        RM_MENU_SLUG,
        __( 'Category Order', 'restaurant-menu' ),
        __( 'Category Order', 'restaurant-menu' ),
        'manage_categories',
        'rm-category-order',
        'rm_category_order_page'
    );
}

add_action(
    'admin_menu',
    'rm_category_order_admin_menu'
);


/*
 * =========================================================
 * ADMIN ASSETS
 * =========================================================
 */

function rm_category_order_admin_assets( $hook ) {

    if ( 'rm_menu_item_page_rm-category-order' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'jquery-ui-sortable'
    );

    wp_enqueue_style(
        'dashicons'
    );

    wp_add_inline_style(
        'dashicons',
        '
        .rm-category-order-wrap {
            max-width: 800px;
        }

        .rm-category-order-intro {
            margin: 8px 0 20px;
            color: #646970;
            font-size: 14px;
        }

        .rm-category-order-list {
            max-width: 700px;
            margin: 0;
            padding: 0;
        }

        .rm-category-order-item {
            display: flex;
            align-items: center;
            gap: 12px;

            margin-bottom: 8px;
            padding: 14px 16px;

            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 6px;

            cursor: move;

            box-sizing: border-box;
        }

        .rm-category-order-item:hover {
            border-color: #8c8f94;
        }

        .rm-category-order-handle {
            color: #8c8f94;
            font-size: 20px;
            line-height: 1;
        }

        .rm-category-order-name {
            flex: 1;
            font-weight: 600;
        }

        .rm-category-order-id {
            color: #8c8f94;
            font-size: 12px;
        }

        .rm-category-order-placeholder {
            height: 48px;
            margin-bottom: 8px;

            border: 2px dashed #2271b1;
            border-radius: 6px;

            background: #f0f6fc;
            box-sizing: border-box;
        }

        .rm-category-order-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }

        .rm-category-order-status {
            display: none;
            font-weight: 500;
        }

        .rm-category-order-status.is-success {
            display: inline-block;
            color: #008a20;
        }

        .rm-category-order-status.is-error {
            display: inline-block;
            color: #d63638;
        }
        '
    );

    wp_add_inline_script(
        'jquery-ui-sortable',
        '
        jQuery(function($) {

            var $list = $("#rm-category-order-list");
            var $saveButton = $("#rm-save-category-order");
            var $status = $("#rm-category-order-status");

            if (!$list.length) {
                return;
            }

            /*
             * Enable drag and drop.
             */
            $list.sortable({

                items: ".rm-category-order-item",

                handle: ".rm-category-order-handle",

                placeholder: "rm-category-order-placeholder",

                tolerance: "pointer"

            });


            /*
             * Save order.
             */
            $saveButton.on("click", function(event) {

                event.preventDefault();

                var categoryIds = [];

                $list
                    .find(".rm-category-order-item")
                    .each(function() {

                        var categoryId = parseInt(
                            $(this).data("term-id"),
                            10
                        );

                        if (categoryId > 0) {
                            categoryIds.push(categoryId);
                        }

                    });


                $saveButton.prop(
                    "disabled",
                    true
                );


                $status
                    .removeClass(
                        "is-success is-error"
                    )
                    .text("Saving...")
                    .show();


                $.ajax({

                    url: ajaxurl,

                    type: "POST",

                    dataType: "json",

                    data: {

                        action: "rm_save_category_order",

                        nonce: rmCategoryOrder.nonce,

                        category_ids: categoryIds

                    },

                    success: function(response) {

                        if (
                            response &&
                            response.success
                        ) {

                            $status
                                .removeClass(
                                    "is-error"
                                )
                                .addClass(
                                    "is-success"
                                )
                                .text(
                                    response.data.message
                                );

                        } else {

                            var message =
                                "Could not save category order.";

                            if (
                                response &&
                                response.data &&
                                response.data.message
                            ) {
                                message =
                                    response.data.message;
                            }

                            $status
                                .removeClass(
                                    "is-success"
                                )
                                .addClass(
                                    "is-error"
                                )
                                .text(
                                    message
                                );
                        }

                    },

                    error: function() {

                        $status
                            .removeClass(
                                "is-success"
                            )
                            .addClass(
                                "is-error"
                            )
                            .text(
                                "An unexpected error occurred."
                            );

                    },

                    complete: function() {

                        $saveButton.prop(
                            "disabled",
                            false
                        );

                    }

                });

            });

        });
        '
    );


    /*
     * Pass nonce to JavaScript.
     */
    wp_localize_script(
        'jquery-ui-sortable',
        'rmCategoryOrder',
        array(
            'nonce' => wp_create_nonce(
                'rm_save_category_order'
            ),
        )
    );
}

add_action(
    'admin_enqueue_scripts',
    'rm_category_order_admin_assets'
);


/*
 * =========================================================
 * ADMIN PAGE
 * =========================================================
 */

function rm_category_order_page() {

    if (
        ! current_user_can(
            'manage_categories'
        )
    ) {

        wp_die(
            esc_html__(
                'You do not have permission to manage categories.',
                'restaurant-menu'
            )
        );

    }


    /*
     * Get categories.
     */
    $categories = get_terms(
        array(
            'taxonomy'   => RM_MENU_CATEGORY_TAX,
            'hide_empty' => false,
            'orderby'    => 'term_order',
            'order'      => 'ASC',
        )
    );


    if ( is_wp_error( $categories ) ) {
        $categories = array();
    }


    /*
     * Get saved order.
     */
    $saved_order = get_option(
        'rm_category_order',
        array()
    );


    if ( ! is_array( $saved_order ) ) {
        $saved_order = array();
    }


    /*
     * Convert to integer IDs.
     */
    $saved_order = array_map(
        'absint',
        $saved_order
    );


    /*
     * Reorder categories according to
     * saved order.
     */
    if ( ! empty( $saved_order ) ) {

        $ordered_categories = array();

        foreach ( $saved_order as $term_id ) {

            foreach ( $categories as $category_key => $category ) {

                if (
                    (int) $category->term_id ===
                    (int) $term_id
                ) {

                    $ordered_categories[] =
                        $category;

                    unset(
                        $categories[ $category_key ]
                    );

                    break;
                }
            }
        }


        /*
         * Add any new categories that
         * aren't in the saved order.
         */
        foreach ( $categories as $category ) {

            $ordered_categories[] =
                $category;
        }


        $categories =
            $ordered_categories;
    }

    ?>

<div class="wrap rm-category-order-wrap">

    <h1>
        <?php
            esc_html_e(
                'Category Order',
                'restaurant-menu'
            );
            ?>
    </h1>


    <p class="rm-category-order-intro">

        <?php
            esc_html_e(
                'Drag and drop the categories to control the order in which they appear on the frontend menu.',
                'restaurant-menu'
            );
            ?>

    </p>


    <?php if ( empty( $categories ) ) : ?>

    <p>
        <?php
                esc_html_e(
                    'No categories found.',
                    'restaurant-menu'
                );
                ?>
    </p>

    <?php else : ?>

    <ul id="rm-category-order-list" class="rm-category-order-list">

        <?php foreach ( $categories as $category ) : ?>

        <li class="rm-category-order-item" data-term-id="<?php echo esc_attr(
                            $category->term_id
                        ); ?>">

            <span class="rm-category-order-handle dashicons dashicons-menu" aria-hidden="true"></span>


            <span class="rm-category-order-name">

                <?php
                            echo esc_html(
                                $category->name
                            );
                            ?>

            </span>


            <span class="rm-category-order-id">

                <?php
                            printf(
                                esc_html__(
                                    'ID: %d',
                                    'restaurant-menu'
                                ),
                                absint(
                                    $category->term_id
                                )
                            );
                            ?>

            </span>

        </li>

        <?php endforeach; ?>

    </ul>


    <div class="rm-category-order-actions">

        <button type="button" class="button button-primary" id="rm-save-category-order">
            <?php
                    esc_html_e(
                        'Save Category Order',
                        'restaurant-menu'
                    );
                    ?>
        </button>


        <span id="rm-category-order-status" class="rm-category-order-status" role="status" aria-live="polite"></span>

    </div>

    <?php endif; ?>

</div>

<?php
}


/*
 * =========================================================
 * AJAX SAVE
 * =========================================================
 */

function rm_save_category_order() {

    /*
     * Verify nonce.
     */
    check_ajax_referer(
        'rm_save_category_order',
        'nonce'
    );


    /*
     * Verify capability.
     */
    if (
        ! current_user_can(
            'manage_categories'
        )
    ) {

        wp_send_json_error(
            array(
                'message' => __(
                    'You do not have permission to reorder categories.',
                    'restaurant-menu'
                ),
            ),
            403
        );

    }


    /*
     * Get submitted IDs.
     */
    $category_ids =
        isset( $_POST['category_ids'] )
            ? wp_unslash(
                $_POST['category_ids']
            )
            : array();


    if ( ! is_array( $category_ids ) ) {

        wp_send_json_error(
            array(
                'message' => __(
                    'Invalid category order.',
                    'restaurant-menu'
                ),
            ),
            400
        );

    }


    /*
     * Sanitize IDs.
     */
    $category_ids = array_map(
        'absint',
        $category_ids
    );


    $category_ids = array_values(
        array_unique(
            array_filter(
                $category_ids
            )
        )
    );


    /*
     * Get actual taxonomy terms.
     */
    $valid_categories = get_terms(
        array(
            'taxonomy'   => RM_MENU_CATEGORY_TAX,
            'hide_empty' => false,
            'fields'     => 'ids',
        )
    );


    if ( is_wp_error( $valid_categories ) ) {

        wp_send_json_error(
            array(
                'message' => __(
                    'Could not load categories.',
                    'restaurant-menu'
                ),
            ),
            500
        );

    }


    $valid_categories = array_map(
        'absint',
        $valid_categories
    );


    /*
     * Validate submitted IDs.
     */
    foreach ( $category_ids as $category_id ) {

        if (
            ! in_array(
                $category_id,
                $valid_categories,
                true
            )
        ) {

            wp_send_json_error(
                array(
                    'message' => __(
                        'Invalid category detected.',
                        'restaurant-menu'
                    ),
                ),
                400
            );

        }

    }


    /*
     * Make sure newly-created categories
     * are not lost.
     */
    foreach ( $valid_categories as $category_id ) {

        if (
            ! in_array(
                $category_id,
                $category_ids,
                true
            )
        ) {

            $category_ids[] =
                $category_id;
        }

    }


    /*
     * Save the complete order.
     */
    update_option(
        'rm_category_order',
        $category_ids,
        false
    );


    /*
     * Success.
     */
    wp_send_json_success(
        array(
            'message' => __(
                'Category order saved successfully.',
                'restaurant-menu'
            ),
        )
    );
}

add_action(
    'wp_ajax_rm_save_category_order',
    'rm_save_category_order'
);