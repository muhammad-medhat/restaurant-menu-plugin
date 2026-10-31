<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_filter( 'acf/load_field/name=fa_icon', function ( $field ) {

    if ( empty( $field['choices'] ) ) {
        return $field;
    }

    foreach ( $field['choices'] as $key => $value ) {
        // Keep the saved value as the Font Awesome class.
        $field['choices'][ $key ] = $value;
    }

    return $field;
});


add_action( 'acf/input/admin_enqueue_scripts', function () {
    wp_enqueue_script(
        'rm-acf-fa-icon',
        RM_URL . 'assets/js/acf-fa-icon.js',
        array( 'jquery', 'acf-input', 'select2' ),
        '1.0.0',
        true
    );
});