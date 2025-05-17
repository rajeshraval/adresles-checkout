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
            'dashicons-location', // Icon
            56                    // Position
        );
    }


	/**
     * Render the plugin registration form.
     */
    public function render_setup_form() {
        $saved = get_option( 'adresles_plugin_info', [] );
    
        // Handle submission
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
    
            if ( is_wp_error( $response ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html( $response->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Plugin registered successfully.', 'adresles-checkout' ) . '</p></div>';
                update_option( 'adresles_plugin_info', $data );
                $saved = $data;
            }
        }
    
        // Prefill values
        $plugin_id = ! empty( $saved['id_plugin'] ) ? $saved['id_plugin'] : wp_generate_uuid4();
        $name      = $saved['name'] ?? get_bloginfo( 'name' );
        $email     = $saved['email'] ?? get_option( 'admin_email' );
        $phone     = $saved['phone'] ?? '';
        $password  = $saved['password'] ?? wp_generate_password( 12, true );
        ?>
    
        <div class="wrap">
            <h1><?php esc_html_e( 'Adresles Plugin Registration', 'adresles-checkout' ); ?></h1>
    
            <?php if ( ! empty( $saved['registered_at'] ) ) : ?>
                <p style="color: green;">
                    <?php esc_html_e( 'Plugin registered on:', 'adresles-checkout' ); ?>
                    <strong><?php echo esc_html( $saved['registered_at'] ); ?></strong>
                </p>
            <?php endif; ?>
    
            <form method="post">
                <?php wp_nonce_field( 'adresles_register_plugin', 'adresles_register_nonce' ); ?>
    
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="id_plugin"><?php esc_html_e( 'Plugin ID', 'adresles-checkout' ); ?></label></th>
                        <td><input type="text" id="id_plugin" name="id_plugin" class="regular-text" value="<?php echo esc_attr( $plugin_id ); ?>" required readonly></td>
                    </tr>
    
                    <tr>
                        <th scope="row"><label for="name"><?php esc_html_e( 'Name', 'adresles-checkout' ); ?></label></th>
                        <td><input type="text" id="name" name="name" class="regular-text" value="<?php echo esc_attr( $name ); ?>" required></td>
                    </tr>
    
                    <tr>
                        <th scope="row"><label for="email"><?php esc_html_e( 'Email', 'adresles-checkout' ); ?></label></th>
                        <td><input type="email" id="email" name="email" class="regular-text" value="<?php echo esc_attr( $email ); ?>" required></td>
                    </tr>
    
                    <tr>
                        <th scope="row"><label for="phone"><?php esc_html_e( 'Phone', 'adresles-checkout' ); ?></label></th>
                        <td><input type="text" id="phone" name="phone" class="regular-text" value="<?php echo esc_attr( $phone ); ?>" placeholder="Optional"></td>
                    </tr>
    
                    <tr>
                        <th scope="row"><label for="password"><?php esc_html_e( 'Password', 'adresles-checkout' ); ?></label></th>
                        <td><input type="text" id="password" name="password" class="regular-text" value="<?php echo esc_attr( $password ); ?>" required></td>
                    </tr>
                </table>
    
                <?php submit_button( __( 'Register / Update Plugin', 'adresles-checkout' ) ); ?>
            </form>
        </div>
    
        <?php
    }    

	/**
     * Register the plugin with Adresles API.
     *
     * @param array $data Registration data.
     * @return array|WP_Error
     */
    public function register_plugin( $data ) {
        $base_url = 'https://5uerf2f2o9.execute-api.us-east-1.amazonaws.com/staging/createEcommerceUser';

        $query_args = [
            'id_plugin'    => $data['id_plugin'],
            'name_plugin'  => 'Adresles Plugin',
            'type_plugin'  => 'Woocomerce',
            'url_callback' => '/login',
        ];

        $url = add_query_arg( $query_args, $base_url );

        $response = wp_remote_post(
            $url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode( $data ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'adresles_api_error',
                __( 'Plugin registration failed.', 'adresles-checkout' ),
                $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code ) {
            return new WP_Error(
                'adresles_api_error',
                __( $body['message'], 'adresles-checkout' ),
                $body
            );
        }

        // Save for future steps.
        update_option( 'adresles_plugin_id', $data['id_plugin'] );
        update_option( 'adresles_plugin_registered_data', $body );

        // NEW: Get appId and secret
        $this->get_plugin_keys( $data['id_plugin'] );

        return $body;
    }
    
    /**
     * Get plugin App ID and Secret from API after registration.
     *
     * @param string $id_plugin The plugin ID used during registration.
     * @return array|WP_Error
     */
    public function get_plugin_keys( $id_plugin ) {
        $url  = 'https://5uerf2f2o9.execute-api.us-east-1.amazonaws.com/staging/getConfig';
        $body = [
            'idPlugin' => $id_plugin,
        ];

        $response = wp_remote_post(
            $url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'adresles_config_error',
                __( 'Failed to retrieve plugin secrets.', 'adresles-checkout' ),
                $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code || empty( $data['success'] ) ) {
            return new WP_Error(
                'adresles_config_error',
                __( 'Unexpected response from getConfig API.', 'adresles-checkout' ),
                $data
            );
        }

        // Save App ID and Secret.
        update_option( 'adresles_plugin_keys', [
            'app_id' => $data['appId'],
            'secret' => $data['secret'],
        ] );

        return $data;
    }

    /**
     * Generate and cache JWT token.
     *
     * @return string|WP_Error JWT token or WP_Error on failure.
     */
    public function get_jwt_token() {

        // Get appId and secret from stored option
        $keys = get_option( 'adresles_plugin_keys', [] );
        if ( empty( $keys['app_id'] ) || empty( $keys['secret'] ) ) {
            return new WP_Error( 'missing_keys', __( 'Plugin keys not found.', 'adresles-checkout' ) );
        }

        $url  = 'https://5uerf2f2o9.execute-api.us-east-1.amazonaws.com/staging/getToken';
        $body = [
            'appId'  => $keys['app_id'],
            'secret' => $keys['secret'],
        ];

        $response = wp_remote_post(
            $url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code || empty( $data['token'] ) ) {
            return new WP_Error( 'token_error', __( 'Failed to get JWT token.', 'adresles-checkout' ), $data );
        }

        // Cache token for 50 minutes (assuming token lasts 1 hour)
        set_transient( 'adresles_jwt_token', $data['token'], 50 * MINUTE_IN_SECONDS );

        return $data['token'];
    }

    /**
     * Get user data by phone using JWT token.
     *
     * @param string $phone Phone number to search.
     * @return array|WP_Error User data or error.
     */
    public function get_user_by_phone( $phone ) {
        if ( empty( $phone ) ) {
            return new WP_Error( 'no_phone', __( 'Phone number is required.', 'adresles-checkout' ) );
        }

        $token = $this->get_jwt_token();
        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $url  = 'https://5uerf2f2o9.execute-api.us-east-1.amazonaws.com/staging/getUser';
        $body = [
            'phone' => $phone,
        ];

        $response = wp_remote_post(
            $url,
            [
                'timeout' => 15, // 👈 increased timeout
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'body' => wp_json_encode( $body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code ) {
            return new WP_Error( 'api_error', __( 'API error when fetching user.', 'adresles-checkout' ), $data );
        }

        if ( empty( $data ) ) {
            return new WP_Error( 'user_not_found', __( 'User not found.', 'adresles-checkout' ) );
        }

        return $data;
    }

    /**
     * Register AJAX handlers for generating token and fetching user.
     */
    public function init_rest_api() {
        register_rest_route(
            'adresles/v1',
            '/generate-token/',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'handle_generate_token' ],
                'permission_callback' => '__return_true', // ✅ allow public access
            ]
        );

        register_rest_route(
            'adresles/v1',
            '/get-user-by-phone/',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_get_user_by_phone' ],
                'permission_callback' => '__return_true', // ✅ allow public access
                'args'                => [
                    'phone' => [
                        'required'          => true,
                        'validate_callback' => function ( $param ) {
                            return is_string( $param ) && strlen( $param ) > 5;
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * Handle token generation REST request.
     *
     * @return WP_REST_Response
     */
    public function handle_generate_token() {
        $token = $this->get_jwt_token();

        if ( is_wp_error( $token ) ) {
            return new WP_REST_Response( [ 'error' => $token->get_error_message() ], 500 );
        }

        return new WP_REST_Response( [ 'token' => $token ], 200 );
    }

    /**
     * Handle get user by phone REST request.
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response
     */
    public function handle_get_user_by_phone( WP_REST_Request $request ) {
        $phone = $request->get_param( 'phone' );
        $data  = $this->get_user_by_phone( $phone );

        if ( is_wp_error( $data ) ) {
            return new WP_REST_Response( [ 'error' => $data->get_error_message() ], 404 );
        }

        return new WP_REST_Response( $data, 200 );
    }

}

