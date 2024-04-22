<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\RequestAQuote
 * @var WP_List_Table $table
 * @var bool $is_blank
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


?>
<div class="ywraq-admin-wrap-content">


	<?php
	/**
	 * This file belongs to the YIT Plugin Framework.
	 *
	 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
	 * that is bundled with this package in the file LICENSE.txt.
	 * It is also available through the world-wide-web at this URL:
	 * http://www.gnu.org/licenses/gpl-3.0.txt
	 *
	 * @package YITH\RequestAQuote
	 */

	/**
	 * Admin View: Exclusions List Table
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly.
	}
	?>
	<div class="wrap ywpar-exclusion-list-tab">
		<a class="button-primary ywraq-add-exclusion" href="#"><?php esc_html_e( '+ Add exclusion', 'yith-woocommerce-request-a-quote' ); ?></a>

		<?php if ( $is_blank ) : ?>
			<div class="ywraq-admin-no-posts">
				<div class="ywraq-admin-no-posts-container">
					<div class="ywraq-admin-no-posts-logo"><img width="80" src="<?php echo esc_url( YITH_YWRAQ_ASSETS_URL . '/icons/exclusion-list.svg' ); ?>"></div>
					<div class="ywraq-admin-no-posts-text">
						<span>
							<strong><?php echo esc_html_x( 'You don\'t have any exclusion yet.', 'Text showed when the list of quotes is empty.', 'yith-woocommerce-request-a-quote' ); ?></strong>
						</span>
						<p><?php echo esc_html_x( 'Click on "Add exclusion" button to exclude a product, a category or a tag!', 'Text showed when the list of quotes is empty.', 'yith-woocommerce-request-a-quote' ); ?></p>
					</div>
				</div>
			</div>
		<?php else : ?>

		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( $get['page'] ); ?>">
			<input type="hidden" name="tab" value="exclusions">
			<?php $table->display(); ?>
		</form>

		<?php endif; ?>

	</div>
</div>
<div id="yith-exclusion-list__delete_row"
	title="<?php esc_html_e( 'Remove item', 'yith-woocommerce-request-a-quote' ); ?>"
	style="display:none;">
	<p><?php esc_html_e( 'This item will be removed from the list.', 'yith-woocommerce-request-a-quote' ); ?>
		<br>
		<?php esc_html_e( 'Do you wish to continue?', 'yith-woocommerce-request-a-quote' ); ?></p>
</div>

<div class="yith-exclusion-list__popup_wrapper">
	<form method="post" class="yith-exclusion-list__form_row">
		<input type="hidden" name="_nonce"
			value="<?php echo sanitize_key( wp_create_nonce( 'yith_ywraq_add_exclusions' ) ); ?>"/>
		<div class="ywraq-field">
			<label for="ywraq-exclusion-type"><?php esc_html_e( 'Exclusion type', 'yith-woocommerce-request-a-quote' ); ?></label>
			<div class="ywraq-field-input">
				<select name="ywraq-exclusion-type" id="ywraq-exclusion-type" class="wc-enhanced-select" style="width: 300px">
					<option
						value="product"><?php esc_html_e( 'Product', 'yith-woocommerce-request-a-quote' ); ?></option>
					<option
						value="product_cat"><?php esc_html_e( 'Category', 'yith-woocommerce-request-a-quote' ); ?></option>
					<option
						value="product_tag"><?php esc_html_e( 'Tag', 'yith-woocommerce-request-a-quote' ); ?></option>
				</select>

				<span class="description"><?php esc_html_e( 'Choose if you want to add a product, a category or a tag.', 'yith-woocommerce-request-a-quote' ); ?></span>
			</div>
		</div>
		<div class="ywraq-field ywraq-exclusion-field ywraq-exclusion-product" dep-value="product">
			<label for="ywraq-exclusion-type"><?php esc_html_e( 'Choose products to add', 'yith-woocommerce-request-a-quote' ); ?></label>
			<div class="ywraq-field-input">
				<?php
				yit_add_select2_fields(
					array(
						'style'            => 'width: 300px;display: inline-block;',
						'class'            => 'wc-product-search',
						'id'               => 'add_products',
						'name'             => 'add_products',
						'data-placeholder' => __( 'Search product...', 'yith-woocommerce-request-a-quote' ),
						'data-multiple'    => true,
						'data-action'      => 'yith_ywraq_search_products',
					)
				);
				?>

				<span class="description"><?php esc_html_e( 'Choose the products to add to the exclusion list', 'yith-woocommerce-request-a-quote' ); ?></span>
			</div>
		</div>
		<div class="ywraq-field ywraq-exclusion-field ywraq-exclusion-category" dep-value="product_cat">
			<label for="ywraq-exclusion-type"><?php esc_html_e( 'Choose categories to add', 'yith-woocommerce-request-a-quote' ); ?></label>
			<div class="ywraq-field-input">
				<?php
				yit_add_select2_fields(
					array(
						'style'            => 'width: 300px;display: inline-block;',
						'class'            => 'wc-product-search',
						'id'               => 'add_categories',
						'name'             => 'add_categories',
						'data-placeholder' => __( 'Search category...', 'yith-woocommerce-request-a-quote' ),
						'data-multiple'    => true,
						'data-action'      => 'yith_ywraq_search_categories',
					)
				);
				?>

				<span class="description"><?php esc_html_e( 'Choose the categories to add to the exclusion list', 'yith-woocommerce-request-a-quote' ); ?></span>
			</div>
		</div>
		<div class="ywraq-field ywraq-exclusion-field ywraq-exclusion-tag" dep-value="product_tag">
			<label for="ywraq-exclusion-type"><?php esc_html_e( 'Choose tags to add', 'yith-woocommerce-request-a-quote' ); ?></label>
			<div class="ywraq-field-input">
				<?php
				yit_add_select2_fields(
					array(
						'style'            => 'width: 300px;display: inline-block;',
						'class'            => 'wc-product-search',
						'id'               => 'add_tags',
						'name'             => 'add_tags',
						'data-placeholder' => __( 'Search tag...', 'yith-woocommerce-request-a-quote' ),
						'data-multiple'    => true,
						'data-action'      => 'yith_ywraq_search_tags',
					)
				);
				?>

				<span class="description"><?php esc_html_e( 'Choose the tags to add to the exclusion list', 'yith-woocommerce-request-a-quote' ); ?></span>
			</div>
		</div>
	</form>
</div>
