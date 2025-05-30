<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class for Adresles Checkout integration.
 */
class Adresles_Checkout_Plugin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'rest_api_init', [ $this, 'init_rest_api' ] );
	}

	/**
	 * Register top-level menu in WordPress admin.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Adresles Plugin', 'adresles-checkout' ),
			__( 'Adresles Plugin', 'adresles-checkout' ),
			'manage_options',
			'adresles-setup',
			[ $this, 'render_setup_form' ],
			'dashicons-location',
			56
		);
	}

	private function get_api_base_url() {
		$mode = get_option( 'adresles_api_mode', 'staging' );
		return $mode === 'production'
			? 'https://api.adresles.com/prod' // hypothetical production URL
			: 'https://5uerf2f2o9.execute-api.us-east-1.amazonaws.com/staging';
	}


	public function render_setup_form() {
		$saved          = get_option( 'adresles_plugin_info', [] );
		$field_mappings = get_option( 'adresles_field_mapping', [] );

		$plugin_id = ! empty( $saved['id_plugin'] ) ? $saved['id_plugin'] : wp_generate_uuid4();
		$name      = $saved['name'] ?? get_bloginfo( 'name' );
		$email     = $saved['email'] ?? get_option( 'admin_email' );
		$phone     = $saved['phone'] ?? '';
		$password  = $saved['password'] ?? wp_generate_password( 12, true );

		if (
			isset( $_POST['adresles_register_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['adresles_register_nonce'] ) ), 'adresles_register_plugin' )
		) {
			$data = [
				'id_plugin'      => sanitize_text_field( $_POST['id_plugin'] ),
				'name'           => sanitize_text_field( $_POST['name'] ),
				'phone'          => sanitize_text_field( $_POST['phone'] ),
				'email'          => sanitize_email( $_POST['email'] ),
				'password'       => sanitize_text_field( $_POST['password'] ),
				'enrollmentDate' => date( 'Y-m-d' ),
				'state'          => true,
				'rol'            => 'Administrador',
				'position'       => 'Manager',
				'registered_at'  => current_time( 'mysql' ),
			];

			$response = $this->register_plugin( $data );
			if ( ! is_wp_error( $response ) ) {
				update_option( 'adresles_plugin_info', $data );
				$saved = $data;
				echo '<div class="notice notice-success"><p>Registration successful.</p></div>';
			} else {
				if ( $response->get_error_message() == 'A plugin with this idPlugin already exists' ) {
					echo '<div class="notice notice-success"><p>Setting Saved.</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>' . esc_html( $response->get_error_message() ) . '</p></div>';
				}
			}

			// Save field mapping
			if ( ! empty( $_POST['field_mapping'] ) && is_array( $_POST['field_mapping'] ) ) {
				$clean = [];
				foreach ( $_POST['field_mapping'] as $k => $v ) {
					$clean[ sanitize_text_field( $k ) ] = sanitize_text_field( $v );
				}
				update_option( 'adresles_field_mapping', $clean );
				update_option( 'is_adresles_field_mapping_done', true );
				$field_mappings = $clean;
			}

		}else{
			$is_secret_code_avl = get_option('adresles_plugin_keys', false);

			if( ! $is_secret_code_avl){

				$data = [
				'id_plugin'      => $plugin_id,
				'name'           => $name,
				'phone'          => $phone,
				'email'          => $email,
				'password'       => $password,
				'enrollmentDate' => date( 'Y-m-d' ),
				'state'          => true,
				'rol'            => 'Administrador',
				'position'       => 'Manager',
				'registered_at'  => current_time( 'mysql' ),
				];

				$response = $this->register_plugin( $data );
				if ( ! is_wp_error( $response ) ) {
					update_option( 'adresles_plugin_info', $data );
					$saved = $data;
					echo '<div class="notice notice-success"><p>Registration successful.</p></div>';
				} else {
					if ( $response->get_error_message() == 'A plugin with this idPlugin already exists' ) {
						echo '<div class="notice notice-success"><p>Setting Saved.</p></div>';
					} else {
						echo '<div class="notice notice-error"><p>' . esc_html( $response->get_error_message() ) . '</p></div>';
					}
				}

			}			
		}		

		$api_mode = get_option( 'adresles_api_mode', 'staging' );

		if ( isset( $_POST['adresles_api_mode'] ) ) {
			update_option( 'adresles_api_mode', sanitize_text_field( $_POST['adresles_api_mode'] ) );
			$api_mode = sanitize_text_field( $_POST['adresles_api_mode'] );
		}		

		$api_response = $this->get_keys_for_configuration();

		
		$api_fields = ( ! is_wp_error( $api_response ) && ! empty( $api_response['userDataKeys'] ) )
			?  $api_response['userDataKeys']
			: [];
		
		$wc_fields = [];
		if ( class_exists( 'WC_Checkout' ) ) {
			$checkout = WC()->checkout();
			foreach ( $checkout->get_checkout_fields() as $section => $fields ) {
				foreach ( $fields as $key => $props ) {
					$wc_fields[ $key ] = $props['label'] ?? $key;
				}
			}
		}

		require plugin_dir_path( __FILE__ ) . 'admin/configuration-settings.php';
	}

	/**
	 * Register the plugin with Adresles API.
	 */
	public function register_plugin( $data ) {
		$url = add_query_arg(
			[
				'id_plugin'    => $data['id_plugin'],
				'name_plugin'  => 'Adresles Plugin',
				'type_plugin'  => 'Woocomerce',
				'url_callback' => '/login',
			],
			$this->get_api_base_url() . '/createEcommerceUser'
		);

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $data ),
		] );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'adresles_api_error', __( 'Plugin registration failed.', 'adresles-checkout' ), $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			return new WP_Error( 'adresles_api_error', __( $body['message'], 'adresles-checkout' ), $body );
		}

		update_option( 'adresles_plugin_id', $data['id_plugin'] );
		update_option( 'adresles_plugin_registered_data', $body );

		$this->get_plugin_keys( $data['id_plugin'] );

		return $body;
	}

	/**
	 * Get plugin App ID and Secret from API after registration.
	 */
	public function get_plugin_keys( $id_plugin ) {
		$response = wp_remote_post(
			$this->get_api_base_url() . '/getConfig',
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( [ 'idPlugin' => $id_plugin ] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'adresles_config_error', __( 'Failed to retrieve plugin secrets.', 'adresles-checkout' ), $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 || empty( $data['success'] ) ) {
			return new WP_Error( 'adresles_config_error', __( 'Unexpected response from getConfig API.', 'adresles-checkout' ), $data );
		}

		update_option( 'adresles_plugin_keys', [
			'app_id' => $data['appId'],
			'secret' => $data['secret'],
		] );

		return $data;
	}

	/**
	 * Generate and cache JWT token.
	 */
	public function get_jwt_token() {
		$cached_token = get_transient( 'adresles_jwt_token' );
		if ( $cached_token ) {
			return $cached_token;
		}

		$keys = get_option( 'adresles_plugin_keys', [] );

		if ( empty( $keys['app_id'] ) || empty( $keys['secret'] ) ) {
			return new WP_Error( 'missing_keys', __( 'Plugin keys not found.', 'adresles-checkout' ) );
		}

		$response = wp_remote_post(
			$this->get_api_base_url() . '/getToken',
			[
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( [
					'appId'  => $keys['app_id'],
					'secret' => $keys['secret'],
				] ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 || empty( $data['token'] ) ) {
			return new WP_Error( 'token_error', __( 'Failed to get JWT token.', 'adresles-checkout' ), $data );
		}

		set_transient( 'adresles_jwt_token', $data['token'], 2 * MINUTE_IN_SECONDS );

		return $data['token'];
	}

	/**
	 * Get Keys for configuration.
	 */
	public function get_keys_for_configuration() {
		$token = $this->get_jwt_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_remote_post(
			$this->get_api_base_url() . '/getKeys',
			[
				'timeout' => 15,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				]
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return new WP_Error( 'api_error', __( 'API error when fetching user.', 'adresles-checkout' ), $data );
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'user_not_found', __( 'User not found.', 'adresles-checkout' ) );
		}

		return $data;
	}

	/**
	 * Get user data by phone using JWT token.
	 */
	public function get_user_by_phone( $request ) {
		$token = $this->get_jwt_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$response = wp_remote_post(
			$this->get_api_base_url() . '/getUser',
			[
				'timeout' => 15,
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				],
				'body' => wp_json_encode( $request ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return new WP_Error( 'api_error', __( 'API error when fetching user.', 'adresles-checkout' ), $data );
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'user_not_found', __( 'User not found.', 'adresles-checkout' ) );
		}

		return $data;
	}

	/**
	 * Register REST API endpoints.
	 */
	public function init_rest_api() {
		register_rest_route( 'adresles/v1', '/generate-token/', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_generate_token' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( 'adresles/v1', '/get-user-by-phone/', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_get_user_by_phone' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'phone' => [
					'required'          => true,
					'validate_callback' => fn( $param ) => is_string( $param ) && strlen( $param ) > 5,
				],
			],
		] );
	}

	/**
	 * Handle token generation.
	 */
	public function handle_generate_token() {
		$token = $this->get_jwt_token();

		if ( is_wp_error( $token ) ) {
			return new WP_REST_Response( [ 'error' => $token->get_error_message() ], 500 );
		}

		return new WP_REST_Response( [ 'token' => $token ], 200 );
	}

	/**
	 * Handle user lookup by phone.
	 */
	public function handle_get_user_by_phone( WP_REST_Request $request ) {
		$request_param = $request->get_json_params();
		$data          = $this->get_user_by_phone( $request_param );

		if ( is_wp_error( $data ) ) {
			return new WP_REST_Response( [ 'error' => $data->get_error_message() ], 500 );
		}

		return new WP_REST_Response( $data, 200 );
	}
}
