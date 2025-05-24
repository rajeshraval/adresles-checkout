<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Adresles_Checkout_Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function enqueue_assets() {
        if ( is_checkout() ) {

            wp_enqueue_style(
                'adresles-checkout-style',
                plugin_dir_url( __DIR__ ) . 'assets/css/adresles-checkout.css',
                [],
                '1.0.0'
            );

            wp_enqueue_script(
                'adresles-checkout-js',
                plugin_dir_url( __DIR__ ) . 'assets/js/adresles-checkout.js',
                [ 'jquery' ],
                '1.0.0',
                true
            );

            $keys   = get_option( 'adresles_plugin_keys', [] );
            $app_id = isset( $keys['app_id'] ) ? $keys['app_id'] : '';
            $secret = isset( $keys['secret'] ) ? $keys['secret'] : '';

            wp_localize_script( 'adresles-checkout-js', 'adreslesData', [
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'adresles_nonce' ),
                'register_url' => 'https://app.stg.adresles.com/register?url_callback='.wc_get_checkout_url(),
                'api_path'     => rest_url(),
                'plugin_dir_url' => plugin_dir_url( __DIR__ ),     
            ] );
        }
    }

    public function enqueue_admin_assets(){
        wp_enqueue_style(
            'adresles-admin-style',
            plugin_dir_url( __DIR__ ) . 'assets/css/adresles-admin.css',
            [],
            '1.0.0'
        );
    }
}
