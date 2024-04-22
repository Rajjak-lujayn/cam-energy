<?php
if ( ! class_exists( 'WC_ODOO_Helpers' ) ) :

	class WC_ODOO_Helpers {

		private static $obj;

		public $creds;
		public $default_mapping = array();
		public $custom_mapping  = array();


		public function __construct() {

			$this->creds           = get_option( 'woocommerce_woocommmerce_odoo_integration_settings' );
			$this->default_mapping = array(
				'processing' => 'in_payment',
				'completed'  => 'paid',
				'pending'    => 'quote_order',
				'failed'     => 'cancelled',
				'on-hold'    => 'quote_only',
				'cancelled'  => 'cancelled',
				'refunded'   => 'refunded',
			);

			if ( isset( $this->creds['odoo_woo_order_status'] ) && isset( $this->creds['odoo_payment_status'] ) ) {

				$woo_status  = ( '' != $this->creds['odoo_woo_order_status'] ) ? $this->creds['odoo_woo_order_status'] : '';
				$odoo_states = ( '' != $this->creds['odoo_payment_status'] ) ? $this->creds['odoo_payment_status'] : '';

				if ( '' != $woo_status || '' != $odoo_states ) {
					foreach ( $woo_status as $key => $value ) {
						$this->custom_mapping[ str_replace( 'wc-', '', $value ) ] = $odoo_states[ $key ];
					}
				}
			}

			$odooApi = new WC_ODOO_API();
			if ( $odooApi->is_multi_company() ) {
				$multi_company_func = '/multi-company-files';
			} else {
				$multi_company_func = '';
			}
			include WC_ODOO_INTEGRATION_PLUGINDIR . '/includes' . $multi_company_func . '/class-wc-odoo-functions.php';
			$odoo_function = new WC_ODOO_Functions();

			add_action( 'woocommerce_order_status_changed', array( $odoo_function, 'opmc_odoo_order_status' ), 10, 4 );

			// $this->get_states_code();
		}

		public static function getHelper() {
			if ( ! isset( self::$obj ) ) {
				self::$obj = new WC_ODOO_Helpers();
			}

			return self::$obj;
		}


		public function get_states_code() {
			$authenticated_uid   = get_option( 'opmc_odoo_authenticated_uid' );
			$authenticated_token = get_option( 'opmc_odoo_access_token' );
			$odooApi             = new WC_ODOO_API();
			// $odooApi->addLog('UID : '. var_export($authenticated_uid, 1).' Token : '.var_export($authenticated_token, 1));

			if ( ! $authenticated_uid && ! $authenticated_token ) {

				$states_sync = get_option( '_opmc_odoo_states_sync' );
				if ( ! $states_sync ) {
					// $odooApi->addLog('Sates Sync Called : '. print_r($authenticated_uid, 1));

					$countries = $odooApi->readAll( 'res.country', array( 'id', 'name', 'code', 'currency_id', 'phone_code' ), array(), 2000 );
					$odooApi->addLog( 'countries res: ' . print_r( $countries, 1 ) );
					if ( $countries->success ) {
						$countries      = $countries->data->items;
						$countries_data = array();
						foreach ( $countries as $key => $country ) {
							$countries_data[ $country->id ] = $country;
						}
						// $odooApi->addLog('response : ' . print_r($countries_data, true));
						$fp = fopen( WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/countries.json', 'w' );
						fwrite( $fp, json_encode( $countries_data ) );
						fclose( $fp );
					}

					// $odooApi->addLog('Sates Sync Called : '. print_r($authenticated_uid, 1));

					$states = $odooApi->readAll( 'res.country.state', array( 'id', 'name', 'code', 'country_id' ), array(), 2000 );
					$odooApi->addLog( 'states res: ' . print_r( $states, 1 ) );
					if ( $states->success ) {
						// pr($states->data->items);die();
						$states      = $states->data->items;
						$states_data = array();
						foreach ( $states as $key => $state ) {
							// $odooApi->addLog('Country_id : '. print_r($state->country_id[0],1));
							$states_data[ $state->country_id[0] ][ $state->code ] = $state;
						}
						// $odooApi->addLog('State response : ' . print_r($states_data, true));
						$fp = fopen( WC_ODOO_INTEGRATION_PLUGINDIR . '/includes/states.json', 'w' );
						fwrite( $fp, json_encode( $states_data ) );
						fclose( $fp );
					}
					update_option( '_opmc_odoo_states_sync', true );
				}
			}
		}


		public function upload_product_image( $product ) {
			$image_id     = $product->get_image_id();
			$image_path   = get_attached_file( $image_id );
			$image        = file_get_contents( $image_path );
			$image_base64 = base64_encode( $image );
			return $image_base64;
		}

		public function can_upload_image( $product ) {

			if ( ! empty( $product->get_image_id() ) ) {
				if ( $product->get_image_id() != get_post_meta( $product->get_id(), '_odoo_image_id', true ) ) {
					return true;
				}
				return false;
			}
			return false;
		}

		public function is_export_inv() {
			if ( $this->creds['odoo_export_invoice'] ) {
				return true;
			}

			return false;
		}


		public function is_inv_mark_paid() {
			if ( $this->creds['odoo_mark_invoice_paid'] ) {
				return true;
			}

			return false;
		}

		public function odoo_version() {
			// return isset($this->creds['odooVersion']) ? $this->creds['odooVersion'] : 14;
			return isset( $this->creds['odooVersion'] ) ? $this->creds['odooVersion'] : 15;
		}

		public function odooStates( $value ) {
			$states = array(
				'quote_only'  => array(
					'order_state'   => 'sent',
					'invoice_state' => '',
					'payment_state' => '',
				),
				'quote_order' => array(
					'order_state'   => 'sale',
					'invoice_state' => '',
					'payment_state' => '',
				),
				'in_payment'  => array(
					'order_state'   => 'sale',
					'invoice_state' => 'posted',
					'payment_state' => 'in_payment',
				),
				'paid'        => array(
					'order_state'   => 'sale',
					'invoice_state' => 'posted',
					'payment_state' => 'paid',
				),
				'cancelled'   => array(
					'order_state'   => 'cancel',
					'invoice_state' => 'posted',
					'payment_state' => 'cancelled',
				),
				'refunded'    => array(
					'order_state'   => 'sale',
					'invoice_state' => 'posted',
					'payment_state' => 'reversed',
					'rev_invoice'   => array(
						'state'         => 'posted',
						'payment_state' => 'paid',
					),
				),
			);
			return $states[ $value ];
		}

		public function getState( $status ) {
			if ( 'yes' == $this->creds['odoo_status_mapping'] && count( $this->custom_mapping ) > 0 ) {
				if ( array_key_exists( $status, $this->custom_mapping ) ) {
					return $this->custom_mapping[ $status ];
				} else {
					return $this->default_mapping[ $status ];
				}
			} else {
				return $this->default_mapping[ $status ];
			}
		}

		/**
		 * Verifies the items in an order.
		 *
		 * This function checks each item in a WooCommerce order to ensure that it is valid and exportable.
		 * An item is considered valid if it exists and is exportable if it either has a SKU (for simple products) or
		 * all its variations have a SKU (for variable products).
		 * If an item is not valid or not exportable, the function logs an error message and returns false.
		 * If all items are valid and exportable, the function returns true.
		 *
		 * @param int $order_id The ID of the order to verify.
		 * @return bool Returns true if all items in the order are valid and exportable, false otherwise.
		 *
		 * @throws Exception If the order ID is not valid or if there is a problem retrieving the order or its items.
		 */
		public function verifyOrderItems( $order_id ) {
			$order = new WC_Order( $order_id );
			foreach ( $order->get_items() as $item_id => $item ) {

				$product = $item->get_product();
				$odooApi = new WC_ODOO_API();

				if ( ! $product || null == $product ) {
					$odooApi->addLog( '[Order Export] [Error] [Order Export aborted due to invalid/not found product. Please review the order product details of Order Number #' . print_r($order_id, 1) . '.]' );
					return false;
				}
				if ( ! $this->is_product_exportable($product->get_id()) ) {
					$odooApi->addLog( '[Order Export] [Error] [Order Export aborted due to invalid SKU of product or any of its related variants product. Please review the product : ' . print_r($product->get_name(), 1) . ' details.]' );
					return false;
				}
			}
			return true;
		}
		
		
		/**
		 * Check if a product is exportable.
		 *
		 * A product is considered exportable if it is a simple product with a SKU or a variable product
		 * where all variations have a SKU.
		 * For the uninitiated, SKU stands for Stock Keeping Unit, a unique identifier for
		 * each distinct product and service that can be purchased.
		 *
		 * @param int $product_id The ID of the product to check.
		 * @return bool Returns true if the product is exportable, false otherwise.
		 */
		public function is_product_exportable( $product_id ) {
			// Let's try to get the product using the product ID. If the product doesn't exist, wc_get_product will return false.
			$product = wc_get_product($product_id);
			$odooApi = new WC_ODOO_API();
			
			// If the product exists, and it's a variable product, we're in for a ride.
			if ($product && $product->is_type('variation')) {
				 // Get the parent product (the variable product).
				$parent_product = wc_get_product($product->get_parent_id());
				
				// Let's get all the variations of this product.
				$variations = $parent_product->get_children();
				
				// Now, we'll check each variation.
				foreach ($variations as $variation_id) {
					// Get the variation product.
					$variation = wc_get_product($variation_id);
					
					// If the variation doesn't exist, or it doesn't have a SKU, we're done here. This product is not exportable.
					if (!$variation || !$variation->get_sku()) {
						$odooApi->addLog( '[Product Sync] [Error] [Product variant ' . print_r($variation->get_name(), 1) . ' of <a href="' . print_r(get_edit_post_link($product_id), 1) . '" target="_blank">' . print_r($variation->get_name(), 1) . '</a> have missing/invalid SKU. This product will not be exported. ]' );
						return false; // SKU missing in a variation. This product is as exportable as a lead balloon.
					}
				}
				
				// If we've made it this far, that means all variations have a SKU. The product is exportable.
				return true; // SKU exists in all variations. This product is ready to hit the road.
			} elseif ($product && $product->is_type('variable')) {
				
				// Let's get all the variations of this product.
				$variations = $product->get_children();
				
				// Now, we'll check each variation.
				foreach ($variations as $variation_id) {
					// Get the variation product.
					$variation = wc_get_product($variation_id);
					
					// If the variation doesn't exist, or it doesn't have a SKU, we're done here. This product is not exportable.
					if (!$variation || !$variation->get_sku()) {
						$odooApi->addLog( '[Product Sync] [Error] [Product variant ' . print_r($variation->get_name(), 1) . ' of <a href="' . print_r(get_edit_post_link($product_id), 1) . '" target="_blank">' . print_r($variation->get_name(), 1) . '</a> have missing/invalid SKU. This product will not be exported. ]' );
						return false; // SKU missing in a variation. This product is as exportable as a lead balloon.
					}
				}
				
				// If we've made it this far, that means all variations have a SKU. The product is exportable.
				return true; // SKU exists in all variations. This product is ready to hit the road.
			} elseif ($product && $product->get_sku()) {
				// If the product is a simple product, and it has a SKU, it's exportable.
				return true; // SKU exists for simple product. This product is as exportable as they come.
			}
			
			// If we've reached this point, that means the product either doesn't exist or it doesn't have a SKU. It's not exportable.
			return false; // SKU not found on the main product or its variations. This product isn't going anywhere.
		}
		
		/**
		 * Check if a product is importable.
		 *
		 * A product is considered exportable if it is a simple product with a SKU or a variable product
		 * where all variations have a SKU.
		 * For the uninitiated, SKU stands for Stock Keeping Unit, a unique identifier for
		 * each distinct product and service that can be purchased.
		 *
		 * @param int $product_id The ID of the product to check.
		 * @return bool Returns true if the product is exportable, false otherwise.
		 */
		public function is_product_importable( $template ) {
			$odooApi = new WC_ODOO_API();
			$odoo_base_url = rtrim($this->creds['client_url'], '/') . '/';
			if (1 < $template['product_variant_count']) {
				$conditions = array(
					array(
						'field_key' => 'product_tmpl_id',
						'operator' => '=',
						'field_value' => (int) $template['id']
					)
				);
				$product_variants = $odooApi->search_records('product.product', $conditions, array('display_name', 'product_tmpl_id', $this->creds['odooSkuMapping'],'product_variant_ids'));
				if ($product_variants->success) {
					$variants = json_decode(json_encode($product_variants->data->items), true);
//					$odooApi->addLog('variants : '.print_r($variants, 1));
					
					// Check if $variants is not empty and is an array
					if (!empty($variants) && is_array($variants)) {
						// Check if 'odooSkuMapping' is missing in any record
						$odooSku = true;
						foreach ($variants as $variant) {
								$odooApi->addLog('sku for variant : ' . print_r($variant['display_name'], 1));
							if (!$variant[$this->creds['odooSkuMapping']]) {
								$variant_id = $variant['id'];
								$odoo_product_url = $odoo_base_url . 'web#id=' . $variant_id . '&model=product.product&view_type=form';
								$odooApi->addLog('[Products Import] [Error] [Odoo product variant <a href="' . print_r($odoo_product_url, 1) . '" target="_blank">' . print_r($variant['display_name'], 1) . '</a> is missing SKU. ]');
								$odooSku = false;
							}
						}
						if ($odooSku) {
							return true;
						} else {
							return false;
						}
					}
				}
			} else {
				if ($template[$this->creds['odooSkuMapping']]) {
//					$odooApi->addLog('odoo product sku :'. print_r($template[$this->creds['odooSkuMapping']], 1));
					return true;
				} else {
					$template_id = $template['id'];
					$odoo_product_url = $odoo_base_url . 'web#id=' . $template_id . '&model=product.template&view_type=form';
					$odooApi->addLog('[Products Import] [Error] [Product export for <a href="' . print_r($odoo_product_url, 1) . '" target="_blank">' . print_r($template['display_name'], 1) . '</a> is unsuccessful due to missing SKU. ]');
					return false;
				}
			}
			
			return false;
		}
		

		// if ( ! function_exists( 'mime2ext' ) ) {
		/**
		 * Get file extension from mime type
		 *
//         * @param  str $mime file mimetype
//         * @return str       return extension
		 */
		public function mime2ext( $mime ) {
			$mime_map = array(
				'video/3gpp2'                          => '3g2',
				'video/3gp'                            => '3gp',
				'video/3gpp'                           => '3gp',
				'application/x-compressed'             => '7zip',
				'audio/x-acc'                          => 'aac',
				'audio/ac3'                            => 'ac3',
				'application/postscript'               => 'ai',
				'audio/x-aiff'                         => 'aif',
				'audio/aiff'                           => 'aif',
				'audio/x-au'                           => 'au',
				'video/x-msvideo'                      => 'avi',
				'video/msvideo'                        => 'avi',
				'video/avi'                            => 'avi',
				'application/x-troff-msvideo'          => 'avi',
				'application/macbinary'                => 'bin',
				'application/mac-binary'               => 'bin',
				'application/x-binary'                 => 'bin',
				'application/x-macbinary'              => 'bin',
				'image/bmp'                            => 'bmp',
				'image/x-bmp'                          => 'bmp',
				'image/x-bitmap'                       => 'bmp',
				'image/x-xbitmap'                      => 'bmp',
				'image/x-win-bitmap'                   => 'bmp',
				'image/x-windows-bmp'                  => 'bmp',
				'image/ms-bmp'                         => 'bmp',
				'image/x-ms-bmp'                       => 'bmp',
				'application/bmp'                      => 'bmp',
				'application/x-bmp'                    => 'bmp',
				'application/x-win-bitmap'             => 'bmp',
				'application/cdr'                      => 'cdr',
				'application/coreldraw'                => 'cdr',
				'application/x-cdr'                    => 'cdr',
				'application/x-coreldraw'              => 'cdr',
				'image/cdr'                            => 'cdr',
				'image/x-cdr'                          => 'cdr',
				'zz-application/zz-winassoc-cdr'       => 'cdr',
				'application/mac-compactpro'           => 'cpt',
				'application/pkix-crl'                 => 'crl',
				'application/pkcs-crl'                 => 'crl',
				'application/x-x509-ca-cert'           => 'crt',
				'application/pkix-cert'                => 'crt',
				'text/css'                             => 'css',
				'text/x-comma-separated-values'        => 'csv',
				'text/comma-separated-values'          => 'csv',
				'application/vnd.msexcel'              => 'csv',
				'application/x-director'               => 'dcr',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
				'application/x-dvi'                    => 'dvi',
				'message/rfc822'                       => 'eml',
				'application/x-msdownload'             => 'exe',
				'video/x-f4v'                          => 'f4v',
				'audio/x-flac'                         => 'flac',
				'video/x-flv'                          => 'flv',
				'image/gif'                            => 'gif',
				'application/gpg-keys'                 => 'gpg',
				'application/x-gtar'                   => 'gtar',
				'application/x-gzip'                   => 'gzip',
				'application/mac-binhex40'             => 'hqx',
				'application/mac-binhex'               => 'hqx',
				'application/x-binhex40'               => 'hqx',
				'application/x-mac-binhex40'           => 'hqx',
				'text/html'                            => 'html',
				'image/x-icon'                         => 'ico',
				'image/x-ico'                          => 'ico',
				'image/vnd.microsoft.icon'             => 'ico',
				'text/calendar'                        => 'ics',
				'application/java-archive'             => 'jar',
				'application/x-java-application'       => 'jar',
				'application/x-jar'                    => 'jar',
				'image/jp2'                            => 'jp2',
				'video/mj2'                            => 'jp2',
				'image/jpx'                            => 'jp2',
				'image/jpm'                            => 'jp2',
				'image/jpeg'                           => 'jpeg',
				'image/pjpeg'                          => 'jpeg',
				'application/x-javascript'             => 'js',
				'application/json'                     => 'json',
				'text/json'                            => 'json',
				'application/vnd.google-earth.kml+xml' => 'kml',
				'application/vnd.google-earth.kmz'     => 'kmz',
				'text/x-log'                           => 'log',
				'audio/x-m4a'                          => 'm4a',
				'audio/mp4'                            => 'm4a',
				'application/vnd.mpegurl'              => 'm4u',
				'audio/midi'                           => 'mid',
				'application/vnd.mif'                  => 'mif',
				'video/quicktime'                      => 'mov',
				'video/x-sgi-movie'                    => 'movie',
				'audio/mpeg'                           => 'mp3',
				'audio/mpg'                            => 'mp3',
				'audio/mpeg3'                          => 'mp3',
				'audio/mp3'                            => 'mp3',
				'video/mp4'                            => 'mp4',
				'video/mpeg'                           => 'mpeg',
				'application/oda'                      => 'oda',
				'audio/ogg'                            => 'ogg',
				'video/ogg'                            => 'ogg',
				'application/ogg'                      => 'ogg',
				'font/otf'                             => 'otf',
				'application/x-pkcs10'                 => 'p10',
				'application/pkcs10'                   => 'p10',
				'application/x-pkcs12'                 => 'p12',
				'application/x-pkcs7-signature'        => 'p7a',
				'application/pkcs7-mime'               => 'p7c',
				'application/x-pkcs7-mime'             => 'p7c',
				'application/x-pkcs7-certreqresp'      => 'p7r',
				'application/pkcs7-signature'          => 'p7s',
				'application/pdf'                      => 'pdf',
				'application/octet-stream'             => 'pdf',
				'application/x-x509-user-cert'         => 'pem',
				'application/x-pem-file'               => 'pem',
				'application/pgp'                      => 'pgp',
				'application/x-httpd-php'              => 'php',
				'application/php'                      => 'php',
				'application/x-php'                    => 'php',
				'text/php'                             => 'php',
				'text/x-php'                           => 'php',
				'application/x-httpd-php-source'       => 'php',
				'image/png'                            => 'png',
				'image/x-png'                          => 'png',
				'application/powerpoint'               => 'ppt',
				'application/vnd.ms-powerpoint'        => 'ppt',
				'application/vnd.ms-office'            => 'ppt',
				'application/msword'                   => 'doc',
				'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
				'application/x-photoshop'              => 'psd',
				'image/vnd.adobe.photoshop'            => 'psd',
				'audio/x-realaudio'                    => 'ra',
				'audio/x-pn-realaudio'                 => 'ram',
				'application/x-rar'                    => 'rar',
				'application/rar'                      => 'rar',
				'application/x-rar-compressed'         => 'rar',
				'audio/x-pn-realaudio-plugin'          => 'rpm',
				'application/x-pkcs7'                  => 'rsa',
				'text/rtf'                             => 'rtf',
				'text/richtext'                        => 'rtx',
				'video/vnd.rn-realvideo'               => 'rv',
				'application/x-stuffit'                => 'sit',
				'application/smil'                     => 'smil',
				'text/srt'                             => 'srt',
				'image/svg+xml'                        => 'svg',
				'application/x-shockwave-flash'        => 'swf',
				'application/x-tar'                    => 'tar',
				'application/x-gzip-compressed'        => 'tgz',
				'image/tiff'                           => 'tiff',
				'font/ttf'                             => 'ttf',
				'text/plain'                           => 'txt',
				'text/x-vcard'                         => 'vcf',
				'application/videolan'                 => 'vlc',
				'text/vtt'                             => 'vtt',
				'audio/x-wav'                          => 'wav',
				'audio/wave'                           => 'wav',
				'audio/wav'                            => 'wav',
				'application/wbxml'                    => 'wbxml',
				'video/webm'                           => 'webm',
				'image/webp'                           => 'webp',
				'audio/x-ms-wma'                       => 'wma',
				'application/wmlc'                     => 'wmlc',
				'video/x-ms-wmv'                       => 'wmv',
				'video/x-ms-asf'                       => 'wmv',
				'font/woff'                            => 'woff',
				'font/woff2'                           => 'woff2',
				'application/xhtml+xml'                => 'xhtml',
				'application/excel'                    => 'xl',
				'application/msexcel'                  => 'xls',
				'application/x-msexcel'                => 'xls',
				'application/x-ms-excel'               => 'xls',
				'application/x-excel'                  => 'xls',
				'application/x-dos_ms_excel'           => 'xls',
				'application/xls'                      => 'xls',
				'application/x-xls'                    => 'xls',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
				'application/vnd.ms-excel'             => 'xlsx',
				'application/xml'                      => 'xml',
				'text/xml'                             => 'xml',
				'text/xsl'                             => 'xsl',
				'application/xspf+xml'                 => 'xspf',
				'application/x-compress'               => 'z',
				'application/x-zip'                    => 'zip',
				'application/zip'                      => 'zip',
				'application/x-zip-compressed'         => 'zip',
				'application/s-compressed'             => 'zip',
				'multipart/x-zip'                      => 'zip',
				'text/x-scriptzsh'                     => 'zsh',
			);

			return isset( $mime_map[ $mime ] ) ? $mime_map[ $mime ] : false;
		}
		// }

		public function save_image( $data ) {

			$odooApi    = new WC_ODOO_API();
			$base64_img = base64_decode( $data['image_1024'] );
			$f          = finfo_open();
			$mime_type  = finfo_buffer( $f, $base64_img, FILEINFO_MIME_TYPE );
			$title      = str_replace( ' ', '_', $data['name'] ) . '_' . $data['id'];
			// Upload dir.
			$upload_dir  = wp_upload_dir();
			$upload_path = str_replace( '/', DIRECTORY_SEPARATOR, $upload_dir['path'] ) . DIRECTORY_SEPARATOR;

			$helper = self::getHelper();

			$decoded         = $base64_img;
			$filename        = $title . '.' . $this->mime2ext( $mime_type );
			$file_type       = $mime_type;
			$hashed_filename = md5( $filename . microtime() ) . '_' . $filename;
			// Save the image in the uploads directory.
			$upload_file = file_put_contents( $upload_path . $hashed_filename, $decoded );

			$attachment = array(
				'post_mime_type' => $file_type,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $hashed_filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'guid'           => $upload_dir['url'] . '/' . basename( $hashed_filename ),
			);

			$image_path = $upload_dir['path'] . '/' . $hashed_filename;
			$attach_id  = wp_insert_attachment( $attachment, $image_path );

			$imagenew     = get_post( $attach_id );
			$fullsizepath = get_attached_file( $imagenew->ID );
			require_once ABSPATH . 'wp-admin/includes/image.php';
			// Generate and save the attachment metas into the database
			$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			return $attach_id;
		}
		
		public function object_to_array( $obj ) {
			if (is_object($obj)) {
$obj = (array) $obj;
			}
			if (is_array($obj)) {
				$new = array();
				foreach ($obj as $key => $val) {
					$new[$key] = $this->object_to_array($val);
				}
			} else {
				$new = $obj;
			}
			return $new;
		}

//$product_data_array = object_to_array($product_data);
	}

endif;
