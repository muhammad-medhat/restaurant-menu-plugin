<?php
/**
 * Category image for the Restaurant Menu plugin.
 * Requires RM_MENU_CATEGORY_TAX and RM_URL to be defined by the main plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// $acf=['name_ar'=>'Arabic Name','name_en'=>'English Name','desc_ar'=>'Arabic Description','desc_en'=>'English Description'];
################################################################

/**
 * ACF fields to display in the Menu Categories table.
 *
 * Array key   = ACF field name.
 * Array value = Column heading.
 */

/**
 * Configure ACF fields displayed in the category table.
 */
function rm_category_admin_columns_config() {
    return [
        'fa_icon'    => 'Icon',
        'name_ar'    => 'Arabic Name',
        'name_en'    => 'English Name',
        'desc_ar'    => 'Arabic Description',
        'desc_en'    => 'English Description',
        'is_compact' => 'Compact',
    ];
}

/**
 * Register the configured columns after the category name.
 */
add_filter( 'manage_edit-' . RM_MENU_CATEGORY_TAX . '_columns', function ( $columns ) {

        $new_columns = [];

        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;

            if ( 'name' === $key ) {
                foreach ( rm_category_admin_columns_config() as $field => $field_label ) {
                    $new_columns[ $field ] = $field_label;
                }
            }
        }

        return $new_columns;
    }
);

/**
 * Display the configured ACF fields.
 */
add_filter( 'manage_' . RM_MENU_CATEGORY_TAX . '_custom_column', function ( $content, $column_name, $term_id ) {

        $fields = rm_category_admin_columns_config();

        // Leave WordPress's other columns unchanged.
        if ( ! isset( $fields[ $column_name ] ) ) {
            return $content;
        }

        // ACF taxonomy term fields use the term_{$term_id} format.
        $value = get_field( $column_name, 'term_' . $term_id );

        if ( $value === null || $value === false || $value === '' ) {
            return '<span style="color:#999;">—</span>';
        }

        // Font Awesome icon.
        if ( 'fa_icon' === $column_name ) {
            $classes = preg_split( '/\s+/', trim( (string) $value ) );
            $classes = array_filter( $classes, 'sanitize_html_class' );

            return '<i class="' . esc_attr( implode( ' ', $classes ) ) .
                '" aria-hidden="true"></i> <code>' .
                esc_html( $value ) . '</code>';
        }

        // ACF true/false field.
        if ( 'is_compact' === $column_name ) {
            return $value ? 'Yes' : 'No';
        }

        // Descriptions.
        if ( strpos( $column_name, 'desc_' ) === 0 ) {
            return esc_html(
                wp_trim_words( wp_strip_all_tags( $value ), 10 )
            );
        }

        // Regular text values.
        if ( is_scalar( $value ) ) {
            return esc_html( (string) $value );
        }

        return '<span style="color:#999;">—</span>';
    },
    10,
    3
);