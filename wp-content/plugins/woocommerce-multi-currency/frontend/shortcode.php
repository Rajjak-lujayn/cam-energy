<?php

/**
 * Class WOOMULTI_CURRENCY_Frontend_Shortcode
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOOMULTI_CURRENCY_Frontend_Shortcode {
	protected $settings;
	protected $current_url;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();

		$items = $this->settings->get_list_shortcodes();
		foreach ( $items as $k => $item ) {
			if ( $k ) {
				add_shortcode(
					'woo_multi_currency_' . $k, array(
						$this,
						'shortcode_' . $k
					)
				);
			}
		}

		add_shortcode( 'woo_multi_currency', array( $this, 'shortcode_woo_multi_currency' ) );
		add_shortcode( 'woo_multi_currency_exchange', array( $this, 'woo_multi_currency_exchange' ) );
		add_shortcode( 'woo_multi_currency_rates', array( $this, 'woo_multi_currency_rates' ) );
		add_filter( 'wmc_shortcode', array( $this, 'replace_shortcode' ), 10, 2 );
		add_shortcode( 'woo_multi_currency_flatsome_mobile_menu', array( $this, 'shortcode_flatsome_mobile_menu' ) );
		$this->current_url = ! empty( $_POST['wmc_current_url'] ) ? sanitize_text_field( $_POST['wmc_current_url'] ) : remove_query_arg( 'wmc-currency' );
	}

	/**
	 * Shortcode Currency selector
	 */
	public function shortcode_woo_multi_currency() {
		$args = array( 'settings' => WOOMULTI_CURRENCY_Data::get_ins(), 'shortcode' => 'default' );
		ob_start();
		wmc_get_template( 'woo-multi-currency-selector.php', $args );

		return ob_get_clean();
	}

	/**
	 * Replace shortcode
	 *
	 * @param $shortcode
	 * @param $data
	 *
	 * @return string
	 */
	public function replace_shortcode( $shortcode, $data ) {
		$layout    = isset( $data['layout'] ) ? $data['layout'] : '';
		$flag_size = isset( $data['flag_size'] ) ? $data['flag_size'] : '';
		$attr      = '';

		if ( $flag_size ) {
			$attr = 'flag_size =' . $flag_size;
		}
		if ( $layout ) {
			$shortcode = '[woo_multi_currency_' . $layout . ' ' . $attr . ']';
		}

		return $shortcode;
	}

	/**
	 * Shortcode show list currency rates
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return float|int|string
	 */
	public function woo_multi_currency_rates( $atts, $content = null ) {
		extract(
			shortcode_atts(
				array(
					'currencies' => '',
				), $atts
			)
		);
		if ( $currencies ) {
			$currencies = array_map( 'strtoupper', array_map( 'trim', array_filter( explode( ',', $currencies ) ) ) );
		} else {
			$currencies = array();
		}
		$list_currencies  = $this->settings->get_list_currencies();
		$currency_default = $this->settings->get_default_currency();
		ob_start(); ?>
        <div class="woocommerce-multi-currency wmc-shortcode wmc-list-currency-rates">
			<?php
			if ( count( $currencies ) ) {
				foreach ( $currencies as $currency ) {
					if ( array_key_exists( $currency, $list_currencies ) ) {
						if ( $currency == $currency_default ) {
							continue;
						} ?>
                        <div class="wmc-currency-rate">
							<?php echo $currency_default . '/' . $currency ?> = <?php
							echo $list_currencies[ $currency ]['rate'];
							?>
                        </div>
					<?php }
				}
			} else {
				foreach ( $list_currencies as $key => $currency ) {
					if ( $key == $currency_default ) {
						continue;
					} ?>
                    <div class="wmc-currency-rate">
						<?php echo $currency_default . '/' . $key ?> = <?php
						echo $currency['rate'];
						?>
                    </div>
				<?php }
			} ?>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shortcode exchange
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return float|int|string
	 */
	public function woo_multi_currency_exchange( $atts, $content = null ) {
		extract(
			shortcode_atts(
				array(
					'price'    => '',
					'currency' => '',
				), $atts
			)
		);
		if ( $price ) {
			$selected_currencies = $this->settings->get_list_currencies();

			if ( $currency && is_array( $selected_currencies[ $currency ] ) ) {
				$data = $selected_currencies[ $currency ];
				switch ( $data['pos'] ) {
					case 'left' :
						$format = '%1$s%2$s';
						break;
					case 'right' :
						$format = '%2$s%1$s';
						break;
					case 'left_space' :
						$format = '%1$s&nbsp;%2$s';
						break;
					case 'right_space' :
						$format = '%2$s&nbsp;%1$s';
						break;
				}
				$args = array(
					'currency'     => $currency,
					'decimals'     => $data['decimals'],
					'price_format' => $format
				);

				return "<span class='wmc-cache-value' data-value='{$price}' data-currency='{$currency}' >" . wc_price( wmc_get_price( $price, $currency ), $args ) . '</span>';

			} else {
				return "<span class='wmc-cache-value' data-value='{$price}' data-currency='{$currency}' >" . wc_price( wmc_get_price( $price ) ) . '</span>';
			}
		} else {
			return '';
		}
	}

	/**
	 * Shortcode plain horizontal
	 * @return string
	 */
	public function shortcode_plain_horizontal( $atts, $content = null ) {

		extract(
			shortcode_atts(
				array(
					'title' => ''
				), $atts
			)
		);
		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}
		$current_currency = $this->settings->get_current_currency();
		$links            = $this->settings->get_links();
		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-horizontal" data-layout="plain_horizontal">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
			<?php foreach ( $links as $k => $link ) {
				if ( $current_currency ) {
					if ( $current_currency == $k ) {
						$class = "wmc-active";
					} else {
						$class = '';
					}
				}

				/*End override*/
				?>
                <div class="wmc-currency <?php echo esc_attr( $class ) ?>">
					<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                    <a rel="nofollow" class="wmc-currency-redirect" href="#"
                       data-currency="<?php echo esc_attr( $k ) ?>">
						<?php }else{ ?>
                        <a rel="nofollow" href="<?php echo $class ? '#' : esc_url( $link ) ?>">
							<?php } ?>
							<?php echo esc_html( $k ) ?>
                        </a>
                </div>
			<?php } ?>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Plain vertical
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function shortcode_plain_vertical_2( $atts, $content = null ) {
		$args = array( 'settings' => WOOMULTI_CURRENCY_Data::get_ins(), 'shortcode' => 'listbox_code' );
		ob_start();
		wmc_get_template( 'woo-multi-currency-selector.php', $args );

		return ob_get_clean();
	}

	public function shortcode_plain_vertical( $atts, $content = null ) {
		extract(
			shortcode_atts(
				array(
					'title'  => '',
					'symbol' => '',
				), $atts
			)
		);
		$links            = $this->settings->get_links();
		$current_currency = $this->settings->get_current_currency();
		$current_symbol   = $symbol ? ' / ' . get_woocommerce_currency_symbol( $current_currency ) : '';

		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}

		$class = $this->get_position_option();

		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-vertical <?php echo esc_attr( $class ) ?>" data-layout="plain_vertical">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
            <div class="wmc-currency-wrapper" onclick="">
				<span class="wmc-current-currency">
					<?php echo esc_html( $current_currency . $current_symbol ) ?>
				</span>
                <div class="wmc-sub-currency">
					<?php foreach ( $links as $k => $link ) {
						if ( $current_currency == $k ) {
							continue;
						}
						$sub_symbol = $symbol ? ' / ' . get_woocommerce_currency_symbol( $k ) : '';
						?>
                        <div class="wmc-currency">
							<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                            <a rel="nofollow" class="wmc-currency-redirect" href="#"
                               data-currency="<?php echo esc_attr( $k ) ?>">
								<?php }else{ ?>
                                <a rel="nofollow" href="<?php echo esc_url( $link ) ?>">
									<?php } ?>
									<?php echo esc_html( $k . $sub_symbol ) ?></a>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * List Flag Horizontal
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function shortcode_layout3( $atts, $content = null ) {
		$this->enqueue_flag_css();
		extract(
			shortcode_atts(
				array(
					'title'     => '',
					'flag_size' => 0.6
				), $atts
			)
		);
		$current_currency = $this->settings->get_current_currency();
		$links            = $this->settings->get_links();
		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}

		$class = $this->get_position_option();

		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-horizontal layout3 <?php echo esc_attr( $class ) ?>" data-layout="layout3">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
			<?php foreach ( $links as $k => $link ) {
				if ( $current_currency ) {
					if ( $current_currency == $k ) {
						$class = "wmc-active";
					} else {
						$class = '';
					}
				}
				/*End override*/
				$country = $this->settings->get_country_data( $k );

				?>
                <div class="wmc-currency <?php echo esc_attr( $class ) ?>">
					<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                    <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>" class="wmc-currency-redirect"
                       href="#" data-currency="<?php echo esc_attr( $k ) ?>">
						<?php }else{ ?>
                        <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                           href="<?php echo $class ? '#' : esc_url( $link ) ?>">
							<?php } ?>
                            <i style="<?php echo $this->fix_style( $flag_size ) ?>"
                               class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "></i>
                        </a>
                </div>
			<?php } ?>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	public function enqueue_flag_css() {
		if ( WP_DEBUG ) {
			wp_enqueue_style( 'wmc-flags', WOOMULTI_CURRENCY_CSS . 'flags-64.css' );
		} else {
			wp_enqueue_style( 'wmc-flags', WOOMULTI_CURRENCY_CSS . 'flags-64.min.css' );
		}

	}

	public function fix_style( $flag_size ) {
		$margin_width = ( 60 - 60 * $flag_size ) / 2;
		$margin_heigh = ( 40 - 40 * $flag_size ) / 2;
		$style        = "transform: scale({$flag_size}); margin: -{$margin_heigh}px -{$margin_width}px";

		return $style;
	}

	/**
	 * List Flags vertical
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function shortcode_layout4( $atts, $content = null ) {
		$this->enqueue_flag_css();
		extract(
			shortcode_atts(
				array(
					'title'     => '',
					'flag_size' => 0.6
				), $atts
			)
		);
		$links            = $this->settings->get_links();
		$current_currency = $this->settings->get_current_currency();
		$country          = $this->settings->get_country_data( $current_currency );
		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}
		$class = $this->get_position_option();
		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-vertical layout4 <?php echo esc_attr( $class ) ?>"
             data-layout="layout4" data-flag_size="<?php echo esc_attr( $flag_size ) ?>">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
            <div class="wmc-currency-wrapper" onclick="">
				<span class="wmc-current-currency">
                       <i style="<?php echo $this->fix_style( $flag_size ) ?>"
                          alt="<?php echo esc_attr( $country['name'] ) ?>"
                          class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "> </i>
                    <span class="wmc-current-currency-arrow"></span>
				</span>
                <div class="wmc-sub-currency">
					<?php foreach ( $links as $k => $link ) {
						if ( $current_currency == $k ) {
							continue;
						}
						/*End override*/
						$country = $this->settings->get_country_data( $k );
						?>
                        <div class="wmc-currency">
							<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                            <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                               class="wmc-currency-redirect" href="#" data-currency="<?php echo esc_attr( $k ) ?>">
								<?php }else{ ?>
                                <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                                   href="<?php echo esc_url( $link ) ?>">
									<?php } ?>
                                    <i style="<?php echo $this->fix_style( $flag_size ) ?>"
                                       class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "></i>
                                </a>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	public function get_position_option() {
		$class  = '';
		$sticky = $this->settings->get_param( 'shortcode_position' );
		if ( in_array( $sticky, array_keys( WOOMULTI_CURRENCY_Data::$pos_options ) ) ) {
			$class = 'wmc-shortcode-fixed ' . $sticky;
		}

		return $class;
	}

	/**
	 * List Flags + Currency code
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function shortcode_layout5( $atts, $content = null ) {
		$this->enqueue_flag_css();

		extract(
			shortcode_atts(
				array(
					'title'        => '',
					'flag_size'    => 0.6,
					'symbol'       => '',
					'country_name' => '',
				), $atts
			)
		);

		$links                    = $this->settings->get_links();
		$current_currency         = $this->settings->get_current_currency();
		$country                  = $this->settings->get_country_data( $current_currency );
		$display_current_currency = $country_name ? $country['name'] : $current_currency;
		$display_current_currency = apply_filters( 'wmc_shortcode_custom_currency', $display_current_currency );
		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}

		$class = $this->get_position_option();
		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-vertical layout5 <?php echo esc_attr( $class ) ?>" data-layout="layout5">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
            <div class="wmc-currency-wrapper">
				<span class="wmc-current-currency" style="line-height: <?php echo $flag_size * 40 ?>px">
                    <i style="<?php echo $this->fix_style( $flag_size ) ?>"
                       class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "> </i>
                    <span>
                        <?php echo esc_html( $display_current_currency ) ?>
                        <?php echo( $symbol ? ', ' . get_woocommerce_currency_symbol( $current_currency ) : '' ); ?>
                    </span>
				</span>
                <div class="wmc-sub-currency">
					<?php foreach ( $links as $k => $link ) {
						if ( $current_currency == $k ) {
							continue;
						}

						/*End override*/
						$country          = $this->settings->get_country_data( $k );
						$display_currency = $country_name ? $country['name'] : $k;
						$display_currency = apply_filters( 'wmc_shortcode_custom_currency', $display_currency );
						?>
                        <div class="wmc-currency <?php echo esc_attr( $display_currency ) ?>">
							<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                            <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                               class="wmc-currency-redirect" href="#" data-currency="<?php echo esc_attr( $k ) ?>">
								<?php }else{ ?>
                                <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                                   href="<?php echo $link ?>" style="line-height: <?php echo $flag_size * 40 ?>px">
									<?php } ?>
                                    <i style="<?php echo $this->fix_style( $flag_size ) ?>"
                                       class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "> </i>
                                    <span><?php echo esc_html( $display_currency ) ?></span>
									<?php echo( $symbol ? ', ' . get_woocommerce_currency_symbol( $k ) : '' ); ?>
                                </a>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Shortcode pain horizontal currencies
	 * @return string
	 */
	public function shortcode_layout6( $atts, $content = null ) {

		extract(
			shortcode_atts(
				array(
					'title' => '',
				), $atts
			)
		);
		$links            = $this->settings->get_links();
		$current_currency = $this->settings->get_current_currency();
		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}
		$class = $this->get_position_option();

		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-horizontal layout6 <?php echo esc_attr( $class ) ?>" data-layout="layout6">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
			<?php
			foreach ( $links as $k => $link ) {
				if ( $current_currency ) {
					if ( $current_currency == $k ) {
						$class = "wmc-active";
					} else {
						$class = '';
					}
				}
				?>
                <div class="wmc-currency <?php echo esc_attr( $class ) ?>">
					<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                    <a rel="nofollow" class="wmc-currency-redirect" href="#"
                       data-currency="<?php echo esc_attr( $k ) ?>">
						<?php }else{ ?>
                        <a rel="nofollow" href="<?php echo $class ? '#' : esc_url( $link ) ?>">
							<?php } ?>
							<?php echo esc_html( get_woocommerce_currency_symbol( $k ) ) ?></a>
                </div>
			<?php } ?>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Pain vertical currency symbols
	 *
	 * @param      $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function shortcode_layout7( $atts, $content = null ) {
		extract(
			shortcode_atts(
				array(
					'title' => '',
				), $atts
			)
		);
		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}
		$current_currency = $this->settings->get_current_currency();
		$symbol           = get_woocommerce_currency_symbol( $current_currency );
		$links            = $this->settings->get_links();
		$class            = $this->get_position_option();
		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-vertical vertical-currency-symbols <?php echo esc_attr( $class ) ?>" data-layout="layout7">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
            <div class="wmc-currency-wrapper">
				<span class="wmc-current-currency">
					<?php echo $symbol ?>
				</span>

                <div class="wmc-sub-currency">
					<?php foreach ( $links as $k => $link ) {

						if ( $current_currency == $k ) {
							continue;
						}
						?>
                        <div class="wmc-currency">
							<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                            <a rel="nofollow" class="wmc-currency-redirect" href="#"
                               data-currency="<?php echo esc_attr( $k ) ?>">
								<?php }else{ ?>
                                <a rel="nofollow" href="<?php echo esc_url( $link ) ?>">
									<?php } ?>
									<?php echo get_woocommerce_currency_symbol( $k ); ?></a>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	public function shortcode_layout8( $atts, $content = null ) {
		ob_start();
		$current_currency = $this->settings->get_current_currency();
		$symbol           = get_woocommerce_currency_symbol( $current_currency );
		$links            = $this->settings->get_links();
		$class            = $this->get_position_option();
		$fix_class        = ctype_alpha( substr( $symbol, 0, 2 ) ) && strlen( $symbol ) >= 3 ? 'wmc-fix-font' : '';
		?>
        <div class="woocommerce-multi-currency wmc-shortcode vertical-currency-symbols-circle <?php echo esc_attr( $class ) ?>" data-layout="layout8">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
            <div class="wmc-currency-wrapper" onclick="">
				<span class="wmc-current-currency <?php echo esc_attr( $fix_class ) ?>">
					<?php echo esc_html( $symbol ) ?>
				</span>

                <div class="wmc-sub-currency">
					<?php foreach ( $links as $k => $link ) {
						if ( $current_currency == $k ) {
							continue;
						}
						?>
                        <div class="wmc-currency">
							<?php
							$symbol    = esc_html( get_woocommerce_currency_symbol( $k ) );
							$fix_class = ctype_alpha( substr( $symbol, 0, 2 ) ) && strlen( $symbol ) >= 3 ? 'wmc-fix-font' : '';
							if ( $this->settings->enable_switch_currency_by_js() ) {
								$k = esc_attr( $k );
								echo "<a rel='nofollow' class='wmc-currency-redirect {$fix_class}' href='#' data-currency='{$k}'>{$symbol}</a>";
							} else {
								$link = esc_url( $link );
								echo "<a rel='nofollow' class='{$fix_class}' href='{$link}'>{$symbol}</a>";
							}
							?>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div>
		<?php

		return ob_get_clean();
	}

	public function shortcode_layout9( $atts, $content = null ) {
		$current_currency     = $this->settings->get_current_currency();
		$links                = $this->settings->get_links();
		$class                = $this->get_position_option();
		$current_currency_pos = array_search( $current_currency, array_keys( $links ), true );
		$left_arr             = array_slice( $links, 0, $current_currency_pos );
		$right_arr            = array_slice( $links, $current_currency_pos );
		ob_start();
		?>
        <div class="woocommerce-multi-currency wmc-shortcode layout9 <?php echo esc_attr( $class ) ?>" data-layout="layout9">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
            <div class="wmc-currency-wrapper">
				<?php
				if ( is_array( $left_arr ) && count( $left_arr ) ) {
					$i = 0;
					foreach ( $left_arr as $code => $link ) {
						$symbol = get_woocommerce_currency_symbol( $code );
						?>
                        <div class="wmc-currency wmc-left" style="z-index: <?php echo esc_attr( $i ++ ) ?>">
							<?php
							if ( $this->settings->enable_switch_currency_by_js() ) {
								$code = esc_attr( $code );
								echo "<a rel='nofollow' class='wmc-currency-redirect ' href='#' data-currency='{$code}'>{$symbol}</a>";
							} else {
								$link = esc_url( $link );
								echo "<a rel='nofollow' href='{$link}'>{$symbol}</a>";
							}
							?>
                        </div>
						<?php
					}
				}

				if ( is_array( $right_arr ) && $i = count( $right_arr ) ) {
					foreach ( $right_arr as $code => $link ) {
						$active           = $current_currency == $code ? 'wmc-active' : '';
						$z_index          = $current_currency == $code ? 999 : $i --;
						$align            = $current_currency == $code ? 'wmc-current-currency' : 'wmc-right';
						$symbol           = get_woocommerce_currency_symbol( $code );
						$current_currency = $current_currency == $code && $current_currency != $symbol ? $current_currency : '';
						?>
                        <div class="wmc-currency <?php echo esc_attr( $align ) . ' ' . esc_attr( $active ) ?>"
                             style="z-index: <?php echo esc_attr( $z_index ) ?>">
							<?php
							if ( $this->settings->enable_switch_currency_by_js() ) {
								$code = esc_attr( $code );
								echo "<a rel='nofollow' class='wmc-currency-redirect ' href='#' data-currency='{$code}'>{$current_currency} {$symbol}</a>";
							} else {
								$link = esc_url( $link );
								echo "<a rel='nofollow' href='{$link}'>{$current_currency} {$symbol}</a>";
							}
							?>
                        </div>
						<?php
					}
				}
				?>
            </div>
        </div>
		<?php

		return ob_get_clean();
	}

	public function shortcode_layout10( $atts, $content = null ) {
		$this->enqueue_flag_css();

		extract(
			shortcode_atts(
				array(
					'title'     => '',
					'flag_size' => 0.4,
					'symbol'    => '',
				), $atts
			)
		);

		$links            = $this->settings->get_links();
		$current_currency = $this->settings->get_current_currency();
		ob_start();
		if ( $title ) {
			echo '<h3>' . $title . '</h3>';
		}
		$data_flag_size = $flag_size;
		$class          = $this->get_position_option();
		$countries      = get_woocommerce_currencies();
		$line_height    = ( $flag_size * 40 ) . 'px';
		$flag_size      = $this->fix_style( $flag_size );
		?>
        <div class="woocommerce-multi-currency wmc-shortcode plain-vertical layout10 <?php echo esc_attr( $class ) ?>"
             data-layout="layout10" data-flag_size="<?php echo esc_attr( $data_flag_size ) ?>">
            <input type="hidden" class="wmc-current-url" value="<?php echo esc_attr( $this->current_url ) ?>">
            <div class="wmc-currency-wrapper">
				<span class="wmc-current-currency" style="line-height: <?php echo esc_attr( $line_height ) ?>">
                    <?php
                    $country_data = $this->settings->get_country_data( $current_currency );
                    $country_code = strtolower( $country_data['code'] );
                    $symbol       = get_woocommerce_currency_symbol( $current_currency );

                    echo "<span class=''>";
                    echo "<i style='{$flag_size}' class='vi-flag-64 flag-{$country_code}'></i>";
                    echo "<span class='wmc-text'>({$current_currency}) {$symbol}</span></span>";
                    ?>

                <i class="wmc-open-dropdown-currencies"></i>

                </span>
                <div class="wmc-sub-currency">
					<?php foreach ( $links as $k => $link ) {
						if ( $current_currency == $k ) {
							continue;
						}

						$hidden = $current_currency == $k ? 'wmc-hidden' : '';
						/*End override*/
						$country = $this->settings->get_country_data( $k );

						?>
                        <div class="wmc-currency <?php echo esc_attr( $hidden ) ?>" data-currency="<?php echo esc_attr( $k ) ?>">
							<?php
							$html = '';
							if ( $this->settings->enable_switch_currency_by_js() ) {
								$html .= sprintf( "<a rel='nofollow'  class='wmc-currency-redirect' href='#' data-currency='%1s'>", esc_attr( $k ) );
							} else {
								$html .= sprintf( "<a rel='nofollow' class='' href='%1s' style='line-height:%2s'>", esc_url( $link ), $line_height );
							}
							$html .= sprintf( "<i style='%1s' class='vi-flag-64 flag-%2s'></i>", $flag_size, strtolower( $country['code'] ) );
							$html .= sprintf( "<span class=''>%1s</span>", esc_html( $countries[ $k ] ) );
							$html .= sprintf( "<span class=''>(%1s)</span>", esc_html( get_woocommerce_currency_symbol( $k ) ) );
							$html .= '</a>';
							echo $html;
							?>
                        </div>
					<?php } ?>
                </div>
            </div>
        </div>
		<?php

		return ob_get_clean();
	}

	public function shortcode_flatsome_mobile_menu( $atts, $content = null ) {
		$this->enqueue_flag_css();

		extract(
			shortcode_atts(
				array(
					'title'     => '',
					'flag_size' => 0.6,
					'symbol'    => ''
				), $atts
			)
		);

		$links            = $this->settings->get_links();
		$current_currency = $this->settings->get_current_currency();
		$country          = $this->settings->get_country_data( $current_currency );
		ob_start();

		?>
        <span class="wmc-flatsome-mobile-nav wmc-current-currency" style="line-height: <?php echo $flag_size * 40 ?>px">
                 <span>
                    <?php echo esc_html( $current_currency ) ?>
                    <?php echo( $symbol ? ', ' . get_woocommerce_currency_symbol( $current_currency ) : '' ); ?>
                </span>
                <i style="<?php echo $this->fix_style( $flag_size ) ?>"
                   class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "> </i>

        </span>

        <ul class="children">
			<?php foreach ( $links as $k => $link ) {
				if ( $current_currency == $k ) {
					continue;
				}

				$country = $this->settings->get_country_data( $k );
				?>
                <li class="wmc-currency">
					<?php if ( $this->settings->enable_switch_currency_by_js() ){ ?>
                    <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                       class="wmc-currency-redirect" href="#" data-currency="<?php echo esc_attr( $k ) ?>">
						<?php }else{ ?>
                        <a rel="nofollow" title="<?php echo esc_attr( $country['name'] ) ?>"
                           href="<?php echo $link ?>" style="line-height: <?php echo $flag_size * 40 ?>px">
							<?php } ?>
                            <span><?php echo esc_html( $k ) ?></span>
                            <i style="<?php echo $this->fix_style( $flag_size ) ?>"
                               class="vi-flag-64 flag-<?php echo strtolower( $country['code'] ) ?> "> </i>
							<?php echo( $symbol ? ', ' . get_woocommerce_currency_symbol( $k ) : '' ); ?>
                        </a>
                </li>
			<?php } ?>
        </ul>
		<?php

		$html = ob_get_clean();

		return $html;
	}

	public function shortcode_custom_work_layout( $attr, $content = null ) {
		do_action( 'wmc_custom_work_layout', $attr, $content );
	}
}