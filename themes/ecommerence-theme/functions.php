<?php
/**
 * My Ecommerce Theme Functions.
 *
 * Sets up theme defaults and registers support for various WordPress features.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Theme setup.
 */
function myecommerce_theme_setup() {
    // Make theme available for translation.
    load_theme_textdomain( 'myecommerce-theme', get_template_directory() . '/languages' );

    // Add support for document title tag.
    add_theme_support( 'title-tag' );

    // Enable support for post thumbnails.
    add_theme_support( 'post-thumbnails' );

    // Register navigation menu.
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'myecommerce-theme' ),
    ) );

    // WooCommerce support.
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'myecommerce_theme_setup' );

/**
 * Enqueue styles and scripts.
 */
function myecommerce_theme_enqueue_assets() {
    // Enqueue the main stylesheet.
    wp_enqueue_style( 'myecommerce-style', get_stylesheet_uri() );

    // Enqueue additional CSS from assets folder.
    wp_enqueue_style( 'myecommerce-main-css', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0', 'all' );

    // Enqueue custom JS.
    wp_enqueue_script( 'myecommerce-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0', true );
}
add_action( 'wp_enqueue_scripts', 'myecommerce_theme_enqueue_assets' );

