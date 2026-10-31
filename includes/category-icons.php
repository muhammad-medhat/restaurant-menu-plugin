<?php
/**
 * Category icon picker for the Restaurant Menu plugin.
 *
 * Place this file at:
 * restaurant-menu/includes/category-icons.php
 *
 * Requires RM_MENU_CATEGORY_TAX and RM_URL to be defined by the main plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Available PNG icons for menu categories.
 *
 * Add the matching PNG files to:
 * assets/images/category-icons/
 */
function rm_get_category_icon_options() {
    return array(
        ''              => array( 'label' => 'No icon',       'file' => '', 'fa' => '' ),
        'breakfast'     => array( 'label' => 'Breakfast',     'file' => 'breakfast.png', 'fa' => 'f013' ),
        'soups'         => array( 'label' => 'Soups',         'file' => 'soup.png', 'fa' => 'f015' ),
        'appetizers'    => array( 'label' => 'Appetizers',    'file' => 'appetizers.png', 'fa' => 'f016' ),
        'salads'        => array( 'label' => 'Salads',        'file' => 'salads.png', 'fa' => 'f017' ),
        'sandwiches'    => array( 'label' => 'Sandwiches',    'file' => 'sandwiches.png', 'fa' => 'f018' ),
        'burger'        => array( 'label' => 'Burgers',       'file' => 'burger.png', 'fa' => 'f019' ),
        'soup'          => array( 'label' => 'Soup',          'file' => 'soup.png', 'fa' => 'f015' ),
        'pasta'         => array( 'label' => 'Pasta',         'file' => 'pasta.png', 'fa' => 'f01a' ),
        'pizza'         => array( 'label' => 'Pizza',         'file' => 'pizza.png', 'fa' => 'f01b' ),
        'chicken'       => array( 'label' => 'Chicken',       'file' => 'chicken.png', 'fa' => 'f01c' ),
        'meat'          => array( 'label' => 'Meat',          'file' => 'meat.png', 'fa' => 'f01d' ),
        'fajita'        => array( 'label' => 'Fajita',        'file' => 'fajita.png', 'fa' => 'f01e' ),
        'extras'        => array( 'label' => 'Extras',        'file' => 'extras.png', 'fa' => 'f01f' ),
        'yogurt-drinks' => array( 'label' => 'Yogurt Drinks', 'file' => 'yogurt-drinks.png', 'fa' => 'f020' ),
        'desserts'      => array( 'label' => 'Desserts',      'file' => 'desserts.png', 'fa' => 'f021' ),
        'ice-cream'     => array( 'label' => 'Ice Cream',     'file' => 'ice-cream.png', 'fa' => 'f022' ),
        'hot-drinks'    => array( 'label' => 'Hot Drinks',    'file' => 'hot-drinks.png', 'fa' => 'f023' ),
        'cocktails'     => array( 'label' => 'Cocktails',     'file' => 'cocktails.png', 'fa' => 'f024' ),
        'milkshake'     => array( 'label' => 'Milkshake',     'file' => 'milkshake.png', 'fa' => 'f025' ),
        'smoothie'      => array( 'label' => 'Smoothie',      'file' => 'smoothie.png', 'fa' => 'f026' ),
        'iced-drinks'   => array( 'label' => 'Iced Drinks',   'file' => 'iced-drinks.png', 'fa' => 'f027' ),
        'fresh-juices'  => array( 'label' => 'Fresh Juices',  'file' => 'fresh-juices.png', 'fa' => 'f028' ),
    );
}

/**
 * Render the visual PNG icon picker.
 */
function rm_render_category_icon_picker( $selected_icon = '' ) {
    $icons    = rm_get_category_icon_options();
    $base_url = trailingslashit( RM_URL ) . 'assets/img/category-icons/';
    ?>
<div class="rm-icon-picker">
    <input type="hidden" name="rm_category_icon" class="rm-category-icon-value"
        value="<?php echo esc_attr( $selected_icon ); ?>">

    <div class="rm-icon-grid">
        <?php foreach ( $icons as $key => $icon ) : ?>
        <?php
                $is_selected = ( (string) $selected_icon === (string) $key );
                $image_url   = ! empty( $icon['file'] ) ? $base_url . $icon['file'] : '';
                ?>
        <button type="button" class="rm-icon-option <?php echo $is_selected ? 'is-selected' : ''; ?>"
            data-icon="<?php echo esc_attr( $key ); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">
            <?php if ( $image_url ) : ?>
            <img src="<?php echo esc_url( $image_url ); ?>" alt="" class="rm-category-icon-image" loading="lazy">
            <?php else : ?>
            <span class="rm-no-icon" aria-hidden="true">×</span>
            <?php endif; ?>
            <span class="rm-icon-label"><?php echo esc_html( $icon['label'] ); ?></span>
        </button>
        <?php endforeach; ?>
    </div>
</div>
<?php
}

