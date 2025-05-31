<?php defined( 'ABSPATH' ) || exit; ?>

<div class="wrap plugin-entry">
	<img src="<?php echo BASE_URL . 'assets/adresles-logo.png'; ?>" alt="Adresles Logo" style="max-height: 60px; margin-bottom: 10px;">
	<h1><?php esc_html_e( 'Welcome to Adresles Plugin Registration', 'adresles-checkout' ); ?></h1>

	<?php if ( ! empty( $saved['registered_at'] ) ) : ?>
		<p style="color: green;">
			<?php esc_html_e( 'Plugin registered on:', 'adresles-checkout' ); ?>
			<strong><?php echo esc_html( $saved['registered_at'] ); ?></strong>
		</p>
	<?php endif; ?>

	<p><?php esc_html_e( 'Adresles es la solución que simplifica la compra online eliminando las barreras del checkout tradicional...', 'adresles-checkout' ); ?></p>

	<p><?php esc_html_e( 'Simplifica el checkout, multiplica tus resultados.', 'adresles-checkout' ); ?></p>

	<h2><?php esc_html_e( 'Checkout sin direcciones', 'adresles-checkout' ); ?></h2>

	<ul>

		<li><?php esc_html_e( '✅ Menos fricción, más ventas', 'adresles-checkout' ); ?></li>

		<li><?php esc_html_e( '✅ Pedidos sin direcciones', 'adresles-checkout' ); ?></li>

		<li><?php esc_html_e( '✅ Experiencia de usuario optimizada', 'adresles-checkout' ); ?></li>

	</ul>

	<form method="post">
		<?php wp_nonce_field( 'adresles_register_plugin', 'adresles_register_nonce' ); ?>

		<!-- STEP 1: Basic Configuration -->
		<h2><?php esc_html_e( 'Step 1: Basic Configuration', 'adresles-checkout' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="adresles_api_mode"><?php esc_html_e( 'API Mode', 'adresles-checkout' ); ?></label></th>
				<td>
					<select name="adresles_api_mode" id="adresles_api_mode" class="regular-text adresles-cw">
						<option value="staging" <?php selected( $api_mode, 'staging' ); ?>>Staging</option>
						<option value="production" <?php selected( $api_mode, 'production' ); ?>>Production</option>
					</select>
					<p class="description"><?php esc_html_e( 'Select the environment for Adresles API.', 'adresles-checkout' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="id_plugin"><?php esc_html_e( 'Plugin ID', 'adresles-checkout' ); ?></label></th>
				<td><input type="text" name="id_plugin" value="<?php echo esc_attr( $plugin_id ); ?>" class="regular-text adresles-cw" readonly></td>
			</tr>
			<tr>
				<th><label for="name"><?php esc_html_e( 'Name', 'adresles-checkout' ); ?></label></th>
				<td><input type="text" name="name" value="<?php echo esc_attr( $name ); ?>" class="regular-text adresles-cw" required></td>
			</tr>
			<tr>
				<th><label for="email"><?php esc_html_e( 'Email', 'adresles-checkout' ); ?></label></th>
				<td><input type="email" name="email" value="<?php echo esc_attr( $email ); ?>" class="regular-text adresles-cw" required></td>
			</tr>
			<tr>
				<th><label for="phone"><?php esc_html_e( 'Phone', 'adresles-checkout' ); ?></label></th>
				<td><input type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text adresles-cw"></td>
			</tr>
			<tr style="display:none;">
				<th><label for="password"><?php esc_html_e( 'Password', 'adresles-checkout' ); ?></label></th>
				<td><input type="text" name="password" value="<?php echo esc_attr( $password ); ?>" class="regular-text adresles-cw"></td>
			</tr>
		</table>

		<?php if ( ! empty( $saved['registered_at'] ) ) : ?>
			<!-- STEP 2: Field Mapping -->
			<h2 style="margin-top:40px;"><?php esc_html_e('Step 2: Field Mapping', 'adresles-checkout'); ?></h2>
			<p><?php esc_html_e('Now that your plugin is registered, please map your WooCommerce fields to Adresles fields to activate the addressless checkout flow.', 'adresles-checkout'); ?></p>

			<table class="form-table">
				<?php foreach ( $wc_fields as $wc_key => $wc_label ) :
					if ( $wc_key === 'order_comments' ) continue;?>
					<tr>
						<th><label for="mapping_<?php echo esc_attr( $wc_key ); ?>"><?php echo esc_html( $wc_label ); ?></label></th>
						<td>
							<select class="adresles-cw" name="field_mapping[<?php echo esc_attr( $wc_key ); ?>]" id="mapping_<?php echo esc_attr( $wc_key ); ?>" required>
								<option value=""><?php esc_html_e('-- Select --', 'adresles-checkout'); ?></option>
								<?php foreach ( $api_fields as $api_field ) : ?>
									<option value="<?php echo esc_attr( $api_field ); ?>" <?php selected( $field_mappings[ $wc_key ] ?? '', $api_field ); ?>>
										<?php echo esc_html( $api_field ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php else : ?>
			<p style="margin-top:20px; font-style: italic; color: #555;">
				<?php esc_html_e( 'After saving the above details, the field mapping options will appear below.', 'adresles-checkout' ); ?>
			</p>
		<?php endif; ?>

		<?php submit_button( __( 'Save Settings', 'adresles-checkout' ) ); ?>
	</form>
</div>
