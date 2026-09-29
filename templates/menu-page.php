<?php
/**
 * Restaurant Menu Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// die;
/*
 * Current language.
 *
 * Arabic is the default language.
 */
$lang = isset( $_GET['lang'] ) && 'en' === $_GET['lang']
	? 'en'
	: 'ar';

$is_rtl = ( 'ar' === $lang );

$direction = $is_rtl ? 'rtl' : 'ltr';
$language  = $is_rtl ? 'ar' : 'en';

$name_field        = $is_rtl ? 'name_ar' : 'name_en';
$description_field = $is_rtl ? 'description_ar' : 'description_en';

$categories = get_terms(
	array(
		'taxonomy'   => 'rm_menu_category',
		'hide_empty' => true,
	)
);
?>

<div class="restaurant-menu restaurant-menu--<?php echo esc_attr( $language ); ?>"
    dir="<?php echo esc_attr( $direction ); ?>" lang="<?php echo esc_attr( $language ); ?>">

    <!-- Header -->
    <header class="restaurant-menu__header">

        <div class="restaurant-menu__logo">
            <?php
			/*
			 * We'll replace this with an ACF restaurant logo
			 * later.
			 */
			?>
            <div class="restaurant-menu__logo-placeholder">
                🍽️
            </div>
        </div>

        <h1 class="restaurant-menu__title">
            <?php echo $is_rtl ? 'قائمة الطعام' : 'Our Menu'; ?>
        </h1>

        <div class="restaurant-menu__language">

            <a href="<?php echo esc_url( add_query_arg( 'lang', 'ar' ) ); ?>"
                class="<?php echo $is_rtl ? 'active' : ''; ?>">
                العربية
            </a>

            <span>|</span>

            <a href="<?php echo esc_url( add_query_arg( 'lang', 'en' ) ); ?>"
                class="<?php echo ! $is_rtl ? 'active' : ''; ?>">
                English
            </a>

        </div>

    </header>


    <!-- Category navigation -->
    <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>

    <nav class="restaurant-menu__categories">

        <?php foreach ( $categories as $category ) : ?>

        <?php
				$category_name = get_field(
					$is_rtl ? 'name_ar' : 'name_en',
					$category
				);

				if ( empty( $category_name ) ) {
					$category_name = $category->name;
				}
				?>

        <a href="#category-<?php echo esc_attr( $category->term_id ); ?>" class="restaurant-menu__category">

            <?php echo esc_html( $category_name ); ?>
        </a>

        <?php endforeach; ?>

    </nav>

    <?php endif; ?>


    <!-- Menu content -->
    <main class="restaurant-menu__content">

        <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>

        <?php foreach ( $categories as $category ) : ?>

        <?php
				$category_name = get_field(
					$is_rtl ? 'name_ar' : 'name_en',
					$category
				);

				if ( empty( $category_name ) ) {
					$category_name = $category->name;
				}

				$menu_items = new WP_Query(
					array(
						'post_type'      => RM_MENU_ITEM_CPT,
						'post_status'    => 'publish',
						'posts_per_page' => -1,

						'tax_query'      => array(
							array(
								'taxonomy' => 'rm_menu_category',
								'field'    => 'term_id',
								'terms'    => $category->term_id,
							),
						),

						'orderby'        => 'menu_order',
						'order'          => 'ASC',
					)
				);
				?>

        <?php if ( $menu_items->have_posts() ) : ?>

        <section id="category-<?php echo esc_attr( $category->term_id ); ?>" class="restaurant-menu__section">

            <h2 class="restaurant-menu__section-title">
                <?php echo esc_html( $category_name ); ?>
            </h2>


            <div class="restaurant-menu__items">

                <?php while ( $menu_items->have_posts() ) : ?>

                <?php $menu_items->the_post(); ?>

                <?php
								$item_id = get_the_ID();

								$name = get_field(
									$name_field,
									$item_id
								);

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

								/*
								 * If Available is explicitly false,
								 * don't display the item.
								 */
								if ( false === $available ) {
									continue;
								}

								$item_class = $featured
									? 'menu-item menu-item--featured'
									: 'menu-item';
								?>


                <article class="<?php echo esc_attr( $item_class ); ?>">

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


                    <div class="menu-item__body">

                        <div class="menu-item__header">

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

                                <span>
                                    <?php echo esc_html( $price ); ?>
                                </span>

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

        <?php else : ?>

        <p class="restaurant-menu__empty">
            <?php
				echo $is_rtl
					? 'لا توجد أصناف متاحة حالياً.'
					: 'No menu items available.';
				?>
        </p>

        <?php endif; ?>

    </main>

</div>