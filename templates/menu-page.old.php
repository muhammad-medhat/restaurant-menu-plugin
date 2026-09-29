<?php
/**
 * old Restaurant Menu Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lang = isset( $_GET['lang'] ) && 'en' === $_GET['lang']
	? 'en'
	: 'ar';

$is_rtl = ( 'ar' === $lang );

$direction = $is_rtl ? 'rtl' : 'ltr';
$language  = $is_rtl ? 'ar' : 'en';

$name_field        = $is_rtl ? 'name_ar' : 'name_en';
$description_field = $is_rtl ? 'description_ar' : 'description_en';
?>

<div class="restaurant-menu restaurant-menu--<?php echo esc_attr( $language ); ?>"
    dir="<?php echo esc_attr( $direction ); ?>" lang="<?php echo esc_attr( $language ); ?>">

    <header class="restaurant-menu__header">

        <div class="restaurant-menu__language-switcher">

            <a href="<?php echo esc_url( add_query_arg( 'lang', 'ar' ) ); ?>"
                class="<?php echo $is_rtl ? 'active' : ''; ?>">
                العربية
            </a>

            <span>/</span>

            <a href="<?php echo esc_url( add_query_arg( 'lang', 'en' ) ); ?>"
                class="<?php echo ! $is_rtl ? 'active' : ''; ?>">
                English
            </a>

        </div>

    </header>


    <?php
        $categories = get_terms(
            array(
                'taxonomy'   => 'rm_menu_category',
                'hide_empty' => true,
            )
        );
	?>

    <nav class="restaurant-menu__categories">

        <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>

        <?php foreach ( $categories as $category ) : ?>

        <?php
                $category_name = get_field(
                    $is_rtl ? 'name_ar' : 'name_en',
                    $category
                );

                // Fallback to the default WordPress category name.
                if ( empty( $category_name ) ) {
                    $category_name = $category->name;
                }
        ?>

        <a href="#category-<?php echo esc_attr( $category->term_id ); ?>" class="restaurant-menu__category">
            <?php echo esc_html( $category_name ); ?>
        </a>

        <?php endforeach; ?>

    </nav>


    <main class="restaurant-menu__content">

        <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>

        <?php foreach ( $categories as $category ) : ?>

        <?php
				$menu_items = new WP_Query(
					array(
                        'post_type'      => RM_MENU_ITEM_CPT,
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'tax_query'      => array(
							array(
								'taxonomy' => 'rm_menu_category',
								'field'    => 'term_id',
								'terms'    => $category->term_id,
							),
						),
					)
				);
				?>

        <?php if ( $menu_items->have_posts() ) : ?>

        <section id="category-<?php echo esc_attr( $category->term_id ); ?>" class="restaurant-menu__category-section">
            <?php
                $category_name = get_field(
                    $is_rtl ? 'name_ar' : 'name_en',
                    $category
                );

                if ( empty( $category_name ) ) {
                    $category_name = $category->name;
                }
            ?>
            <h2 class="restaurant-menu__category-title">
                <?php echo esc_html( $category->name ); ?>
            </h2>


            <div class="restaurant-menu__items">

                <?php while ( $menu_items->have_posts() ) : ?>

                <?php $menu_items->the_post(); ?>

                <?php
								$item_id = get_the_ID();

								$name = get_field( $name_field, $item_id );

								$description = get_field(
									$description_field,
									$item_id
								);

								$price = get_field(
									'price',
									$item_id
								);

								$old_price = get_field(
									'old_price',
									$item_id
								);

								$image = get_field(
									'item_image',
									$item_id
								);

								$available = get_field(
									'available',
									$item_id
								);

								$featured = get_field(
									'featured',
									$item_id
								);

								if ( false === $available ) {
									continue;
								}
								?>

                <article class="menu-item <?php echo $featured ? 'menu-item--featured' : ''; ?>">

                    <?php if ( $image ) : ?>

                    <div class="menu-item__image">

                        <?php
											echo wp_get_attachment_image(
												$image,
												'medium',
												false,
												array(
													'loading' => 'lazy',
												)
											);
											?>

                    </div>

                    <?php endif; ?>


                    <div class="menu-item__content">

                        <div class="menu-item__top">

                            <h3 class="menu-item__name">
                                <?php echo esc_html( $name ); ?>
                            </h3>

                            <?php if ( $price ) : ?>

                            <div class="menu-item__price">

                                <?php if ( $old_price ) : ?>

                                <del>
                                    <?php echo esc_html( $old_price ); ?>
                                </del>

                                <?php endif; ?>

                                <strong>
                                    <?php echo esc_html( $price ); ?>
                                </strong>

                            </div>

                            <?php endif; ?>

                        </div>


                        <?php if ( $description ) : ?>

                        <p class="menu-item__description">
                            <?php echo esc_html( $description ); ?>
                        </p>

                        <?php endif; ?>

                    </div>

                </article>

                <?php endwhile; ?>

            </div>

        </section>

        <?php endif; ?>

        <?php wp_reset_postdata(); ?>

        <?php endforeach; ?>

        <?php endif; ?>

    </main>

</div>
<?php endif ;?>