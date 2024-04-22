<?php
/**
 * Default form Framework Field Template.
 *
 * @package YITH\RequestAQuote
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @var array $field
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

wp_enqueue_script( 'yith_default_form_field' );
wp_enqueue_style( 'yith_default_form_field' );

$id     = $field['id']; //phpcs:ignore
$values = get_option( $id );
if ( empty( $values ) ) {
	$values = call_user_func_array( $field['callback_default_form'], array() );
	update_option( $id, $values );
}

global $wpdb;

$upload_max_filesize = isset( $wpdb->qm_php_vars['upload_max_filesize'] ) ? str_replace( 'M', '', $wpdb->qm_php_vars['upload_max_filesize'] ) : '';
$columns             = array(
	'name' => array(
		'label'         => esc_html_x( 'Name', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => true,
		'type'          => 'text',
	),

	'type' => array(
		'label'         => esc_html_x( 'Type', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => true,
		'default'       => 'text',
		'type'          => 'select',
		'class'         => 'wc-enhanced-select',
		'options'       => YIT_Plugin_Default_Form()->get_field_types(),
	),

	'id' => array(
		'label'         => esc_html_x( 'ID', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'select',
		'options'       => array(
			'billing_state'  => 'billing_state',
			'shipping_state' => 'shipping_state',
		),
		'class'         => 'wc-enhanced-select',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'state',
		),
	),

	'class' => array(
		'label'         => esc_html_x( 'Class', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'description'   => esc_html_x( 'Separate classes with commas.', 'Default form description', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'text',
	),

	'label' => array(
		'label'         => esc_html_x( 'Label', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => true,
		'type'          => 'text',
	),

	'label_class' => array(
		'label'         => esc_html_x( 'Label Class', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'description'   => esc_html_x( 'Separate classes with commas.', 'Default form description', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'text',
	),

	'placeholder' => array(
		'label'         => esc_html_x( 'Placeholder', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'text',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'text|email|tel|textarea|select|ywraq_multiselect|ywraq_datepicker|ywraq_timepicker',
		),
	),

	'description' => array(
		'label'         => esc_html_x( 'Description', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'description'   => esc_html_x( 'You can use the shortcode [terms] and [privacy_policy]', 'Default form description', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'textarea',
		'rows'          => 5,
		'columns'       => 10,
		'deps'          => array(
			'id'     => 'type',
			'values' => 'ywraq_acceptance',
		),
	),

	'upload_allowed_extensions' => array(
		'label'         => esc_html_x( 'Allowed extensions', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'description'   => esc_html_x( 'Add a list of allowed extensions comma separated.', 'Default form description', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'default'       => apply_filters( 'ywraq_allowed_extension_for_upload_field', 'jpg,doc,png' ),
		'type'          => 'text',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'ywraq_upload',
		),
	),

	'max_filesize' => array(
		'label'         => esc_html_x( 'Max filesize (MB):', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'description'   => esc_html_x( 'Add the max file size of upload file.', 'Default form description', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'default'       => $upload_max_filesize,
		'type'          => 'text',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'ywraq_upload',
		),
	),

	'position' => array(
		'label'         => esc_html_x( 'Position', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'select',
		'class'         => 'wc-enhanced-select',
		'options'       => ywraq_get_array_positions_form_field(),
		'default'       => 'form-row-wide',
	),

	'connect_to_field' => array(
		'label'         => esc_html_x( 'Connect to', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => true,
		'type'          => 'select',
		'options'       => ywraq_get_connect_fields(),
		'class'         => 'wc-enhanced-select',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'text|email|tel|textarea|radio|checkbox|select|country|state|ywraq_multiselect|ywraq_datepicker|ywraq_timepicker',
		),
	),

	'options' => array(
		'label'         => esc_html_x( 'Options', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'description'   => '',
		'show_on_table' => false,
		'default'       => '',
		'type'          => 'option_list',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'radio|select|ywraq_multiselect',
		),
	),

	'validation-rules' => array(
		'label'         => esc_html_x( 'Validation', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'select',
		'class'         => 'wc-enhanced-select',
		'options'       => ywraq_get_array_validation_form_field(),
		'deps'          => array(
			'id'     => 'type',
			'values' => 'text|password|tel|textarea|state|country|ywraq_upload|email',
		),
	),

	'required' => array(
		'label'         => esc_html_x( 'Required', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => true,
		'type'          => 'onoff',
		'default'       => 'no',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'text|email|password|tel|checkbox|textarea|radio|state|country|ywraq_upload|select|country|state|ywraq_multiselect|ywraq_datepicker|ywraq_timepicker|ywraq_acceptance',
		),
	),

	'checked' => array(
		'label'         => esc_html_x( 'Checked', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => false,
		'type'          => 'onoff',
		'deps'          => array(
			'id'     => 'type',
			'values' => 'checkbox',
		),
	),

	'enabled' => array(
		'label'         => esc_html_x( 'Activate', 'Default form column', 'yith-woocommerce-request-a-quote' ),
		'show_on_table' => true,
		'show_on_popup' => false,
		'default'       => 'yes',
	),

	'actions' => array(
		'label'         => '',
		'show_on_table' => true,
		'show_on_popup' => false,

	),
);

$custom_attributes = isset( $field['custom_attributes'] ) ? (array) $field['custom_attributes'] : '';
$custom_attributes = implode( ' ', $custom_attributes );

?>


<div class="yith-default-form" data-option-id="<?php echo esc_attr( $id ); ?>"
     data-callback="<?php echo esc_attr( $field['callback_default_form'] ); ?>" <?php echo( $custom_attributes ); //phpcs:ignore ?>>
	<div class="yith-default-form__actions">
		<button id="yith-default-form__add-fields"
				class="yith-default-form__add-fields button-primary"><?php esc_html_e( 'Add field', 'yith-woocommerce-request-a-quote' ); ?></button>
		<button id="yith-default-form__restore-default"
				class="yith-default-form__restore-default button-secondary yith-button-ghost"><?php esc_html_e( 'Restore Default', 'yith-woocommerce-request-a-quote' ); ?></button>
	</div>

	<div class="yith-default-form__form_table">
		<?php do_action( 'yith_default_form_before_table', $field ); ?>
		<table class="yith-default-form-main-table">
			<thead>
			<tr>
				<?php
				foreach ( $columns as $key => $column ) :
					if ( isset( $column['show_on_table'] ) && $column['show_on_table'] ) :
						?>
						<th class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $column['label'] ); ?></th>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
			</thead>
			<tbody class="ui-sortable">
			<?php if ( $values ) : ?>
				<?php
				foreach ( $values as $name => $value ) :

					$can_be_trashed = ! isset( $value['standard'] ) || ! $value['standard'];
					?>
					<tr>
						<?php
						foreach ( $columns as $key => $column ) :

							$current_default = isset( $column['default'] ) ? $column['default'] : '';
							if ( 'name' === $key ) {
								$current_value = $name;
							} else {
								$current_value = isset( $value[ $key ] ) ? $value[ $key ] : $current_default;
							}


							if ( is_array( $current_value ) ) {
								if ( empty( $current_value ) ) {
									$current_value = '';
								} elseif ( 'options' === $key ) {
									$current_value = YIT_Plugin_Default_Form()->print_options_field( $current_value );
								} else {
									$current_value = is_array( $current_value ) && ! empty( $current_value ) ? implode( ',', $current_value ) : $current_value;
								}
							}

							?>
							<input type="hidden" name="field_<?php echo esc_attr( $key ); ?>[]"
									data-name="<?php echo esc_attr( $key ); ?>"
									value="<?php echo esc_attr( $current_value ); ?>"
									data-default="<?php echo esc_attr( $current_default ); ?>"/>
							<?php
							if ( isset( $column['type'] ) && 'select' === $column['type'] ) {
								$current_value = is_array( $current_value ) ? implode( ',', $current_value ) : $current_value;
							}

							if ( isset( $column['show_on_table'] ) && $column['show_on_table'] ) :

								if ( 'enabled' === $key ) :
									$is_disabled = in_array( $name, array( 'first_name', 'email' ), true );
									$current_value = $is_disabled ? 'yes' : $current_value;
									?>
									<td class="<?php echo esc_attr( $key ); ?>">
										<?php
										yith_plugin_fw_get_field(
											array(
												'class'             => $is_disabled ? 'yith-disabled' : '',
												'type'              => 'onoff',
												'id'                => $key,
												'name'              => $key,
												'value'             => $current_value,
												'custom_attributes' => ! ! $is_disabled ? array( 'disabled' => 'disabled' ) : array(),
											),
											true
										)
										?>
									</td>

								<?php elseif ( 'actions' === $key ) :
									$actions = array();

									$actions['edit']  = array(
										'type'  => 'action-button',
										'title' => _x( 'Edit', 'Tip to edit the field inside the default form', 'yith-woocommerce-request-a-quote' ),
										'icon'  => 'edit',
										'url'   => '',
										'class' => 'action__edit'
									);
									$actions['clone'] = array(
										'type'  => 'action-duplicate',
										'title' => _x( 'Duplicate', 'Tip to clone the field inside the default form', 'yith-woocommerce-request-a-quote' ),
										'icon'  => 'clone',
										'url'   => '',
										'class' => 'action__duplicate'
									);

									if ( $can_be_trashed ) {
										$actions['delete'] = array(
											'type'   => 'action-button',
											'title'  => _x( 'Delete', 'Tip to delete the product inside the exclusion list ', 'yith-woocommerce-request-a-quote' ),
											'icon'   => 'trash',
											'url'    => '',
											'action' => 'delete',
											'class'  => 'action__trash'
										);
									}
									$actions['drag'] = array(
										'type'  => 'action-sort',
										'title' => _x( 'Sort', 'Tip to sort the field inside the default form', 'yith-woocommerce-request-a-quote' ),
										'icon'  => 'drag',
										'url'   => '',
										'class' => 'action__sort'
									);


									?>
									<td class="<?php echo esc_attr( $key ); ?>">
										<div>
											<?php yith_plugin_fw_get_action_buttons( $actions, true ); ?>
										</div>

									</td>
								<?php elseif ( 'required' === $key ) : ?>
									<td class="<?php echo esc_attr( $key ); ?>">
										<?php
										if ( 'yes' === $current_value ) {
											echo '<div class="field_required"></div>';
										} else {
											echo '-';
										}
										?>
									</td>
									<?php
								else :
									if ( isset( $column['options'], $column['options'][ $current_value ] ) ) {
										$current_value = $column['options'][ $current_value ];
									}
									?>
									<td class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $current_value ); ?></td>
								<?php endif; ?>
							<?php endif; ?>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
			<tfoot>

			</tfoot>
		</table>
		<?php do_action( 'yith_default_form_after_table', $field ); ?>
	</div>

	<div id="yith-default-form__delete_row"
			title="<?php esc_html_e( 'Remove field', 'yith-woocommerce-request-a-quote' ); ?>"
			style="display:none;">
		<p><?php esc_html_e( 'This field will be removed from the form.', 'yith-woocommerce-request-a-quote' ); ?>
			<br>
			<?php esc_html_e( 'Do you wish to continue?', 'yith-woocommerce-request-a-quote' ); ?></p>
	</div>
	<div id="yith-default-form__reset_dialog"
			title="<?php esc_html_e( 'Restore default', 'yith-woocommerce-request-a-quote' ); ?>"
			style="display:none;">
		<p><?php esc_html_e( 'All fields will be removed from the form and will be replaced with the default form fields.', 'yith-woocommerce-request-a-quote' ); ?>
			<br>
			<?php esc_html_e( 'Do you wish to continue?', 'yith-woocommerce-request-a-quote' ); ?></p>
	</div>
	<div class="yith-default-form__popup_wrapper">
		<div class="yith-default-form__form_row">
			<table id="yith_form_fields_table">

				<?php
				foreach ( $columns as $name => $column ) :
					$value = isset( $column['default'] ) ? $column['default'] : '';
					$show = isset( $column['show_on_popup'] ) ? $column['show_on_popup'] : true;
					$custom_attributes = isset( $column['custom_attributes'] ) ? ' ' . $column['custom_attributes'] . ' ' : '';
					$custom_attributes .= isset( $column['class'] ) ? ' class="' . $column['class'] . '" ' : '';
					$custom_attributes .= isset( $column['deps'], $column['deps']['id'], $column['deps']['values'] ) ? ' data-deps="' . $column['deps']['id'] . '" data-deps_value="' . $column['deps']['values'] . '" ' : '';

					if ( ! $show ) :
						?>
						<input type="hidden" name="<?php echo esc_attr( $name ); ?>"
								value="<?php echo esc_attr( $value ); ?>">
						<?php
						continue;
					endif;
					?>

					<tr>
						<th class="label"> <?php echo( isset( $column['label'] ) ? esc_html( $column['label'] ) : '' ); ?> </th>
						<td>
							<?php
							switch ( $column['type'] ) {
								case 'text':
									?>
									<input type="text" id="<?php echo esc_attr( $name ); ?>"
											name="<?php echo esc_attr( $name ); ?>" <?php echo $custom_attributes; ?>/>
									<?php
									break;
								case 'select':
									if ( $column['options'] ) :

										?>
										<select name="<?php echo esc_attr( $name ); ?>"
												id="<?php echo esc_attr( $name ); ?>" <?php echo $custom_attributes; ?> >
											<?php foreach ( $column['options'] as $value => $label ) : ?>
												<option
														value="<?php echo wp_kses_post( $value ); ?>"><?php echo wp_kses_post( $label ); ?></option>
											<?php endforeach; ?>
										</select>
										<?php
									endif;
									break;
								case 'textarea':
									$col = isset( $column['colums'] ) ? $column['colums'] : 10;
									$row = isset( $column['rows'] ) ? $column['rows'] : 5;
									?>
									<textarea id="<?php echo esc_attr( $name ); ?>"
												name="<?php echo esc_attr( $name ); ?>"
												cols="<?php echo esc_attr( $col ); ?>"
												rows="<?php echo esc_attr( $row ); ?>>" <?php echo $custom_attributes; ?>></textarea>
									<?php
									break;
								case 'option_list':

									$col = isset( $column['colums'] ) ? $column['colums'] : 10;
									$row = isset( $column['rows'] ) ? $column['rows'] : 5;
									?>
									<table class="option-list" <?php echo $custom_attributes; ?>>
										<thead>
											<tr>
												<th class="column-label"><?php esc_html_e('Label', 'Label column of an option in request a quote page form tab', 'yith-woocommerce-request-a-quote'); ?></th>
												<th class="column-value"><?php esc_html_e('Value', 'Label value of an option in request a quote page form tab', 'yith-woocommerce-request-a-quote'); ?></th>
												<th class="column-actions"></th>
											</tr>
										</thead>
										<tbody class="ui-sortable">
											<tr data-key="{key}">
												<td class="column-label"><input type="text" name="<?php echo esc_attr( $name ); ?>[{key}][label]" id="<?php echo esc_attr( $name ); ?>_{key}_label" value=""></td>
												<td class="column-value"><input type="text" name="<?php echo esc_attr( $name ); ?>[{key}][value]" id="<?php echo esc_attr( $name ); ?>_{key}_value" value=""></td>
												<td class="column-actions"><span class="drag yith-icon yith-icon-drag ui-sortable-handle"></span>
													<a href="#" role="button" class="delete yith-icon yith-icon-trash"></a></td>
											</tr>
										</tbody>
									</table>
									<a href="#" role="button" id="add_new_option"><?php echo esc_html_x('+ Add new option', 'text of link to add a new option', 'yith-woocommerce-request-a-quote'); ?></a>
									<?php
									break;
								case 'onoff':
									yith_plugin_fw_get_field(
										array(
											'type'              => 'onoff',
											'id'                => $name,
											'name'              => $name,
											'value'             => 'yes',
											'custom_attributes' => $custom_attributes,
										),
										true
									);
							}
							?>

							<?php if ( isset( $column['description'] ) ) : ?>
								<div class="description"><?php echo esc_html( $column['description'] ); ?></div>
							<?php endif; ?>
							<?php if ( 'name' === $name ) : ?>
								<div class="description field-exists">
									<?php esc_html_e( 'This field is already defined', 'yith-woocommerce-request-a-quote' ); ?>
								</div>
								<div class="description required">
									<?php esc_html_e( 'This field is required', 'yith-woocommerce-request-a-quote' ); ?>
								</div>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
	</div>
</div>
