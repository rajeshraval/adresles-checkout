<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Adresles_Order_Email {

	public function __construct() {
		add_action( 'woocommerce_email_order_meta', [ $this, 'add_adresles_checkout_to_email_block' ], 10, 4 );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_adresles_checkout_order_meta' ], 10, 2 );
		add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_order_details_on_thankyou' ], 10 );
		add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'display_admin_order_meta_box' ] );
	}

	/**
	 * Add Adresles and Gift checkout details to WooCommerce emails (HTML format).
	 */
	public function add_adresles_checkout_to_email_block( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$adresles_selected = get_post_meta( $order->get_id(), '_adresles_checkout_selected', true );
		$gift_selected     = get_post_meta( $order->get_id(), '_adresles_gift_selected', true );

		if ( $adresles_selected || $gift_selected ) {
			echo '<h2 style="margin-top:30px;">' . __( 'Información de Adresles / Regalo', 'adresles' ) . '</h2>';
			echo '<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;" border="1">';

			if ( $adresles_selected ) {
				echo '<tr><th style="text-align:left;">' . __( 'Confirmaré la dirección luego con Adresles', 'adresles' ) . '</th><td>✔️</td></tr>';
				echo '<tr><th style="text-align:left;">' . __( 'Teléfono Móvil', 'adresles' ) . '</th><td>' . esc_html( get_post_meta( $order->get_id(), '_adresles_mobile', true ) ) . '</td></tr>';
			}

			if ( $gift_selected ) {
				echo '<tr><th style="text-align:left;">' . __( 'Es un regalo', 'adresles' ) . '</th><td>✔️</td></tr>';
			}

			echo '</table>';
		}
	}

	/**
	 * Save Adresles and Gift fields to order meta.
	 */
	public function save_adresles_checkout_order_meta( $order_id, $data ) {
		$order = wc_get_order( $order_id );

		if ( isset( $_POST['adresles_checkout_selected'] ) && $_POST['adresles_checkout_selected'] === '1' ) {
			update_post_meta( $order_id, '_adresles_checkout_selected', 'yes' );
			update_post_meta( $order_id, '_adresles_mobile', sanitize_text_field( $_POST['adresles_mobile'] ) );
			$order->add_order_note( 'Order placed via Confirmaré la dirección luego con Adresles.' );
		}

		if ( isset( $_POST['adresles_gift_selected'] ) && $_POST['adresles_gift_selected'] === '1' ) {
			update_post_meta( $order_id, '_adresles_gift_selected', 'yes' );
		}
	}

	/**
	 * Show custom fields on thank you page and My Account > View Order.
	 */
	public function display_order_details_on_thankyou( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		if ( get_post_meta( $order->get_id(), '_adresles_checkout_selected', true ) === 'yes' ) {
			echo '<section class="adresles-order-section" style="margin-top:20px;">';
			echo '<h3>' . __( 'Adresles & Es un regalo Info', 'adresles' ) . '</h3>';
			echo '<table class="shop_table shop_table_responsive">';
		}

		if ( get_post_meta( $order->get_id(), '_adresles_checkout_selected', true ) === 'yes' ) {
			echo '<tr><th>' . __( 'Confirmaré la dirección luego con Adresles', 'adresles' ) . '</th><td>✔️</td></tr>';
			echo '<tr><th>' . __( 'Teléfono Móvil', 'adresles' ) . '</th><td>' . esc_html( get_post_meta( $order->get_id(), '_adresles_mobile', true ) ) . '</td></tr>';
		}

		if ( get_post_meta( $order->get_id(), '_adresles_gift_selected', true ) === 'yes' ) {
			echo '<tr><th>' . __( 'Es un regalo', 'adresles' ) . '</th><td>✔️</td></tr>';
		}

		if ( get_post_meta( $order->get_id(), '_adresles_checkout_selected', true ) === 'yes' ) {
			echo '</table>';
			echo '</section>';
		}
	}

	/**
	 * Show in WooCommerce Admin Order Panel.
	 */
	public function display_admin_order_meta_box( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		echo '<div class="adresles-admin-box" style="padding:10px;border:1px solid #ccc;margin:250px 0px 0px 0px;">';
		echo '<h3>' . __( 'Adresles & Es un regalo Info', 'adresles' ) . '</h3>';

		if ( get_post_meta( $order->get_id(), '_adresles_checkout_selected', true ) === 'yes' ) {
			echo '<p><strong>Confirmaré la dirección luego con Adresles:</strong> ✔️</p>';
			echo '<p><strong>Mobile:</strong> ' . esc_html( get_post_meta( $order->get_id(), '_adresles_mobile', true ) ) . '</p>';
		}

		if ( get_post_meta( $order->get_id(), '_adresles_gift_selected', true ) === 'yes' ) {
			echo '<p><strong>Es un regalo:</strong> ✔️</p>';
		}

		echo '</div>';
	}
}
