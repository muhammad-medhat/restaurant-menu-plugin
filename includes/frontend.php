<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Restaurant Menu Shortcode
 * Usage:
 * [restaurant_menu]
 */
function rm_render_menu() {

    ob_start();

    ?>

<div class="rm-menu">

    <div class="rm-menu__language">
        <button type="button" class="rm-language-btn active" data-language="ar">
            العربية
        </button>

        <button type="button" class="rm-language-btn" data-language="en">
            English
        </button>
    </div>


    <div class="rm-menu__categories">

        <?php

            $categories = get_terms(
                array(
                    'taxonomy'   => RM_MENU_CATEGORY_TAX,
                    'hide_empty' => true,
                )
            );


            if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) :

                foreach ( $categories as $category ) :

                    ?>

        <section class="rm-category" id="rm-category-<?php echo esc_attr( $category->term_id ); ?>">

            <h2 class="rm-category__title">

                <span class="rm-lang rm-lang-ar">
                    <?php echo esc_html( $category->name ); ?>
                </span>

                <span class="rm-lang rm-lang-en">
                    <?php
                                echo esc_html(
                                    get_term_meta(
                                        $category->term_id,
                                        'name_en',
                                        true
                                    )
                                );
                                ?>
                </span>

            </h2>


            <div class="rm-category__items">

                <?php

                            $items = new WP_Query(
                                array(
                                    'post_type'      => RM_MENU_ITEM_CPT,
                                    'post_status'    => 'publish',
                                    'posts_per_page' => -1,

                                    'tax_query' => array(
                                        array(
                                            'taxonomy' => RM_MENU_CATEGORY_TAX,
                                            'field'    => 'term_id',
                                            'terms'    => $category->term_id,
                                        ),
                                    ),

                                    'orderby' => 'menu_order',
                                    'order'   => 'ASC',
                                )
                            );


                            if ( $items->have_posts() ) :

                                while ( $items->have_posts() ) :

                                    $items->the_post();

                                    $post_id = get_the_ID();


                                    $name_ar = get_field(
                                        'name_ar',
                                        $post_id
                                    );

                                    $name_en = get_field(
                                        'name_en',
                                        $post_id
                                    );

                                    $desc_ar = get_field(
                                        'desc_ar',
                                        $post_id
                                    );

                                    $desc_en = get_field(
                                        'desc_en',
                                        $post_id
                                    );

                                    $price = get_field(
                                        'price',
                                        $post_id
                                    );

                                    ?>

                <article class="rm-item">

                    <div class="rm-item__content">

                        <h3 class="rm-item__name">

                            <span class="rm-lang rm-lang-ar">
                                <?php
                                                    echo esc_html(
                                                        $name_ar
                                                    );
                                                    ?>
                            </span>

                            <span class="rm-lang rm-lang-en">
                                <?php
                                                    echo esc_html(
                                                        $name_en
                                                    );
                                                    ?>
                            </span>

                        </h3>


                        <div class="rm-item__description">

                            <span class="rm-lang rm-lang-ar">
                                <?php
                                                    echo esc_html(
                                                        $desc_ar
                                                    );
                                                    ?>
                            </span>

                            <span class="rm-lang rm-lang-en">
                                <?php
                                                    echo esc_html(
                                                        $desc_en
                                                    );
                                                    ?>
                            </span>

                        </div>


                        <div class="rm-item__price">

                            <?php
                                                echo esc_html(
                                                    $price
                                                );
                                                ?>

                            <span class="rm-currency rm-currency-ar">
                                جنيه
                            </span>

                            <span class="rm-currency rm-currency-en">
                                EGP
                            </span>

                        </div>

                    </div>

                </article>

                <?php

                                endwhile;

                                wp_reset_postdata();

                            endif;

                            ?>

            </div>

        </section>

        <?php

                endforeach;

            endif;

            ?>

    </div>

</div>

<?php

    return ob_get_clean();
}


add_shortcode(
    'restaurant_menu1',
    'rm_render_menu'
);