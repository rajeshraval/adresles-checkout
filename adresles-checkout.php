<?php

/**
 * Plugin Name: Adresles Checkout
 * Description: Adds addressless checkout and optional gift details to WooCommerce checkout.
 * Version: 1.0.0
 * Author: Rajesh Raval
 * Text Domain: adresles-checkout
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Load core classes
require_once plugin_dir_path(__FILE__) . 'includes/class-adresles-rest-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-adresles-frontend.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-adresles-order-email.php'; // Include the new order/email class
require_once plugin_dir_path(__FILE__) . 'includes/class-adresles-woo-flow.php';   // Include the new order/email class

define('API_BASE_URL', 'https://5uerf2f2o9.execute-api.us-east-1.amazonaws.com/staging');
define('BASE_URL', plugin_dir_url(__FILE__) );

// Init plugin logic
new Adresles_Checkout_Plugin();
new Adresles_Checkout_Frontend();
new Adresles_Order_Email();   // Initialize the order and email handling class
new Adresles_Woo_Flow();
