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

        add_action( 'wp_ajax_get_cart_summary', [ $this, 'get_cart_summary' ] );

        add_action( 'wp_ajax_nopriv_get_cart_summary', [ $this, 'get_cart_summary' ] );

        add_filter( 'woocommerce_checkout_fields', [ $this, 'add_shipping_phone_field' ] );

        add_action( 'woocommerce_admin_order_data_after_shipping_address', [ $this, 'show_shipping_phone_admin' ] );

        add_action( 'woocommerce_email_after_order_table', [ $this, 'show_shipping_phone_email' ], 20, 4 );

        add_action( 'woocommerce_order_details_after_order_table', [ $this, 'show_shipping_phone_thank_you' ] );



    }



    /**

     * Add "shipping_phone" field to shipping section.

     */

    public function add_shipping_phone_field( $fields ) {

        $fields['shipping']['shipping_phone'] = [

            'label'       => __( 'Teléfono de Envío', 'adresles' ),

            'required'    => true,

            'class'       => [ 'form-row-wide' ],

            'type'        => 'tel',

            'priority'    => 120,

            'autocomplete'=> 'tel',

        ];

        return $fields;

    }



    /**

     * Show shipping phone in admin order details.

     */

    public function show_shipping_phone_admin( $order ) {

        $shipping_phone = $order->get_meta( 'shipping_phone' );

        if ( $shipping_phone ) {

            echo '<p><strong>' . __( 'Teléfono de Envío:', 'adresles' ) . '</strong> ' . esc_html( $shipping_phone ) . '</p>';

        }

    }



    /**

     * Show shipping phone in customer/admin emails.

     */

    public function show_shipping_phone_email( $order, $sent_to_admin, $plain_text, $email ) {

        $shipping_phone = $order->get_meta( 'shipping_phone' );

        if ( $shipping_phone ) {

            echo '<p><strong>' . __( 'Teléfono de Envío:', 'adresles' ) . '</strong> ' . esc_html( $shipping_phone ) . '</p>';

        }

    }



    /**

     * Show shipping phone on the Thank You page.

     */

    public function show_shipping_phone_thank_you( $order ) {

        $shipping_phone = $order->get_meta( 'shipping_phone' );

        if ( $shipping_phone ) {

            echo '<p><strong>' . __( 'Teléfono de Envío:', 'adresles' ) . '</strong> ' . esc_html( $shipping_phone ) . '</p>';

        }

    }



    /**

     * AJAX handler to return WooCommerce cart summary for Adresles.

     */

    public function get_cart_summary() {
        if ( ! WC()->cart ) {
            wp_send_json_error( [ 'message' => 'Cart is not available.' ] );
        }

        $cart        = WC()->cart;
        $cart_items  = $cart->get_cart();
        $order_total = floatval( $cart->get_total( 'edit' ) );
        $order_id    = uniqid();

        $order_products = [];

        foreach ( $cart_items as $item ) {
            $product = $item['data'];

            if ( ! $product instanceof WC_Product ) {
                continue;
            }

            // Get product ID from the main product if it's a variation
            $main_product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

            // Get category names
            $category_names = [];
            $terms = get_the_terms( $main_product_id, 'product_cat' );
            if ( $terms && ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    $category_names[] = $term->name;
                }
            }

            $order_products[] = [
                'name'     => $product->get_name(),
                'price'    => floatval( $product->get_price() ),
                'quantity' => intval( $item['quantity'] ),
                'category' => implode( ', ', $category_names ),
            ];
        }

        $response = [
            'order_id'       => $order_id,
            'order_amount'   => $order_total,
            'order_products' => $order_products,
        ];

        wp_send_json_success( $response );
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

            // 'label' => __('Usaré Adresles', 'adresles'),
            'label' => __('Usaré Adresles', 'adresles') . 
            ' <span class="adresles-tooltip-wrapper">
                <span class="adresles-tooltip-icon">
                <img src="' . plugin_dir_url(dirname(__FILE__)) . '/assets/question-icon.png" alt="info" />
                </span>
                <span class="adresles-tooltip-text">Ahora `ecommerce` te facilita el envío con Adresles. No tienes que rellenar ninguna dirección. Haz tu pedido ahora. Luego por Whatsapp podrás indicar la dirección de entrega.</span>
            </span>',

        ]);



        // Adresles Mobile (Required)

        echo '<div id="adresles_mobile_field_wapper" style="display:none;">';

        woocommerce_form_field('adresles_mobile', [

            'autocomplete'      => 'off',

            'type'              => 'tel',

            'class'             => ['form-row-wide'],

            'label'             => __('Teléfono Móvil', 'adresles'),

            'custom_attributes' => ['disabled' => 'disabled'],

            'required'          => true,

        ]);

        echo '</div>';



        echo '<div class="adresles-notice" style="margin-top:10px; color:red; display:none;">Dirección no encontrada. <a href="javascript:void(0);" class="adresles-register-link">Haz clic aquí para registrarte</a></div>';

        echo '<div class="temp-msg-div" style="margin-top:10px; color:red; display:none;"></div>';

        echo '</div>'; // Close black background



        // Gift Section (Initially visible but disabled)

        echo '<div id="adresles_gift_section" class="adresles-gift-section" style="position:relative;display:none">';

        echo '<img id="right-image" src="' . plugin_dir_url(dirname(__FILE__)) . 'assets/icon-label.png" alt="Adresles Info" style="width: 38px; vertical-align: middle; position:absolute; right: 10px;top: 20px;">';



        woocommerce_form_field('adresles_gift_selected', [

            'type'              => 'checkbox',

            'class'             => ['form-row-wide'],

            // 'label'             => __('Es un regalo', 'adresles'),
            'label' => __('Es un regalo', 'adresles') . 
            ' <span class="adresles-tooltip-wrapper">
                <span class="adresles-tooltip-icon">
                <img src="' . plugin_dir_url(dirname(__FILE__)) . '/assets/question-icon.png" alt="info" />
                </span>
                <span class="adresles-tooltip-text">Si no sabes o no tienes claro la dirección del receptor. Clica aquí para que ADRESLES la obtenga por ti.</span>
            </span>',

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

