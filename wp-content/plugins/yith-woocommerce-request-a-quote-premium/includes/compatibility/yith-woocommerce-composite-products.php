<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements the YWRAQ_YITH_Composite_Products class.
 *
 * @class   YWRAQ_YITH_Composite_Products
 * @since   1.5.5
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\RequestAQuote
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YWRAQ_YITH_Composite_Products' ) ) {

	/**
	 * Class YWRAQ_YITH_Composite_Products
	 */
	class YWRAQ_YITH_Composite_Products {

		/**
		 * Single instance of the class
		 *
		 * @var \YWRAQ_WooCommerce_Composite_Products
		 */
		protected static $instance;

		/**
		 * YITH WCP Cart
		 *
		 * @var YITH_WCP_Cart|null
		 */
		protected $_yith_wcp_cart; //phpcs:ignore

		/**
		 * Returns single instance of the class
		 *
		 * @return YWRAQ_WooCommerce_Composite_Products
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 */
		public function __construct() {

			$yith_wcp = YITH_WCP();

			if ( ! isset( $yith_wcp->frontend ) ) {
				$yith_wcp->frontend = new YITH_WCP_Frontend( $yith_wcp );
			}

			$this->_yith_wcp_cart = $yith_wcp->frontend->getCartObject();

			// add to quote button.
			add_filter( 'ywraq_ajax_add_item_prepare', array( $this, 'ajax_add_item' ), 10, 2 );
			add_filter( 'ywraq_add_item', array( $this, 'add_item' ), 10, 2 );

			// hide price in single product page.
			add_action( 'wp_enqueue_scripts', array( $this, 'add_css_to_hide_composite_prices' ), 99 );

			// table.
			add_filter( 'yith_ywraq_item_class', array( $this, 'add_class_to_composite_parent' ), 10, 3 );
			add_action( 'ywraq_after_request_quote_view_item', array( $this, 'show_composite_data' ), 10, 2 );
			add_action( 'ywraq_mini_widget_view_item', array( $this, 'show_composite_data_in_widget' ), 10, 2 );
			add_action( 'ywraq_list_widget_view_item', array( $this, 'show_composite_data_in_widget' ), 10, 2 );
			add_action( 'ywraq_quote_adjust_price', array( $this, 'adjust_price' ), 10, 2 );

			add_action(
				'ywraq_after_request_quote_view_item_on_email',
				array( $this, 'show_composit_data_on_email' ),
				10,
				2
			);

			// order.
			add_action( 'ywraq_from_cart_to_order_item', array( $this, 'add_order_item_meta' ), 10, 3 );
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_order_itemmeta' ), 10, 1 );
			add_filter( 'ywraq_formatted_line_total', array( $this->_yith_wcp_cart, 'order_item_subtotal' ), 10, 3 );
		}

		/**
		 * Prepare the item before adding it to list
		 *
		 * @param   array $postdata    Postdada.
		 * @param   int   $product_id  Product id.
		 *
		 * @return array
		 */
		public function ajax_add_item( $postdata, $product_id ) {

			if ( empty( $postdata ) ) {
				$postdata = array();
			}

			$postdata['add-to-cart'] = $product_id;

			$ywcp_composite_data = $this->_yith_wcp_cart->add_cart_item_data( null, $product_id, $postdata );
			if ( ! empty( $ywcp_composite_data ) ) {
				$postdata = array_merge( $ywcp_composite_data, $postdata );
			}

			return $postdata;
		}

		/**
		 * Add item to list.
		 *
		 * @param   array $product_raq  Product to add.
		 * @param   array $raq          RAQ list.
		 *
		 * @return mixed
		 */
		public function add_item( $product_raq, $raq ) {

			if ( isset( $product_raq['yith_wcp_component_data'] ) ) {
				$raq['yith_wcp_component_data'] = $product_raq['yith_wcp_component_data'];
			}

			return $raq;
		}


		/**
		 * Add class to composite parent.
		 *
		 * @param   string $class  Class.
		 * @param   array  $raq    RAQ list.
		 * @param   string $key    RAQ item key.
		 *
		 * @return string
		 */
		public function add_class_to_composite_parent( $class, $raq, $key ) {

			if ( array_key_exists( 'yith_wcp_component_data', $raq[ $key ] ) ) {
				$class .= ' ywcp_component_item';
			}

			return $class;
		}

		/**
		 * Show composite data.
		 *
		 * @param   array  $raq  RAQ list.
		 * @param   string $key  Item key.
		 */
		public function show_composite_data( $raq, $key ) {

			if ( array_key_exists( 'yith_wcp_component_data', $raq[ $key ] ) ) {
				$product        = wc_get_product( $raq[ $key ]['yith-add-to-cart'] );
				$component_data = $raq[ $key ]['yith_wcp_component_data'];

				if ( $product->is_type( 'yith-composite' ) && isset( $component_data['selection_variation_data'] ) ) {

					$composite_quantity = $raq[ $key ]['quantity'];

					$composite_stored_data = $product->getComponentsData();

					$show_prices       = 'no' === get_option( 'ywraq_hide_price', 'no' );
					$show_thumbnail    = apply_filters(
						'ywraq_item_thumbnail',
						ywraq_show_element_on_list( 'images' )
					);
					$show_single_price = $show_prices && ywraq_show_element_on_list( 'single_price' );
					$show_line_total   = $show_prices && ywraq_show_element_on_list( 'line_total' );
					$show_quantity     = ywraq_show_element_on_list( 'quantity' );

					foreach ( $composite_stored_data as $wcp_key => $component_item ) {

						if ( isset( $component_data['selection_data'][ $wcp_key ] ) ) {

							if ( $component_data['selection_data'][ $wcp_key ] > 0 ) {

								// variation selected.
								if ( isset( $component_data['selection_variation_data'][ $wcp_key ] ) && $component_data['selection_variation_data'][ $wcp_key ] > 0 ) {
									$child_product = wc_get_product( $component_data['selection_variation_data'][ $wcp_key ] );
								} else {
									$child_product = wc_get_product( $component_data['selection_data'][ $wcp_key ] );
								}

								if ( ! $child_product ) {
									continue;
								}

								YITH_WCP_Frontend::markProductAsCompositeProcessed(
									$child_product,
									$product->get_id(),
									$wcp_key
								);

								$child_quantity = $component_data['selection_quantity'][ $wcp_key ];

								$wcp_component_item = $product->getComponentItemByKey( $wcp_key );
								$sold_individually  = isset( $wcp_component_item['sold_individually'] ) && $wcp_component_item['sold_individually'] ? $wcp_component_item['sold_individually'] : false;

								?>
								<tr class="cart_item ywcp_component_child_item"
									data-wcpkey="<?php echo esc_attr( $key ); ?>">
									<td class="product-remove">
									</td>
									<?php if ( $show_thumbnail ) : ?>
										<td class="product-thumbnail">
											<?php
											$thumbnail = $child_product->get_image();

											if ( ! $child_product->is_visible() ) {
												echo wp_kses_post( $thumbnail );
											} else {
												printf(
													'<a href="%s">%s</a>',
													esc_url( $child_product->get_permalink() ),
													wp_kses_post( $thumbnail )
												);
											}
											?>
										</td>
									<?php endif; ?>
									<td class="product-name">
										<?php

										$title = $child_product->get_title();

										if ( $child_product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
											$sku    = apply_filters(
												'ywraq_sku_label',
												__(
													' SKU:',
													'yith-woocommerce-request-a-quote'
												)
											) . $child_product->get_sku();
											$title .= apply_filters( 'ywraq_sku_label_html', $sku, $child_product );
										}

										echo sprintf(
											'<strong>%s</strong><br>',
											wp_kses_post( $component_item['name'] )
										);
										?>
										<a href="<?php echo esc_url( $child_product->get_permalink() ); ?>"><?php echo wp_kses_post( $title ); ?></a>
										<?php
										// Meta data.

										$item_data = array();
										if ( $child_product->is_type( 'variation' ) ) {
											$variation_data = $child_product->get_data()['attributes'];
											if ( ! empty( $variation_data ) ) {
												foreach ( $variation_data as $attribute_name => $option ) {
													$item_data[] = array(
														'key' => wc_attribute_label(
															str_replace(
																'attribute_',
																'',
																$attribute_name
															)
														),
														'value' => $option,
													);
												}
											}
										}

										$item_data = apply_filters(
											'ywraq_request_quote_view_item_data',
											$item_data,
											$raq,
											$child_product
										);

										// Output flat or in list format.
										if ( count( $item_data ) > 0 ) {
											foreach ( $item_data as $data ) {
												echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . "\n";
											}
										}

										?>
									</td>
									<?php if ( $show_single_price ) : ?>
										<td class="product-price"></td>
									<?php endif; ?>
									<?php if ( $show_quantity ) : ?>
										<td class="product-quantity">
											<?php
											echo esc_html( ( $sold_individually ) ? $child_quantity : $child_quantity * $composite_quantity );
											?>
										</td>
									<?php endif; ?>
									<?php if ( $show_line_total ) : ?>
										<td class="product-subtotal">
											<?php
											// wc 2.7.
											$child_product_price = wc_get_price_to_display( $child_product );

											if ( $sold_individually ) {

												echo ( $child_product->get_price() ) ? esc_html__(
													'Option subtotal: ',
													'yith-woocommerce-request-a-quote'
												) . wp_kses_post( wc_price( $child_product_price * $child_quantity ) ) : '';

											} else {
												echo ( $child_product->get_price() ) ? esc_html__(
													'Option subtotal: ',
													'yith-woocommerce-request-a-quote'
												) . wp_kses_post( wc_price( $child_product_price * $child_quantity * $composite_quantity ) ) : '';

											}
											?>
										</td>
									<?php endif; ?>
								</tr>
								<?php

							}
						}
					}
				}
			}
		}

		/**
		 * Show composite data inside the widget
		 *
		 * @param   array  $raq  Raq list.
		 * @param   string $key  Item key.
		 */
		public function show_composite_data_in_widget( $raq, $key ) {

			if ( array_key_exists( 'yith_wcp_component_data', $raq[ $key ] ) ) {

				$product        = wc_get_product( $raq[ $key ]['yith-add-to-cart'] );
				$component_data = $raq[ $key ]['yith_wcp_component_data'];

				if ( $product->is_type( 'yith-composite' ) && isset( $component_data['selection_variation_data'] ) ) {

					$composite_quantity = $raq[ $key ]['quantity'];

					$composite_stored_data = $product->getComponentsData();

					foreach ( $composite_stored_data as $wcp_key => $component_item ) {

						if ( isset( $component_data['selection_data'][ $wcp_key ] ) ) {

							if ( $component_data['selection_data'][ $wcp_key ] > 0 ) {

								// variation selected.
								if ( isset( $component_data['selection_variation_data'][ $wcp_key ] ) && $component_data['selection_variation_data'][ $wcp_key ] > 0 ) {
									$child_product = wc_get_product( $component_data['selection_variation_data'][ $wcp_key ] );
								} else {
									$child_product = wc_get_product( $component_data['selection_data'][ $wcp_key ] );
								}

								if ( ! $child_product ) {
									continue;
								}

								$wcp_component_item = $product->getComponentItemByKey( $wcp_key );
								$sold_individually  = isset( $wcp_component_item['sold_individually'] ) && $wcp_component_item['sold_individually'] ? $wcp_component_item['sold_individually'] : false;

								YITH_WCP_Frontend::markProductAsCompositeProcessed(
									$child_product,
									$product->get_id(),
									$wcp_key
								);

								$child_quantity = $component_data['selection_quantity'][ $wcp_key ];
								?>
								<div class="cart_item ywcp_component_child_item" data-wcpkey="<?php echo esc_attr( $key ); ?>">
									<?php

									$title = $child_product->get_title();

									if ( $child_product->get_sku() !== '' && ywraq_show_element_on_list( 'sku' ) ) {
										$sku    = apply_filters(
											'ywraq_sku_label',
											__(
												' SKU:',
												'yith-woocommerce-request-a-quote'
											)
										) . $child_product->get_sku();
										$title .= apply_filters( 'ywraq_sku_label_html', $sku, $child_product );
									}

									echo sprintf( '<strong>%s</strong>', wp_kses_post( $component_item['name'] ) );
									?>
									<a href="<?php echo esc_url( $child_product->get_permalink() ); ?>"><?php echo wp_kses_post( $title ); ?></a>
									<?php
									// Meta data.
									$item_data = array();
									if ( $child_product->is_type( 'variation' ) ) {
										$variation_data = $child_product->get_data()['attributes'];

										if ( ! empty( $variation_data ) ) {
											foreach ( $variation_data as $attribute_name => $option ) {

												$item_data[] = array(
													'key' => wc_attribute_label(
														str_replace(
															'attribute_',
															'',
															$attribute_name
														)
													),
													'value' => $option,
												);

											}
										}
									}

									$item_data = apply_filters(
										'ywraq_request_quote_view_item_data',
										$item_data,
										$raq,
										$child_product
									);
									// Output flat or in list format.
									if ( count( $item_data ) > 0 ) {
										foreach ( $item_data as $data ) {
											echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . "\n";
										}
									}
									?>

									<span class="product-quantity"><?php echo esc_html( ( $sold_individually ) ? $child_quantity : $child_quantity * $composite_quantity ); ?></span>
								</div>
								<?php

							}
						}
					}
				}
			}
		}

		/**
		 * Show composite data inside the email.
		 *
		 * @param   array  $raq  RAQ content.
		 * @param   string $key  Key item.
		 */
		public function show_composit_data_on_email( $raq, $key ) {

			if ( isset( $raq[ $key ] ) ) {
				$show_image      = get_option( 'ywraq_show_preview' ) === 'yes' || in_array( 'images', get_option( 'ywraq_product_table_show' ), true );
				$show_permalinks = apply_filters(
					'ywraq_list_show_product_permalinks',
					true,
					'email_request_quote_table'
				);
				if ( is_array( $raq[ $key ] ) && array_key_exists( 'yith_wcp_component_data', $raq[ $key ] ) ) {
					$product = wc_get_product( $raq[ $key ]['yith-add-to-cart'] );

					$component_data = $raq[ $key ]['yith_wcp_component_data'];

					if ( $product->is_type( 'yith-composite' ) && isset( $component_data['selection_variation_data'] ) ) {

						$composite_quantity = $raq[ $key ]['quantity'];

						$composite_stored_data = $product->getComponentsData();

						foreach ( $composite_stored_data as $wcp_key => $component_item ) {

							if ( isset( $component_data['selection_data'][ $wcp_key ] ) ) {

								if ( $component_data['selection_data'][ $wcp_key ] > 0 ) {

									// variation selected.
									if ( isset( $component_data['selection_variation_data'][ $wcp_key ] ) && $component_data['selection_variation_data'][ $wcp_key ] > 0 ) {

										$child_product = wc_get_product( $component_data['selection_variation_data'][ $wcp_key ] );

									} else {

										$child_product = wc_get_product( $component_data['selection_data'][ $wcp_key ] );

									}

									if ( ! $child_product ) {
										continue;
									}

									$wcp_component_item = $product->getComponentItemByKey( $wcp_key );
									$sold_individually  = isset( $wcp_component_item['sold_individually'] ) && $wcp_component_item['sold_individually'] ? $wcp_component_item['sold_individually'] : false;

									YITH_WCP_Frontend::markProductAsCompositeProcessed(
										$child_product,
										$product->get_id(),
										$wcp_key
									);

									$child_quantity = $component_data['selection_quantity'][ $wcp_key ];

									$title = $child_product->get_title();

									if ( '' !== $child_product->get_sku() && ywraq_show_element_on_list( 'sku' ) ) {
										$sku    = apply_filters(
											'ywraq_sku_label',
											__(
												' SKU:',
													'yith-woocommerce-request-a-quote' ) ) . $child_product->get_sku(); //phpcs:ignore
										$title .= ' ' . apply_filters(
											'ywraq_sku_label_html',
											$sku,
												$child_product ); //phpcs:ignore
									}

									?>
									<tr class="yith-composite-child">

										<td scope="col" class="td product-name"
											style="text-align:left;">
											<?php
											echo sprintf(
												'<strong>%s</strong><br>',
												wp_kses_post( $wcp_component_item['name'] )
											);
											?>


											<?php
											if ( $show_image ) :

												$dimensions = apply_filters(
													'ywraq_email_image_product_size',
													array( 80, 80 ),
													$child_product
												);
												$src        = ( $child_product->get_image_id() ) ? current(
													wp_get_attachment_image_src(
														$child_product->get_image_id(),
														$dimensions
													)
												) : wc_placeholder_img_src();
												?>
												<?php if ( $show_permalinks && $child_product->is_visible() ) : ?>
											<a href="<?php echo esc_url( $child_product->get_permalink() ); ?>" class="thumb-wrapper">
												<?php else : ?>
												<div class="thumb-wrapper">
													<?php endif; ?>
													<img
															src="<?php echo esc_url( $src ); ?>"
															height="<?php echo esc_attr( $dimensions[1] ); ?>"
															width="<?php echo esc_attr( $dimensions[0] ); ?>"/>

													<?php if ( $show_permalinks && $child_product->is_visible() ) : ?>
											</a> 
														<?php
											else :
												?>
														</td><?php endif ?>
										<?php endif; ?>
										<div class="product-name-wrapper">
											<?php
											if ( $show_permalinks && $child_product->is_visible() ) : ?>
												<a
													href="<?php echo esc_url( $child_product->get_permalink() ); ?>"> <?php endif; ?>
												<?php echo wp_kses_post( $title ); ?>
												<?php
												if ( $show_permalinks && $child_product->is_visible() ) :
													?>
													</a><?php endif; ?>

											<?php
											$item_data = array();
											if ( $child_product->is_type( 'variation' ) ) {
												$variation_data = $child_product->get_data()['attributes'];

												if ( ! empty( $variation_data ) ) {
													foreach ( $variation_data as $attribute_name => $option ) {

														$item_data[] = array(
															'key' => wc_attribute_label(
																str_replace(
																	'attribute_',
																	'',
																	$attribute_name
																)
															),
															'value' => $option,
														);

													}
												}
											}

											$item_data = apply_filters(
												'ywraq_request_quote_view_item_data',
												$item_data,
												$raq,
												$child_product
											);
											// Output flat or in list format.
											if ( count( $item_data ) > 0 ) {
												echo '<ul>';
												foreach ( $item_data as $data ) {
													echo '<li>' . esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . '</li>';
												}
												echo '</ul>';
											}
											?>
										</div>
										</td>
										<td scope="col" class="td quantity"
											style="text-align:center;"><?php echo esc_html( ( $sold_individually ) ? $child_quantity : $child_quantity * $composite_quantity ); ?></td>
										<?php

										if ( ywraq_show_element_on_list( 'line_total' ) && ywraq_show_element_on_list( 'total' ) && get_option( 'ywraq_hide_price' ) !== 'yes' ) :
											?>
											<td scope="col" class="td subtotal" style="text-align:left;">
												<?php

												$child_product_price = wc_get_price_to_display( $child_product );

												if ( $sold_individually ) {

													echo ( $child_product->get_price() ) ? esc_html__(
														'Option subtotal: ',
														'yith-woocommerce-request-a-quote'
													) . wp_kses_post( wc_price( $child_product_price * $child_quantity ) ) : '';

												} else {
													echo ( $child_product->get_price() ) ? esc_html__(
														'Option subtotal: ',
														'yith-woocommerce-request-a-quote'
													) . wp_kses_post( wc_price( $child_product_price * $child_quantity * $composite_quantity ) ) : '';

												}
												?>
											</td>
										<?php endif; ?>
									</tr>
									<?php

								}
							}
						}
					}
				} else {
					return '';
				}
			}
		}

		/**
		 * Get product price html
		 *
		 * @param   float      $product_sub_total  .
		 * @param   WC_Product $product            Product.
		 * @param   array      $raq_item           RAQ item.
		 *
		 * @return string
		 */
		public function product_price_html( $product_sub_total, $product, $raq_item ) {

			if ( $product->is_type( 'yith-composite' ) && ! empty( $raq_item['yith_wcp_component_data'] ) ) {

				if ( $product->isPerItemPricing() ) {

					$component_data = $raq_item['yith_wcp_component_data'];

					$composite_base_price = $component_data['product_base_price'];

					$composite_total = $this->get_childs_totals( $raq_item );

					$new_subtotal = $composite_base_price + $composite_total;

					$total_price = $new_subtotal * absint( $raq_item['quantity'] );

					$price_html = wc_price( $total_price );

					if ( $composite_base_price > 0 ) {
						$price_html .= '<div>(' . _x( 'Base Price:', 'Integration composite product: cart price advice', 'yith-woocommerce-request-a-quote' ) . ' ' . wc_price( $composite_base_price * $raq_item['quantity'] ) . ' + ' . _x( 'Total Price:', 'cart price advice', 'yith-wooommerce-composite-products' ) . ' ' . wc_price( $composite_total * $raq_item['quantity'] ) . ')</div>';
					}

					return $price_html;

				}
			} elseif ( isset( $raq_item['yith_wcp_child_component_data'] ) ) {

				$child_item_meta = $raq_item['yith_wcp_child_component_data'];

				if ( $child_item_meta['yith_wcp_component_parent_object']->isPerItemPricing() ) {
					return _x( 'Options subtotal', 'Integration - Composite product subtotal', 'yith-woocommerce-request-a-quote' ) . ': ' . $product_sub_total;
				} else {
					return '';
				}
			}

			return $product_sub_total;

		}


		/**
		 * Return the price of the product.
		 *
		 * @param   array      $values    .
		 * @param   WC_Product $_product  .
		 * @param   string     $taxes     .
		 */
		public function adjust_price( $values, $_product, $taxes = 'inc' ) {

			if ( isset( $values['yith_wcp_component_data'] ) && 'yith-composite' === $_product->get_type() ) {

				$qty   = $values['quantity'];
				$price = wc_prices_include_tax() ? wc_get_price_including_tax( $_product ) : wc_get_price_excluding_tax(
					$_product,
					$qty
				);

				$_product->set_price( $price );
			}
		}

		/**
		 * Get the total price of childs
		 *
		 * @param   array $raq_item  Item.
		 *
		 * @return int|string
		 */
		private function get_childs_totals( $raq_item ) {

			$new_subtotal = 0;

			if ( isset( $raq_item['yith_wcp_component_data'] ) ) {

				$product = wc_get_product( $raq_item['yith-add-to-cart'] );

				$component_data = $raq_item['yith_wcp_component_data'];

				if ( $product->is_type( 'yith-composite' ) && isset( $component_data['selection_variation_data'] ) ) {

					$composite_stored_data = $product->getComponentsData();

					foreach ( $composite_stored_data as $wcp_key => $component_item ) {

						if ( isset( $component_data['selection_data'][ $wcp_key ] ) ) {

							if ( $component_data['selection_data'][ $wcp_key ] > 0 ) {

								// variation selected.
								if ( isset( $component_data['selection_variation_data'][ $wcp_key ] ) && $component_data['selection_variation_data'][ $wcp_key ] > 0 ) {

									$child_product = wc_get_product( $component_data['selection_variation_data'][ $wcp_key ] );

								} else {

									$child_product = wc_get_product( $component_data['selection_data'][ $wcp_key ] );

								}

								if ( ! $child_product ) {
									continue;
								}

								YITH_WCP_Frontend::markProductAsCompositeProcessed(
									$child_product,
									$product->get_id(),
									$wcp_key
								);

								$child_quantity      = $component_data['selection_quantity'][ $wcp_key ];
								$child_product_price = wc_get_price_to_display( $child_product );
								$new_subtotal       += ( $child_product_price * $child_quantity );

							}
						}
					}
				}
			}

			return $new_subtotal;
		}

		/**
		 * Add order item meta
		 *
		 * @param   array  $values         .
		 * @param   string $cart_item_key  Cart item key.
		 * @param   int    $item_id        Item id.
		 */
		public function add_order_item_meta( $values, $cart_item_key, $item_id ) {

			if ( ! empty( $values['yith_wcp_component_data'] ) ) {

				wc_add_order_item_meta( $item_id, '_yith_wcp_component_data', $values['yith_wcp_component_data'] );

			} elseif ( ! empty( $values['yith_wcp_child_component_data'] ) ) {

				wc_add_order_item_meta(
					$item_id,
					'_yith_wcp_child_component_data',
					$values['yith_wcp_child_component_data']
				);

				wc_add_order_item_meta( $item_id, '_yith_wcp_child_component_data_no_reorder', 1 );

			}

		}

		/**
		 * Hidden order item meta
		 *
		 * @param   array $array  .
		 *
		 * @return array
		 */
		public function hidden_order_itemmeta( $array ) {

			$array = array_merge( $array, array( '_yith_wcp_child_component_data_no_reorder' ) );

			return $array;

		}

		/**
		 * Add css to hide composite price.
		 */
		public function add_css_to_hide_composite_prices() {

			global $post;

			if ( isset( $post->ID ) ) {
				$product = wc_get_product( $post->ID );
				if ( $product instanceof WC_Product ) {
					$hide_price = get_option( 'ywraq_hide_price' ) === 'yes';
					if ( ! catalog_mode_plugin_enabled() && $hide_price ) {

						$css = '.ywcp_component_subtotal, .ywcp_wcp_group_total {
							display:none!important;
						}';

						wp_add_inline_style( 'yith_ywraq_frontend', $css );
					}
				}
			}
		}

	}

	/**
	 * Unique access to instance of YWRAQ_WooCommerce_Product_Addon class
	 *
	 * @return YWRAQ_WooCommerce_Composite_Products
	 */
	function YWRAQ_YITH_Composite_Products() { //phpcs:ignore
		return YWRAQ_YITH_Composite_Products::get_instance();
	}

	if ( class_exists( 'YITH_WCP' ) ) {
		YWRAQ_YITH_Composite_Products();
	}
}
