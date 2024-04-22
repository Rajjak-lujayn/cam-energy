<?php
	$defaults = array(
		'class'             => 'button-secondary',
		'css'               => '',
		'custom_attributes' => array(),
		'desc_tip'          => true,
		'description'       => '',
		'title'             => '',
		'disable'           => true,
	);
	// pr( $settings_fileds );
	$freq_options = array(
		'hourly'     => __( 'Every Hour', 'wc-odoo-integration' ),
		'twicedaily' => __( 'Twice A Day', 'wc-odoo-integration' ),
		'daily'      => __( 'Once A Day', 'wc-odoo-integration' ),
	);
	$fields       = $this->allowed_html();
	$this->odooTax = $this->get_option('odooTax');
	$this->shippingOdooTax = $this->get_option('shippingOdooTax');
	$this->invoiceJournal = $this->get_option('invoiceJournal');
	$this->companyFile = $this->get_option('companyFile');
	?>
<div class="tabset">
	<!-- Tab 1 -->
	<input type="radio" name="tabset" id="tab1" aria-controls="odoo_creds_settings" checked>
	<label for="tab1"><?php echo esc_html__( 'Settings ', 'wc-odoo-integration' ); ?></label>
	<?php if ($opmc_odoo_access_token && $opmc_odoo_authenticated_uid) : ?>
		<input type="radio" name="tabset" id="tab5" aria-controls="opmc_ofw_configs">
		<label for="tab5"><?php echo esc_html__( 'Configurations', 'wc-odoo-integration' ); ?></label>
		<?php if ( '' != $this->companyFile || '' != $this->odooTax || '' != $this->shippingOdooTax || '' != $this->invoiceJournal ) : ?>
			<!-- Tab 2 -->
			<input type="radio" name="tabset" id="tab2" aria-controls="odoo_import">
			<label for="tab2"><?php echo esc_html__( 'Import', 'wc-odoo-integration' ); ?></label>
			<!-- Tab 3 -->
			<input type="radio" name="tabset" id="tab3" aria-controls="odoo_export">
			<label for="tab3"><?php echo esc_html__( 'Export', 'wc-odoo-integration' ); ?></label>
		<?php endif; ?>
	<?php endif; ?>
	<!-- Tab 4 -->
	<input type="radio" name="tabset" id="tab4" aria-controls="odoo_debug_log">
	<label for="tab4"><?php echo esc_html__( 'Logs', 'wc-odoo-integration' ); ?></label>

	<div class="tab-panels">

		<section id="odoo_creds_settings" class="tab-panel">
			<h2><?php echo esc_html__( 'Odoo Integration', 'wc-odoo-integration' ); ?> <span class="opmc-odoo-check <?php echo esc_attr( $opmc_odoo_indicator['class'] ); ?>"> <span class="dashicons <?php echo esc_attr( $opmc_odoo_indicator['icon'] ); ?>"></span><?php echo esc_html__( $opmc_odoo_indicator['value'] ); ?></span></h2>
			<div>
				<p>Odoo API configuration and global settings.</p>
			</div>
			<table class="form-table">
				<?php
				foreach ( $tab1 as $sk => $sv ) {
					$input_type = $this->get_field_type( $sv );
					if ( method_exists( $this, 'generate_' . $input_type . '_html' ) ) {
						$html = $this->{'generate_' . $input_type . '_html'}( $sk, $sv );
					} else {
						$html = $this->generate_text_html( $sk, $sv );
					}
					// echo $html;
					echo wp_kses( $html, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</table>
		</section>
		<?php if ($opmc_odoo_access_token && $opmc_odoo_authenticated_uid) : ?>
			<section id="opmc_ofw_configs" class="tab-panel">
				<h2>General Configuration</h2>
				<div>
					<p><?php esc_html_e('These settings are important because they determine how your orders, products, taxes, customers, etc. are syncing with Odoo.', 'wc-odoo-integration'); ?></p>
				</div>
				<table class="form-table">
					<?php
					foreach ( $tab_config as $sk => $sv ) {
						$input_type = $this->get_field_type( $sv );
						if ( method_exists( $this, 'generate_' . $input_type . '_html' ) ) {
							$html = $this->{'generate_' . $input_type . '_html'}( $sk, $sv );
						} else {
							$html = $this->generate_text_html( $sk, $sv );
						}
						// echo $html;
						echo wp_kses( $html, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</table>
			</section>
			<?php if ( '' != $this->companyFile || '' != $this->odooTax || '' != $this->shippingOdooTax || '' != $this->invoiceJournal ) : ?>
				<section id="odoo_import" class="tab-panel">
					<h2 id="logs_title"><?php echo esc_html__( 'Import Settings', 'wc-odoo-integration' ); ?> </h2>
					<div>
						<p> Settings for data import from Odoo to WooCommerce.</p>
					</div>
						<div class="product-function">
							<div class="product-function-lt">
								<h2 class="product-function-heading"><?php echo esc_html__( 'Product Functions', 'wc-odoo-integration' ); ?></h2>
								<table class="form-table">
									
									<?php
									
									foreach ( $tab2 as $sk => $sv ) {
										$input_type = $this->get_field_type( $sv );
										if ( method_exists( $this, 'generate_' . $input_type . '_html' ) ) {
											$html = $this->{'generate_' . $input_type . '_html'}( $sk, $sv );
										} else {
											$html = $this->generate_text_html( $sk, $sv );
										}
										// echo $html;
										echo wp_kses( $html, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</table>
	
							</div>
							<div class="product-function-rt">
								<h2 class="product-function-heading"><?php echo esc_html__( 'Customer Functions', 'wc-odoo-integration' ); ?></h2>
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row" class="titledesc">
												<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer' ); ?>">
													<?php
														echo esc_html__( 'Import/Update Customers', 'wc-odoo-integration' );
														$data = wp_parse_args( $tab2_2['odoo_import_customer'], $defaults );
														echo wp_kses_post( $this->get_tooltip_html( $data ) );
													?>
												</label>
											</th>
											<td class="forminp">
												<fieldset>
													<legend class="screen-reader-text"><span><?php echo esc_html__( 'Import/Update Customers', 'wc-odoo-integration' ); ?></span></legend>
													<label class="switch">
														<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer' ); ?>" value="<?php $this->get_option( 'odoo_import_customer' ); ?>" <?php checked( $this->get_option( 'odoo_import_customer' ), 'yes' ); ?>>
														<span class="slider round"></span>
													</label>
												</fieldset>
											</td>
										</tr>
									<tr valign="top">
										<th scope="row" class="titledesc">
											<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_frequency' ); ?>">
												<?php
													echo esc_html__( 'Customer Frequency', 'wc-odoo-integration' );
													$data = wp_parse_args( $tab2_2['odoo_import_customer_frequency'], $defaults );
													echo wp_kses_post( $this->get_tooltip_html( $data ) );
												?>
											</label>
										</th>
										<td class="forminp">
											<fieldset>
												<legend class="screen-reader-text"><span><?php echo esc_html__( 'Customer Frequency', 'wc-odoo-integration' ); ?></span></legend>
												<select class="select " name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_frequency' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_frequency' ); ?>" style="">
													<?php foreach ( $freq_options as $option_key_inner => $option_value_inner ) : ?>
														<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( $this->get_option( 'odoo_import_customer_frequency' ) ) ); ?>><?php echo esc_html__( $option_value_inner ); ?></option>
													<?php endforeach ?>
												</select>
												<p class="description"><?php echo esc_html__( 'Select customer cron frequency to sync customer.', 'wc-odoo-integration' ); ?></p>
											</fieldset>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc">
											<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_from_date' ); ?>">
												<?php
													echo esc_html__( 'Odoo Customer Sync', 'wc-odoo-integration' );
													$data = wp_parse_args( $tab2_2['odoo_import_refund_order'], $defaults );
													echo wp_kses_post( $this->get_tooltip_html( $data ) );
												?>
												<span class="woocommerce-help-tip" data-tip="<?php echo esc_html__( 'Specify the customer creation start and end dates for synchronization within the chosen time period from Odoo to WooCommerce.', 'wc-odoo-integration' ); ?>"></span>
											</label>
										</th>
										<td class="forminp">
											<fieldset>
												<legend class="screen-reader-text"><span><?php echo esc_html__( 'Odoo Customer Sync', 'wc-odoo-integration' ); ?></span></legend>
												<div class="io-field">
													<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_from_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_from_date' ); ?>" placeholder="<?php esc_html__( 'From', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( '_odoo_import_customer_from_date' ) ); ?>" class="datepicker_min">
	
													<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_to_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_customer_to_date' ); ?>" placeholder="<?php esc_html__( 'To', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( '_odoo_import_customer_to_date' ) ); ?>" class="datepicker_max">
	
													<button type="button" name="odoo_import_customer_sync" value="submit" id="odoo_import_customer_sync"><?php echo esc_html__( 'Submit', 'wc-odoo-integration' ); ?></button>
													<button type="button" id="odoo_import_customer_sync_loading" style="display:none;"><?php echo esc_html__( 'Please wait.', 'wc-odoo-integration' ); ?></button>
													<span class="odoo_import_customer_sync_message"></span>
												</div>
											</fieldset>
										</td>
									</tr>
									</tbody>
								</table>

								<h2 class="product-function-heading"><?php echo esc_html__( 'Discount and Coupon Functions', 'wc-odoo-integration' ); ?></h2>
								<table class="form-table">
									<?php
									foreach ( $tab2_3 as $sk => $sv ) {
										$input_type = $this->get_field_type( $sv );
										if ( method_exists( $this, 'generate_' . $input_type . '_html' ) ) {
											$html = $this->{'generate_' . $input_type . '_html'}( $sk, $sv );
										} else {
											$html = $this->generate_text_html( $sk, $sv );
										}
										// echo $html;
										echo wp_kses( $html, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</table>
							</div>
						</div>

						<div class="product-function">
							<div class="product-function-lt">
								<div class="inner-product-function">
									<h2 class="product-function-heading"><?php echo esc_html__( 'Order Functions', 'wc-odoo-integration' ); ?></h2>
								</div>
								<table class="form-table">
									<tbody>
										<tr valign="top">
											<th scope="row" class="titledesc">
												<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_from_date' ); ?>">
												<?php
													echo esc_html__( 'Import Orders', 'wc-odoo-integration' );
													$data = wp_parse_args( $tab2_2['odoo_import_order'], $defaults );
													echo wp_kses_post( $this->get_tooltip_html( $data ) );
												?>
											</label>
										</th>
										<td class="forminp">
											<fieldset>
												<legend class="screen-reader-text"><span><?php echo esc_html__( 'Import Orders', 'wc-odoo-integration' ); ?></span></legend>
												<div class="io-field">
													<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_from_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_from_date' ); ?>" placeholder="<?php esc_html__( 'From', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( 'odoo_import_order_from_date' ) ); ?>" class="datepicker_min">
													
													<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_to_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_to_date' ); ?>" placeholder="<?php esc_html__( 'To', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( 'odoo_import_order_to_date' ) ); ?>" class="datepicker_max">
													<label class="switch">
														
														<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order' ); ?>" value="<?php $this->get_option( 'odoo_import_order' ); ?>" <?php checked( $this->get_option( 'odoo_import_order' ), 'yes' ); ?>>
														<span class="slider round"></span>
													</label>
												</div>
												<p class="description"><?php echo esc_html__( 'Select the date range for orders to import, Leave blank to import all orders till date.', 'wc-odoo-integration' ); ?></p>
											</fieldset>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc">
											<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_frequency' ); ?>">
												<?php
													echo esc_html__( 'Order Frequency', 'wc-odoo-integration' );
													$data = wp_parse_args( $tab2_2['odoo_import_order_frequency'], $defaults );
													echo wp_kses_post( $this->get_tooltip_html( $data ) );
												?>
											</label>
										</th>
										<td class="forminp">
											<fieldset>
												<legend class="screen-reader-text"><span><?php echo esc_html__( 'Order Frequency', 'wc-odoo-integration' ); ?></span></legend>
												<select class="select " name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_frequency' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_order_frequency' ); ?>" style="">
													<?php foreach ( $freq_options as $option_key_inner => $option_value_inner ) : ?>
														<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( $this->get_option( 'odoo_import_order_frequency' ) ) ); ?>><?php echo esc_html__( $option_value_inner ); ?></option>
													<?php endforeach ?>
												</select>
												<p class="description"><?php echo esc_html__( 'Select order cron frequency to sync order.', 'wc-odoo-integration' ); ?></p>
											</fieldset>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row" class="titledesc">
											<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_refund_order' ); ?>">
												<?php
													echo esc_html__( 'Import Refund Orders', 'wc-odoo-integration' );
													$data = wp_parse_args( $tab2_2['odoo_import_refund_order'], $defaults );
													echo wp_kses_post( $this->get_tooltip_html( $data ) );
												?>
											</label>
										</th>
										<td class="forminp">
											<fieldset>
												<legend class="screen-reader-text"><span><?php echo esc_html__( 'Import Refund Orders', 'wc-odoo-integration' ); ?></span></legend>
												<div class="io-field">
													<label class="switch">
														<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_refund_order' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_refund_order' ); ?>" value="<?php $this->get_option( 'odoo_import_refund_order' ); ?>" <?php checked( $this->get_option( 'odoo_import_refund_order' ), 'yes' ); ?>>
													<span class="slider round"></span>
													</label>
												</div>
											</fieldset>
										</td>
									</tr>
									<tr valign="top" class= "opmc_import_refund_order_frequency">
										<th scope="row" class="titledesc">
											<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_refund_order_frequency' ); ?>">
												<?php
													echo esc_html__( 'Order Refund Frequency', 'wc-odoo-integration' );
													$data = wp_parse_args( $tab2_2['odoo_import_refund_order_frequency'], $defaults );
													echo wp_kses_post( $this->get_tooltip_html( $data ) );
												?>
											</label>
										</th>
										<td class="forminp">
											<fieldset>
												<legend class="screen-reader-text"><span><?php echo esc_html__( 'Order Refund Frequency', 'wc-odoo-integration' ); ?></span></legend>
												<select class="select " name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_refund_order_frequency' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_import_refund_order_frequency' ); ?>" style="">
													<?php foreach ( $freq_options as $option_key_inner => $option_value_inner ) : ?>
														<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( $this->get_option( 'odoo_import_refund_order_frequency' ) ) ); ?>><?php echo esc_html__( $option_value_inner ); ?></option>
														<?php endforeach ?>
												</select>
												<p class="description"><?php echo esc_html__( 'Select refund order cron frequency to sync refund order.', 'wc-odoo-integration' ); ?></p>
											</fieldset>
										</td>
									</tr>
									</tbody>
								</table>
							</div>
							<div class="product-function-rt"></div>
						</div>
				</section>
				<section id="odoo_export" class="tab-panel">
					<h2 id="logs_title"><?php echo esc_html__( 'Export Settings', 'wc-odoo-integration' ); ?> </h2>
					<div>
						<p> Settings for data export from WooCommerce to Odoo.</p>
					</div>

					<div class="product-function">
						<div class="product-function-lt">
							<h2 class="product-function-heading"><?php echo esc_html__( 'Product Functions', 'wc-odoo-integration' ); ?></h2>
							<table class="form-table">
								<?php
								foreach ( $tab3 as $sk => $sv ) {
									$input_type = $this->get_field_type( $sv );
									if ( method_exists( $this, 'generate_' . $input_type . '_html' ) ) {
										$html = $this->{'generate_' . $input_type . '_html'}( $sk, $sv );
									} else {
										$html = $this->generate_text_html( $sk, $sv );
									}
									// echo $html;
									echo wp_kses( $html, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="woocommerce_woocommmerce_odoo_integration_odoo_export_product_from_date">
											<?php echo esc_html__( 'Product Sync', 'wc-odoo-integration' ); ?>
											<span class="woocommerce-help-tip" data-tip="<?php echo esc_html__( 'Enter the product creation start and end dates to synchronize products for the selected time period. By specifying these dates, you can ensure that only products created within the defined time frame will be synced between the platforms, helping you manage product data effectively.', 'wc-odoo-integration' ); ?>"></span>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Product Sync', 'wc-odoo-integration' ); ?></span></legend>
											<div class="io-field">
												<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_product_from_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_product_from_date' ); ?>" placeholder="<?php esc_html__( 'From', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( '_odoo_export_product_from_date' ) ); ?>" class="datepicker_min">
	
												<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_product_to_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_product_to_date' ); ?>" placeholder="<?php esc_html__( 'To', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( '_odoo_export_product_to_date' ) ); ?>" class="datepicker_max">
	
												<button type="button" name="odoo_export_product_sync" value="submit" id="odoo_export_product_sync"><?php echo esc_html__( 'Submit', 'wc-odoo-integration' ); ?></button>
												<button type="button" id="odoo_export_product_sync_loading" style="display:none;"><?php echo esc_html__( 'Please wait.', 'wc-odoo-integration' ); ?></button>
												<span class="odoo_export_product_sync_message"></span>
											</div>
										</fieldset>
									</td>
								</tr>
							</table>
						</div>
						<div class="product-function-rt">
							<h2 class="product-function-heading"><?php echo esc_html__( 'Customer Functions', 'wc-odoo-integration' ); ?></h2>
							<table class="form-table">
								<tbody>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_map_to_default_customers' ); ?>">
											<?php
												echo esc_html__( 'Map All Orders to Default Customer', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_map_to_default_customers'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Map All Orders to Default Customer', 'wc-odoo-integration' ); ?></span></legend>
											<label class="switch">
												<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_map_to_default_customers' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_map_to_default_customers' ); ?>" value="<?php $this->get_option( 'odoo_map_to_default_customers' ); ?>" <?php checked( $this->get_option( 'odoo_map_to_default_customers' ), 'yes' ); ?>>
												<span class="slider round"></span>
											</label>
										</fieldset>
									</td>
								</tr>
								<tr valign="top" class="opmc-odoo-default-customer">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_default_customer_id' ); ?>">
											<?php
												echo esc_html__( 'Default Odoo Customer ID', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_default_customer_id'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Map All Orders to Default Customer', 'wc-odoo-integration' ); ?></span></legend>
											<input type="text" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_default_customer_id' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_default_customer_id' ); ?>" value="<?php echo esc_attr( $this->get_option( 'odoo_default_customer_id' ) ); ?>" >
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer' ); ?>">
											<?php
												echo esc_html__( 'Export/Update Customers', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_export_customer'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Export/Update Customers', 'wc-odoo-integration' ); ?></span></legend>
											<label class="switch">
												<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer' ); ?>" value="<?php $this->get_option( 'odoo_export_customer' ); ?>" <?php checked( $this->get_option( 'odoo_export_customer' ), 'yes' ); ?>>
												<span class="slider round"></span>
											</label>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer_frequency' ); ?>">
											<?php
												echo esc_html__( 'Customer Frequency', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_export_customer_frequency'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Customer Frequency', 'wc-odoo-integration' ); ?></span></legend>
											<select class="select " name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer_frequency' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer_frequency' ); ?>" style="">
												<?php foreach ( $freq_options as $option_key_inner => $option_value_inner ) : ?>
													<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( $this->get_option( 'odoo_export_customer_frequency' ) ) ); ?>><?php echo esc_html__( $option_value_inner ); ?></option>
												<?php endforeach ?>
											</select>
											<p class="description"><?php echo esc_html__( 'Select customer cron frequency to sync customer.', 'wc-odoo-integration' ); ?></p>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="woocommerce_woocommmerce_odoo_integration_odoo_export_customer_from_date">
											<?php echo esc_html__( 'Customer Sync', 'wc-odoo-integration' ); ?>
											<span class="woocommerce-help-tip" data-tip="<?php echo esc_html__( 'Specify the customer creation start and end dates to synchronize customers for the selected time period. By providing these dates, you can ensure that only customers created within the defined time frame will be synced between Odoo and WooCommerce, helping you maintain accurate customer records.', 'wc-odoo-integration' ); ?>"></span>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Customer Sync', 'wc-odoo-integration' ); ?></span></legend>
											<div class="io-field">
												<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer_from_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer_from_date' ); ?>" placeholder="<?php esc_html__( 'From', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( '_odoo_export_customer_from_date' ) ); ?>" class="datepicker_min">
	
												<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer_to_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_customer_to_date' ); ?>" placeholder="<?php esc_html__( 'To', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( '_odoo_export_customer_to_date' ) ); ?>" class="datepicker_max">
	
												<button type="button" name="odoo_export_customer_sync" value="submit" id="odoo_export_customer_sync"><?php echo esc_html__( 'Submit', 'wc-odoo-integration' ); ?></button>
												<button type="button" id="odoo_export_customer_sync_loading" style="display:none;"><?php echo esc_html__( 'Please wait.', 'wc-odoo-integration' ); ?></button>
												<span class="odoo_export_customer_sync_message"></span>
											</div>
										</fieldset>
									</td>
								</tr>
								</tbody>
							</table>
							<h2 class="product-function-heading"><?php echo esc_html__( 'Discount And Coupon Functions', 'wc-odoo-integration' ); ?></h2>
							<table class="form-table">
								<?php
								foreach ( $tab3_3 as $sk => $sv ) {
									$input_type = $this->get_field_type( $sv );
									if ( method_exists( $this, 'generate_' . $input_type . '_html' ) ) {
										$html = $this->{'generate_' . $input_type . '_html'}( $sk, $sv );
									} else {
										$html = $this->generate_text_html( $sk, $sv );
									}
									// echo $html;
									echo wp_kses( $html, $fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</table>
						</div>
					</div>
					<div class="product-function">
						<div class="product-function-lt">
							<div class="inner-product-function">
								<h2 class="product-function-heading"><?php echo esc_html__( 'Order Functions', 'wc-odoo-integration' ); ?></h2>
							</div>
							<table class="form-table">
								<tbody>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_on_checkout' ); ?>">
											<?php
												echo esc_html__( 'Export Order On Checkout', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_export_order_on_checkout'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Export Order On Checkout', 'wc-odoo-integration' ); ?></span></legend>
											<label class="switch">
												<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_on_checkout' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_on_checkout' ); ?>" value="<?php $this->get_option( 'odoo_export_order_on_checkout' ); ?>" <?php checked( $this->get_option( 'odoo_export_order_on_checkout' ), 'yes' ); ?>>
												<span class="slider round"></span>
											</label>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_invoice' ); ?>">
											<?php
												echo esc_html__( 'Export Invoice', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_export_invoice'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Export Invoice', 'wc-odoo-integration' ); ?></span></legend>
											<label class="switch">
												<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_invoice' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_invoice' ); ?>" value="<?php $this->get_option( 'odoo_export_invoice' ); ?>" <?php checked( $this->get_option( 'odoo_export_invoice' ), 'yes' ); ?>>
												<span class="slider round"></span>
											</label>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_mark_invoice_paid' ); ?>">
											<?php
												echo esc_html__( 'Mark Invoice Paid', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_mark_invoice_paid'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
	
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Mark Invoice Paid', 'wc-odoo-integration' ); ?></span></legend>
											<label class="switch">
												<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_mark_invoice_paid' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_mark_invoice_paid' ); ?>" value="<?php $this->get_option( 'odoo_mark_invoice_paid' ); ?>" <?php checked( $this->get_option( 'odoo_mark_invoice_paid' ), 'yes' ); ?>>
												<span class="slider round"></span>
											</label>
											<p class="description"><?php echo esc_html__( 'Export invoice should be enabled for this.', 'wc-odoo-integration' ); ?></p>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_refund_order' ); ?>">
											<?php
												echo esc_html__( 'Export Refund Order', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_export_refund_order'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Export Refund Order', 'wc-odoo-integration' ); ?></span></legend>
											<label class="switch">
												<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_refund_order' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_refund_order' ); ?>" value="<?php $this->get_option( 'odoo_export_refund_order' ); ?>" <?php checked( $this->get_option( 'odoo_export_refund_order' ), 'yes' ); ?>>
												<span class="slider round"></span>
											</label>
											<p class="description"><?php echo esc_html__( 'Export invoice should be enabled for this.', 'wc-odoo-integration' ); ?></p>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_status_mapping' ); ?>">
											<?php
												echo esc_html__( 'Status Mapping', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_status_mapping'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Status Mapping', 'wc-odoo-integration' ); ?></span></legend>
											<label class="switch">
												<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_status_mapping' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_status_mapping' ); ?>" value="<?php $this->get_option( 'odoo_status_mapping' ); ?>" <?php checked( $this->get_option( 'odoo_status_mapping' ), 'yes' ); ?>>
												<span class="slider round"></span>
											</label>
											<p class="description"><?php echo esc_html__( 'Custom status mapping.', 'wc-odoo-integration' ); ?></p>
										</fieldset>
									</td>
								</tr>
								<?php
									$statuses = wc_get_order_statuses();
									unset( $statuses['wc-refunded'] );
									$odoo_payment_states = array(
										'quote_only'  => __( 'Quote Only', 'wc-odoo-integration' ),
										'quote_order' => __( 'Quote and Sales Order', 'wc-odoo-integration' ),
										'in_payment'  => __( 'In Payment Invoice', 'wc-odoo-integration' ),
										'paid'        => __( 'Paid Invoice', 'wc-odoo-integration' ),
										'cancelled'   => __( 'Cancelled', 'wc-odoo-integration' ),
									);
									$odoo_states_desc    = array(
										'quote_only'  => __( 'This will only create Quote on Odoo. No Sales Order and invoice.', 'wc-odoo-integration' ),
										'quote_order' => __( 'Quote and Sales Order will be created. Invoice will not be created.', 'wc-odoo-integration' ),
										'in_payment'  => __( 'Quote, Sales Order and Invoice will be created. Invoice will be in “In Payment“ State.', 'wc-odoo-integration' ),
										'paid'        => __( 'Quote, Sales Order and Invoice will be created. The invoice will be marked as PAID', 'wc-odoo-integration' ),
										'cancelled'   => __( 'Order will be marked as canceled', 'wc-odoo-integration' ),
									);
									?>
								<tr valign="top" class="order_mapping_block">
									<th scope="row" class="titledesc">
										<label for="woocommerce_woocommmerce_odoo_integration_odoo_woo_order_status"><?php echo esc_html__( 'Order Status Mapping', 'wc-odoo-integration' ); ?>:</label>
									</th>
								</tr>
								<tr valign="top" class="order_mapping_block">
									<th scope="col" class="titledesc">
										<label for="woocommerce_woocommmerce_odoo_integration_odoo_woo_order_status"><?php echo esc_html__( 'Woo Order Status', 'wc-odoo-integration' ); ?></label>
									</th>
									<th scope="col" class="titledesc">
										<label for="woocommerce_woocommmerce_odoo_integration_odoo_payment_status"><?php echo esc_html__( 'Odoo Order State', 'wc-odoo-integration' ); ?></label>
									</th>
								</tr>
								
								<?php
								if ( $this->get_option( 'odoo_woo_order_status' ) > 0 && $this->get_option( 'odoo_payment_status' ) ) :
									$mapped_woo_status          = $this->get_option( 'odoo_woo_order_status' );
									$mapped_odoo_payment_states = $this->get_option( 'odoo_payment_status' );
									?>
									<?php foreach ( $mapped_woo_status as $map_key => $value ) : ?>
										<tr valign="top" class="mappingBlock" data-index="<?php echo esc_attr( $map_key ); ?>" data-max_rows="<?php echo count( $statuses ); ?>">
											<td class="forminp">
												<fieldset>
													<legend class="screen-reader-text"><span><?php echo esc_html__( 'Woo Order Status', 'wc-odoo-integration' ); ?></span></legend>
													<select class="select odoo_woo_order_status" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_woo_order_status[' . $map_key . ']' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_woo_order_status_' . $map_key ); ?>">
														<option value=""><?php echo esc_html__( 'Select Woo Status', 'wc-odoo-integration' ); ?></option>
														<?php foreach ( $statuses as $key => $status_value ) : ?>
															<?php if ( 'wc-refunded' != $key ) : ?>
																<option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) $key, esc_attr( $mapped_woo_status[ $map_key ] ) ); ?>><?php echo esc_html__( $status_value ); ?></option>
															<?php endif; ?>
														<?php endforeach; ?>
													</select>
													<p class="description"><?php echo esc_html__( 'Woo Status to Map.', 'wc-odoo-integration' ); ?></p>
												</fieldset>
											</td>
											<td class="forminp">
												<fieldset>
													<legend class="screen-reader-text"><span><?php echo esc_html__( 'Odoo Order State', 'wc-odoo-integration' ); ?></span></legend>
													<select class="select odoo_payment_status" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_payment_status[' . $map_key . ']' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_payment_status_' . $map_key ); ?>">
														<option value="" data-desc="<?php echo esc_html__( 'Selected state\'s description.', 'wc-odoo-integration' ); ?>"><?php echo esc_html__( 'Select Odoo State', 'wc-odoo-integration' ); ?></option>
														<?php foreach ( $odoo_payment_states as $key => $states_value ) : ?>
															<option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) $key, esc_attr( $mapped_odoo_payment_states[ $map_key ] ) ); ?> data-desc="<?php echo esc_html__( $odoo_states_desc[ $key ] ); ?>" ><?php echo esc_html__( $states_value ); ?></option>
														<?php endforeach; ?>
													</select>
													<p class="description">
														<?php echo ( ( '' != $mapped_odoo_payment_states[ $map_key ] ) ? esc_html__( $odoo_states_desc[ $mapped_odoo_payment_states[ $map_key ] ] ) : esc_html__( 'Selected state\'s description.', 'wc-odoo-integration' ) ); ?>
													</p>
												</fieldset>
											</td>
										</tr>
									<?php endforeach; ?>
									<?php else : ?>
										<tr valign="top" class="mappingBlock" data-index="1" data-max_rows="<?php echo count( $statuses ); ?>">
											<td class="forminp">
												<fieldset>
													<legend class="screen-reader-text"><span><?php echo esc_html__( 'Woo Order Status', 'wc-odoo-integration' ); ?></span></legend>
													<select class="select odoo_woo_order_status" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_woo_order_status[1]' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_woo_order_status_1' ); ?>">
														<option value=""><?php echo esc_html__( 'Select Woo Status', 'wc-odoo-integration' ); ?></option>
														<?php foreach ( $statuses as $key => $value ) : ?>
															<?php if ( 'wc-refunded' != $key ) : ?>
																<option value="<?php echo esc_attr( $key ); ?>" ><?php echo esc_html__( $value ); ?></option>
															<?php endif; ?>
														<?php endforeach; ?>
													</select>
													<p class="description"><?php echo esc_html__( 'Woo Status to Map.', 'wc-odoo-integration' ); ?></p>
												</fieldset>
											</td>
											<td class="forminp">
												<fieldset>
													<legend class="screen-reader-text"><span><?php echo esc_html__( 'Odoo Order State', 'wc-odoo-integration' ); ?></span></legend>
													<select class="select odoo_payment_status" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_payment_status[1]' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_payment_status_1' ); ?>">
														<option value="" data-desc="<?php echo esc_html__( 'Selected state\'s description.', 'wc-odoo-integration' ); ?>"><?php echo esc_html__( 'Select Odoo State', 'wc-odoo-integration' ); ?></option>
														<?php foreach ( $odoo_payment_states as $key => $value ) : ?>
															<option value="<?php echo esc_attr( $key ); ?>" data-desc="<?php echo esc_html__( $odoo_states_desc[ $key ] ); ?>"><?php echo esc_html__( $value ); ?></option>
														<?php endforeach ?>
													</select>
													<p class="description"><?php echo esc_html__( 'Selected state\'s description.', 'wc-odoo-integration' ); ?></p>
												</fieldset>
											</td>
										</tr>
									<?php endif; ?>
								<tr valign="top" class="order_mapping_block">
									<td colspan="2" align="right">
										<input type="button" class="btn btn-primary" value="<?php echo esc_html__( '(+) Add More Mapping', 'wc-odoo-integration' ); ?>" id="addMoreMappingRows" />
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_from_date' ); ?>">
											<?php
												echo esc_html__( 'Export Orders', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_export_order'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Export Orders', 'wc-odoo-integration' ); ?></span></legend>
											<div class="io-field">
												<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_from_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_from_date' ); ?>" placeholder="<?php esc_html__( 'From', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( 'odoo_export_order_from_date' ) ); ?>" class="datepicker_min">
												
												<input type="text" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_to_date' ); ?>" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_to_date' ); ?>" placeholder="<?php esc_html__( 'To', 'wc-odoo-integration' ); ?>" value="<?php echo esc_attr( $this->get_option( 'odoo_export_order_to_date' ) ); ?>" class="datepicker_max">
												
												<label class="switch">
													<input type="checkbox" name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order' ); ?>" value="<?php $this->get_option( 'odoo_export_order' ); ?>" <?php checked( $this->get_option( 'odoo_export_order' ), 'yes' ); ?>>
													<span class="slider round"></span>
												</label>
											</div>
											<p class="description"><?php echo esc_html__( 'Select the date range for orders to export, Leave blank to export all orders till date.', 'wc-odoo-integration' ); ?></p>
										</fieldset>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row" class="titledesc">
										<label for="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_frequency' ); ?>">
											<?php
												echo esc_html__( 'Order Frequency', 'wc-odoo-integration' );
												$data = wp_parse_args( $tab3_2['odoo_export_order_frequency'], $defaults );
												echo wp_kses_post( $this->get_tooltip_html( $data ) );
											?>
										</label>
									</th>
									<td class="forminp">
										<fieldset>
											<legend class="screen-reader-text"><span><?php echo esc_html__( 'Order Frequency', 'wc-odoo-integration' ); ?></span></legend>
											<select class="select " name="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_frequency' ); ?>" id="<?php echo esc_attr( $this->plugin_id . $this->id . '_odoo_export_order_frequency' ); ?>" style="">
												<?php foreach ( $freq_options as $option_key_inner => $option_value_inner ) : ?>
													<option value="<?php echo esc_attr( $option_key_inner ); ?>" <?php selected( (string) $option_key_inner, esc_attr( $this->get_option( 'odoo_export_order_frequency' ) ) ); ?>><?php echo esc_html__( $option_value_inner ); ?></option>
												<?php endforeach ?>
											</select>
											<p class="description"><?php echo esc_html__( 'Select order cron frequency to sync order.', 'wc-odoo-integration' ); ?></p>
										</fieldset>
									</td>
								</tr>
							</tbody>
							</table>
						</div>
						<div class="product-function-rt"></div>
					</div>
				</section>
			<?php endif; ?>
		<?php endif; ?>

		<!-- /* PLUGINS-2244 */ -->

		<section id="odoo_logs" class="tab-panel">
			<div class="tab-panels">
				<h2 id="logs_title"><?php echo esc_html__( 'Logs', 'wc-odoo-integration' ); ?> </h2>
				<div>
					<p> All API transactions/logs are recorded here.</p>
				</div>
				<?php
					$odoos  = array();
					$result = WC_Log_Handler_File::get_log_files();
				foreach ( $result as $value ) {
					$val = explode( '-', $value );
					if ( 'odoo' == $val[0] ) {
						$odoos[] = array( $val[2] . '-' . $val[3] . '-' . $val[4], $value );
					}
				}
				if (!empty($odoos)) :
					$product_job            = get_option( 'selected_log_view', '' );
					$selected_log_view_text = get_option( 'selected_log_view_text', gmdate('Y-m-d') );
					// print_r($selected_log_view_text);
					?>
						<div id="header_content">
							<button type="submit" class="button" id="view_log" style="float: right;"> view</button>
							<select class="select2-selection select2-selection--single" name="logs" id="logs" style="float: right; margin: 0 4px 0 0;">
							<?php
							foreach ( $odoos as $value ) {
								if ( ! empty( $selected_log_view_text == $value[0] ) ) :
									$product_job = $value[1];
									?>
											<option value="<?php echo esc_attr( $product_job ); ?>" selected> <?php echo esc_html( $selected_log_view_text ); ?></option>
										<?php else : ?>
											<option value="<?php echo esc_attr( $value[1] ); ?>"> <?php echo esc_html( $value[0] ); ?></option>
										<?php endif; ?>
										?>
									<?php } ?>
							</select>
						</div>

						<div id="footer_section">
							<div id="log_content">
								<?php
								$logUrl = WC_LOG_DIR . $product_job;
									
								if (file_exists($logUrl)) {
									$data = file_get_contents($logUrl);
										
									if (false !== $data) {
										$entries = preg_split('/(\\d{2}-\\d{2}-\\d{4} @ \\d{2}:\\d{2}:\\d{2} - )|(\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\+\\d{2}:\\d{2} NOTICE )/', $data, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
										$grouped_entries = [];
										// pr($entries);die();
											
										for ($i = 0; $i < count($entries); $i += 2) {
											$grouped_entries[] = [$entries[$i], $entries[$i + 1]];
										}
										$log_entries = array_reverse($grouped_entries);
										?>
											<table id="opmc-odoo-logs-table" class="wp-list-table widefat fixed striped table-view-list">
												<thead>
												<tr>
													<th class="column-tags" id="sort-date">Date</th>
													<th class="column-tags" id="sort-process">Process</th>
													<th class="column-tags" id="sort-status">Status</th>
													<th>Message</th>
												</tr>
												</thead>
												<tbody>
											<?php
											foreach ( $log_entries as $entry ) {
												if ( '' != $entry ) :
													if (strpos($entry[0], 'NOTICE') !== false) {
														$date = explode( '+00:00 NOTICE', $entry[0] );
														$log_date = isset($entry[0]) ? rtrim(str_replace('T', ' at ', $date[0] )) : '';
													} else {
														$log_date = isset($entry[0]) ? rtrim(str_replace('@', 'at', $entry[0]), ' - ') : '';
													}
													$log_message_array = $this->explodeLogMessage($entry[1]);
															
													if (is_array($log_message_array)) {
														$log_name = $log_message_array[0];
														$log_state = $log_message_array[1];
														$log_message = $log_message_array[2];
													} else {
														continue;
													}
															
													?>
															<tr>
																<td><?php echo esc_html( $log_date ); ?></td>
																<td><?php echo esc_html( $log_name ); ?></td>
																<td><?php echo esc_html( $log_state ); ?></td>
																<td><?php echo wp_kses_post( $log_message ); ?></td>
															</tr>
														<?php
														
														endif;
											}
											?>
												</tbody>
											</table>
											<?php
									} else {
										?>
											<div id="log_content">
												There are currently no logs to view.
											</div>
										<?php
									}
								} else {
									?>
										<div id="log_content">
											There are currently no logs to view.
										</div>
									<?php
								}
								?>
							</div>

						</div>
					<?php else : ?>
						<div id="footer_section">
							<div id="log_content">
								There are currently no logs to view.
							</div>
						</div>
					<?php endif; ?>
			</div>
		</section>
	</div>
</div>

<script type="text/javascript">

	sessionState = sessionStorage.getItem("logviewbtnActive");
	console.log(sessionState);
	if( sessionState === "logview" ) {
		jQuery("#tab4").attr('checked', 'checked');
	}

	jQuery('#tab1').click(function(){

		sessionStorage.removeItem('logviewbtnActive');
	});

	jQuery('#tab2').click(function(){
		sessionStorage.removeItem('logviewbtnActive');
	});

	jQuery('#tab3').click(function(){
		sessionStorage.removeItem('logviewbtnActive');
	});
	jQuery('#tab4').click(function(){
		sessionStorage.removeItem('logviewbtnActive');

	});
	jQuery('#tab5').click(function(){
		sessionStorage.removeItem('logviewbtnActive');

	});


	jQuery('#view_log').click(function(e){
		e.preventDefault();
		var selected = jQuery('#logs :selected').val();
		var selected2 = jQuery('#logs :selected').text();
		jQuery.ajax({
			data: {action: 'odoo_view_debug_logs', selected:selected,  selected2:selected2, security:odoo_admin.ajax_nonce},
			type: 'post',
			url: ajaxurl,
			success: function(data) {
				var obj = jQuery.parseJSON(data);
				if(obj.result === 'success'){
					console.log(obj);
					sessionStorage.setItem("logviewbtnActive", "logview");
					location.reload();
				} else {
					alert('failed');
				}
			}
		});
	});

	/* PLUGINS-2244 End */


	jQuery(function(){
		jQuery(".datepicker_min").datepicker({ dateFormat: 'yy-mm-dd' });
		jQuery(".datepicker_max").datepicker({ dateFormat: 'yy-mm-dd' });
		jQuery('#odoo_export_product_sync').click(function(e){
			var dateFrom = jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_product_from_date').val();
			var dateTo = jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_product_to_date').val();
			jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_product_from_date').css({'border':'1px solid #8c8f94'});
			jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_product_to_date').css({'border':'1px solid #8c8f94'});
			e.preventDefault();
			if(dateFrom !=='' && dateTo !== ''){
				if(confirm('Are you sure you want to sync the product with odoo? ')){
					jQuery('#odoo_export_product_sync').css({'display':'none'});
					jQuery('#odoo_export_product_sync_loading').css({'display':'inline-block'});
					jQuery('.odoo_export_product_sync_message').html('');
					jQuery.ajax({
						data: {action: 'odoo_export_product_by_date', dateFrom:dateFrom,dateTo:dateTo, security:odoo_admin.ajax_nonce},
						type: 'post',
						url: ajaxurl,
						success: function(data) {
							var obj = jQuery.parseJSON(data);
							if(obj.result === 'success'){
								jQuery('#odoo_export_product_sync').css({'display':'inline-block'});
								jQuery('#odoo_export_product_sync_loading').css({'display':'none'});
								jQuery('.odoo_export_product_sync_message').html("<?php echo esc_html__( 'Product sync successfully.', 'wc-odoo-integration' ); ?>");
							}else{
								jQuery('#odoo_export_product_sync').css({'display':'inline-block'});
								jQuery('#odoo_export_product_sync_loading').css({'display':'none'});
							}
						}
					});
				}
			}else{
				if(dateFrom === ''){
					jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_product_from_date').css({'border':'1px solid #c50a0a'});
				}
				if(dateTo === ''){
					jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_product_to_date').css({'border':'1px solid #c50a0a'});
				}
			}
		});

		jQuery('#odoo_export_customer_sync').click(function(e){
			e.preventDefault();
			var dateFrom = jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_from_date').val();
			var dateTo = jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_to_date').val();
			jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_from_date').css({'border':'1px solid #8c8f94'});
			jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_to_date').css({'border':'1px solid #8c8f94'});
			if(dateFrom !=='' && dateTo !== ''){
				if(confirm('Are you sure you want to sync the customer with odoo? ')){
					jQuery('#odoo_export_customer_sync').css({'display':'none'});
					jQuery('#odoo_export_customer_sync_loading').css({'display':'inline-block'});
					jQuery('.odoo_export_customer_sync_message').html('');
					jQuery.ajax({
						data: {action: 'odoo_export_customer_by_date', dateFrom:dateFrom,dateTo:dateTo, security:odoo_admin.ajax_nonce},
						type: 'post',
						url: ajaxurl,
						success: function(data) {
							var obj = jQuery.parseJSON(data);
							if(obj.result === 'success'){
								jQuery('#odoo_export_customer_sync').css({'display':'inline-block'});
								jQuery('#odoo_export_customer_sync_loading').css({'display':'none'});
								jQuery('.odoo_export_customer_sync_message').html("<?php echo esc_html__( 'Customer sync  successfully .', 'wc-odoo-integration' ); ?>");
							}else{
								jQuery('#odoo_export_customer_sync').css({'display':'inline-block'});
								jQuery('#odoo_export_customer_sync_loading').css({'display':'none'});
							}
						}
					});
				}
			}else{
				if(dateFrom === ''){
					jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_from_date').css({'border':'1px solid #c50a0a'});
				}
				if(dateTo === ''){
					jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_export_customer_to_date').css({'border':'1px solid #c50a0a'});
				}
			}
		});

		jQuery('#odoo_import_customer_sync').click(function(e){
			e.preventDefault();
			var dateFrom = jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_from_date').val();
			var dateTo = jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_to_date').val();
			jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_from_date').css({'border':'1px solid #8c8f94'});
			jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_to_date').css({'border':'1px solid #8c8f94'});

			if(dateFrom !=='' && dateTo !== ''){
				if(confirm('Are you sure you want to sync the customer with odoo? ')){
					jQuery('#odoo_import_customer_sync').css({'display':'none'});
					jQuery('#odoo_import_customer_sync_loading').css({'display':'inline-block'});
					jQuery('.odoo_import_customer_sync_message').html('');
					jQuery.ajax({
						data: {action: 'odoo_import_customer_by_date', dateFrom:dateFrom,dateTo:dateTo, security:odoo_admin.ajax_nonce},
						type: 'post',
						url: ajaxurl,
						success: function(data) {
							var obj = jQuery.parseJSON(data);
							if(obj.result === 'success'){
								jQuery('#odoo_import_customer_sync').css({'display':'inline-block'});
								jQuery('#odoo_import_customer_sync_loading').css({'display':'none'});
								jQuery('.odoo_import_customer_sync_message').html("<?php echo esc_html__( 'Customer has been successfully sync.', 'wc-odoo-integration' ); ?>");
							}else{
								jQuery('#odoo_import_customer_sync').css({'display':'inline-block'});
								jQuery('#odoo_import_customer_sync_loading').css({'display':'none'});
							}
						}
					});
				}
			}else{
				if(dateFrom === ''){
					jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_from_date').css({'border':'1px solid #c50a0a'});
				}
				if(dateTo === ''){
					jQuery('#woocommerce_woocommmerce_odoo_integration_odoo_import_customer_to_date').css({'border':'1px solid #c50a0a'});
				}
			}
		});


	});


</script>
