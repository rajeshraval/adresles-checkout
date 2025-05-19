<?php

if (!defined('ABSPATH')) {
    exit;
}

class Adresles_Woo_Flow
{
    public function __construct()
    {
        add_action('woocommerce_before_checkout_billing_form', [$this, 'add_adresles_checkout_fields']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_adresles_checkout_fields']);
        // add_filter('woocommerce_checkout_fields', [$this, 'maybe_make_billing_fields_optional']);
        add_action('woocommerce_checkout_process', [$this, 'validate_adresles_gift_fields']);
    }

    public function add_adresles_checkout_fields()
    {
        echo '<div id="adresles_checkout_wrapper">';

        // Black background container
        echo '<div class="adresles-checkout-container" style="position:relative;">';
        echo '<img id="right-image" src="' . plugin_dir_url(dirname(__FILE__)) . 'assets/icon-label.png" alt="Adresles Info" style="width: 38px; vertical-align: middle; position:absolute; right: 10px;top: 20px;">';

        // Adresles checkbox
        woocommerce_form_field('adresles_checkout_selected', [
            'type'  => 'checkbox',
            'class' => ['form-row-wide'],
            'label' => __('Confirmaré la dirección luego con Adresles', 'adresles'),
        ]);

        // Adresles Mobile (Required)
        woocommerce_form_field('adresles_mobile', [
            'autocomplete'      => 'off',
            'type'              => 'tel',
            'class'             => ['form-row-wide'],
            'label'             => __('Teléfono Móvil', 'adresles'),
            'custom_attributes' => ['disabled' => 'disabled'],
            'required'          => true,
        ]);

        echo '<div class="adresles-notice" style="margin-top:10px; color:red; display:none;">Dirección no encontrada. <a href="javascript:void(0);" class="adresles-register-link">Haz clic aquí para registrarte</a></div>';
        echo '<div class="temp-msg-div" style="margin-top:10px; color:red; display:none;"></div>';
        echo '</div>'; // Close black background

        // Gift Section (Initially visible but disabled)
        echo '<div id="adresles_gift_section" class="adresles-gift-section" style="position:relative;">';
        echo '<img id="right-image" src="' . plugin_dir_url(dirname(__FILE__)) . 'assets/icon-label.png" alt="Adresles Info" style="width: 38px; vertical-align: middle; position:absolute; right: 10px;top: 20px;">';

        woocommerce_form_field('adresles_gift_selected', [
            'type'              => 'checkbox',
            'class'             => ['form-row-wide'],
            'label'             => __('Es un regalo', 'adresles'),
            'custom_attributes' => ['disabled' => 'disabled'],
        ]);

        echo '<div id="adresles_gift_shippping_section">';

        echo "</div>";

        echo '</div>'; // End Gift section
        echo '</div>'; // End Wrapper
    }

    public function save_adresles_checkout_fields($order_id)
    {
        if (!empty($_POST['adresles_checkout_selected'])) {
            update_post_meta($order_id, 'adresles_checkout_selected', 'yes');
            update_post_meta($order_id, 'adresles_mobile', sanitize_text_field($_POST['adresles_mobile']));
        }

        if (!empty($_POST['adresles_gift_selected'])) {
            update_post_meta($order_id, 'adresles_gift_selected', 'yes');
            update_post_meta($order_id, 'gift_name', sanitize_text_field($_POST['gift_name']));
            update_post_meta($order_id, 'gift_lastname', sanitize_text_field($_POST['gift_lastname']));
            update_post_meta($order_id, 'gift_phone', sanitize_text_field($_POST['gift_phone']));
            update_post_meta($order_id, 'gift_note', sanitize_textarea_field($_POST['gift_note']));
        }
    }

    // public function maybe_make_billing_fields_optional($fields)
    // {
    //     foreach (['billing', 'shipping'] as $section) {
    //         if (isset($fields[$section])) {
    //             foreach ($fields[$section] as $key => $field) {
    //                 $fields[$section][$key]['required'] = false;
    //             }
    //         }
    //     }
    //     return $fields;
    // }

    public function validate_adresles_gift_fields()
    {
        if (!empty($_POST['adresles_checkout_selected'])) {
            $phone = sanitize_text_field($_POST['adresles_mobile']);

            if (empty($phone)) {
                wc_add_notice(__('Teléfono Móvil es obligatorio para Adresles.', 'adresles'), 'error');
            }
        }
    }
}