/** Add icon picker to the Add Category form. */
function rm_add_category_icon_field() {
    ?>
<div class="form-field">
    <label>Category Icon</label>
    <?php //rm_render_category_icon_picker(); ?>
    <p class="description">Choose an icon for this menu category.</p>
</div>
<?php
}
add_action( RM_MENU_CATEGORY_TAX . '_add_form_fields', 'rm_add_category_icon_field' );

/** Add icon picker to the Edit Category form. */
function rm_edit_category_icon_field( $term ) {
    $current_icon = get_term_meta( $term->term_id, 'rm_category_icon', true );
    ?>
<tr class="form-field">
    <th scope="row"><label>Category Icon</label></th>
    <td>
        <?php //rm_render_category_icon_picker( $current_icon ); ?>
        <p class="description">Choose an icon for this menu category.</p>
    </td>
</tr>
<?php
}
add_action( RM_MENU_CATEGORY_TAX . '_edit_form_fields', 'rm_edit_category_icon_field' );

/** Save the selected category icon key. */
function rm_save_category_icon( $term_id ) {
    if ( ! current_user_can( 'manage_categories' ) || ! isset( $_POST['rm_category_icon'] ) ) {
        return;
    }

    $icon         = sanitize_key( wp_unslash( $_POST['rm_category_icon'] ) );
    $allowed_icons = rm_get_category_icon_options();

    if ( ! array_key_exists( $icon, $allowed_icons ) ) {
        return;
    }

    update_term_meta( $term_id, 'rm_category_icon', $icon );
}
add_action( 'created_' . RM_MENU_CATEGORY_TAX, 'rm_save_category_icon' );
add_action( 'edited_' . RM_MENU_CATEGORY_TAX, 'rm_save_category_icon' );

/** Load the icon picker CSS and JavaScript only on the category admin screen. */
function rm_enqueue_category_icon_picker( $hook_suffix ) {
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

    if ( ! $screen || empty( $screen->taxonomy ) || RM_MENU_CATEGORY_TAX !== $screen->taxonomy ) {
        return;
    }

    wp_enqueue_style(
        'rm-category-icon-picker',
        trailingslashit( RM_URL ) . 'assets/css/category-icon-picker.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'rm-category-icon-picker',
        trailingslashit( RM_URL ) . 'assets/js/category-icon-picker.js',
        array(),
        '1.0.0',
        true
    );
}
add_action( 'admin_enqueue_scripts', 'rm_enqueue_category_icon_picker' );
########################################################################################
########################################################################################
########################################################################################

/**
 * Add an Icon column to the Menu Categories admin table.
 */
function rm_add_category_icon_column( $columns ) {
    $new_columns = array();

    foreach ( $columns as $key => $label ) {
        $new_columns[ $key ] = $label;

        // Place the icon after the category name.
        if ( 'cb' === $key ) {
            $new_columns['rm_category_icon'] = 'Icon';
            $new_columns['rm_category_icon'] = 'Icon';
        }
    }
    return $new_columns;
}


add_filter( 'manage_edit-' . RM_MENU_CATEGORY_TAX . '_columns', 'rm_add_category_icon_column');


/**
 * Display the category PNG in the Icon column.
 */
function rm_render_category_icon_column( $content, $column_name, $term_id ) {
    if ( 'rm_category_icon' !== $column_name ) {
        return $content;
    }

    $icon_key = get_term_meta( $term_id, 'rm_category_icon', true);

    $icons = rm_get_category_icon_options();

    if (
        ! isset( $icons[ $icon_key ] ) ||
        empty( $icons[ $icon_key ]['file'] )
    ) {
        return '<span style="color:#999;">—</span>';
    }

    $icon_url = RM_URL. 'assets/img/category-icons/'. $icons[ $icon_key ]['file'];

    return sprintf(
        '<img src="%s" alt="" width="36" height="36" style="display:block;object-fit:contain;" />',
        esc_url( $icon_url )
    );
}

add_filter('manage_' . RM_MENU_CATEGORY_TAX . '_custom_column', 'rm_render_category_icon_column', 10, 3);