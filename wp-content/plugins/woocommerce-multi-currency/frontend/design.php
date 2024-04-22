<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WOOMULTI_CURRENCY_Frontend_Design
 */
class WOOMULTI_CURRENCY_Frontend_Design {
	protected $settings;

	public function __construct() {

//		$this->settings = new WOOMULTI_CURRENCY_Data();
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();

		/*Add order information*/


		add_action( 'wp_footer', array( $this, 'show_action' ) );


		if ( $this->settings->get_enable() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'front_end_script' ), 1 );
		}

	}

	/**
	 * Public
	 */
	public function front_end_script() {
		if ( WP_DEBUG ) {
			wp_enqueue_style( 'woocommerce-multi-currency', WOOMULTI_CURRENCY_CSS . 'woocommerce-multi-currency.css', array(), WOOMULTI_CURRENCY_VERSION );
		} else {
			wp_enqueue_style( 'woocommerce-multi-currency', WOOMULTI_CURRENCY_CSS . 'woocommerce-multi-currency.min.css', array(), WOOMULTI_CURRENCY_VERSION );
		}

		if ( is_rtl() ) {
			wp_enqueue_style( 'woocommerce-multi-currency-rtl', WOOMULTI_CURRENCY_CSS . 'woocommerce-multi-currency-rtl.css', array(), WOOMULTI_CURRENCY_VERSION );
		}

		/*Custom CSS*/
		$text_color                = $this->settings->get_text_color();
		$background_color          = $this->settings->get_background_color();
		$main_color                = $this->settings->get_main_color();
		$shortcode_bg_color        = $this->settings->get_param( 'shortcode_bg_color' );
		$shortcode_color           = $this->settings->get_param( 'shortcode_color' );
		$shortcode_active_bg_color = $this->settings->get_param( 'shortcode_active_bg_color' );
		$shortcode_active_color    = $this->settings->get_param( 'shortcode_active_color' );

		$links        = $this->settings->get_links();
		$currency_qty = count( $links ) - 1;

		$custom = '.woocommerce-multi-currency .wmc-list-currencies .wmc-currency.wmc-active,.woocommerce-multi-currency .wmc-list-currencies .wmc-currency:hover {background: ' . $main_color . ' !important;}
		.woocommerce-multi-currency .wmc-list-currencies .wmc-currency,.woocommerce-multi-currency .wmc-title, .woocommerce-multi-currency.wmc-price-switcher a {background: ' . $background_color . ' !important;}
		.woocommerce-multi-currency .wmc-title, .woocommerce-multi-currency .wmc-list-currencies .wmc-currency span,.woocommerce-multi-currency .wmc-list-currencies .wmc-currency a,.woocommerce-multi-currency.wmc-price-switcher a {color: ' . $text_color . ' !important;}';
		$custom .= $this->settings->get_custom_css();

		$custom .= ".woocommerce-multi-currency.wmc-shortcode .wmc-currency{background-color:{$shortcode_bg_color};color:{$shortcode_color}}";
		$custom .= ".woocommerce-multi-currency.wmc-shortcode .wmc-current-currency{background-color:{$shortcode_active_bg_color};color:{$shortcode_active_color}}";
		$custom .= ".woocommerce-multi-currency.wmc-shortcode.vertical-currency-symbols-circle .wmc-currency-wrapper:hover .wmc-sub-currency {animation: height_slide {$currency_qty}00ms;}";
		$custom .= "@keyframes height_slide {0% {height: 0;} 100% {height: {$currency_qty}00%;} }";

		wp_add_inline_style( 'woocommerce-multi-currency', $custom );

		switch ( $this->settings->get_sidebar_style() ) {
			case 2:
			case 3:
			case 4:
				$custom1 = '.woocommerce-multi-currency.wmc-sidebar.style-1 .wmc-list-currencies .wmc-currency span:not(.wmc-active-title){width:60px !important;}';
				$custom1 .= '.woocommerce-multi-currency.wmc-sidebar.wmc-right{right: -190px ;}';
				$custom1 .= '.woocommerce-multi-currency.wmc-sidebar.wmc-left{left: -190px ;}';
				wp_add_inline_style( 'woocommerce-multi-currency', $custom1 );
				break;
		}
		/*Multi currency JS*/
		if ( WP_DEBUG ) {
			wp_enqueue_script( 'woocommerce-multi-currency', WOOMULTI_CURRENCY_JS . 'woocommerce-multi-currency.js', array( 'jquery' ), WOOMULTI_CURRENCY_VERSION );
		} else {
			wp_enqueue_script( 'woocommerce-multi-currency', WOOMULTI_CURRENCY_JS . 'woocommerce-multi-currency.min.js', array( 'jquery' ), WOOMULTI_CURRENCY_VERSION );
		}

		wp_localize_script( 'woocommerce-multi-currency', 'wooMultiCurrencyParams', array(
			'enableCacheCompatible' => $this->settings->get_param( 'cache_compatible' ),
			'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
			'switchByJS'            => $this->settings->enable_switch_currency_by_js()
		) );

		if ( $this->settings->enable_switch_currency_by_js() ) {
			wp_enqueue_script( 'woocommerce-multi-currency-switcher', WOOMULTI_CURRENCY_JS . 'woocommerce-multi-currency-switcher.js', array( 'jquery' ), WOOMULTI_CURRENCY_VERSION );
			$params = array(
				'use_session' => $this->settings->use_session(),
				'ajax_url'    => admin_url( 'admin-ajax.php' )
			);
			wp_localize_script( 'woocommerce-multi-currency-switcher', '_woocommerce_multi_currency_params', $params );
		}
	}

	/**
	 * Show Currency converter
	 */
	public function show_action() {

		if ( ! $this->enable() ) {
			return;
		}

		wp_enqueue_style( 'wmc-flags', WOOMULTI_CURRENCY_CSS . 'flags-64.min.css' );

		$logic_value = $this->settings->get_conditional_tags();
		if ( $logic_value ) {
			if ( stristr( $logic_value, "return" ) === false ) {
				$logic_value = "return (" . $logic_value . ");";
			}
			if ( ! eval( $logic_value ) ) {
				return;
			}
		}

		$enable_checkout = $this->settings->get_enable_multi_payment();
		if ( ! $enable_checkout && is_checkout() ) {
			return;
		}
		$currency_selected   = $this->settings->get_current_currency();
		$title               = $this->settings->get_design_title();
		$enable_collapse     = $this->settings->enable_collapse();
		$mb_disable_collapse = $this->settings->disable_collapse();
		$class               = array();

		/*Position left or right*/
		if ( ! $this->settings->get_design_position() ) {
			$class[] = 'wmc-left';
		} else {
			$class[] = 'wmc-right';
		}

		$class[] = 'style-1';

		switch ( $this->settings->get_sidebar_style() ) {
			case 1:
				$class[] = 'wmc-currency-symbol';
				break;
			case 2:
				$class[] = 'wmc-currency-flag';
				break;
			case 3:
				$class[] = 'wmc-currency-flag wmc-currency-code';
				break;
			case 4:
				$class[] = 'wmc-currency-flag wmc-currency-symbol';
				break;
		}

		if ( $enable_collapse ) {
			$class[] = 'wmc-collapse';
		}

		if ( $mb_disable_collapse ) {
			$class[] = 'wmc-mobile-no-collapse';
		}

		$style = '';
		if ( $max_height = $this->settings->get_param( 'max_height' ) ) {
			$style = "max-height:{$max_height}px; overflow-y:auto;";
		}

		?>
        <div class="woocommerce-multi-currency <?php echo esc_attr( implode( ' ', $class ) ); ?> wmc-bottom wmc-sidebar"
             style="<?php echo esc_html( $style ) ?>">
            <div class="wmc-list-currencies">
				<?php if ( $title ) { ?>
                    <div class="wmc-title">
						<?php echo esc_html( $title ) ?>
                    </div>
				<?php } ?>
				<?php
				$links         = $this->settings->get_links();
				$currency_name = get_woocommerce_currencies();
				foreach ( $links as $k => $link ) {
					$selected = $display = '';
					$k        = esc_attr( $k );

					if ( $currency_selected == $k ) {
						$selected = 'wmc-active';
					}

					switch ( $this->settings->get_sidebar_style() ) {
						case 1:
							$symbol  = esc_html( get_woocommerce_currency_symbol( $k ) );
							$display = "<span>{$symbol}</span>";
							break;
						case 2:
						case 3:
						case 4:
							$country = esc_html( strtolower( $this->settings->get_country_data( $k )['code'] ) );
							$display = "<span><i class='vi-flag-64 flag-{$country}'></i></span>";
							break;
						default:
							$display = "<span>{$k}</span>";
					}
					?>
                    <div class="wmc-currency <?php echo esc_attr( $selected ) ?>" data-currency='<?php echo esc_attr( $k ) ?>'>
						<?php
						echo( $display );
						$active = $selected ? "wmc-active-title" : '';
						//						if ( $selected ) {
						//							echo '<span class="wmc-active-title">';
						//						} else {
						$link = esc_url( $link );
						echo $this->settings->enable_switch_currency_by_js() ?
							"<a href='#' class='wmc-currency-redirect' data-currency='{$k}'>" : "<a href='{$link}' >";
						//						}
						switch ( $this->settings->get_sidebar_style() ) {
							case 3:
								echo $k;
								break;
							case 4:
								echo get_woocommerce_currency_symbol( $k );
								break;
							default:
								echo esc_html( $currency_name[ $k ] );
						}
						?>
                        </a>
                    </div>
				<?php } ?>
                <div class="wmc-sidebar-open"></div>
            </div>
        </div>
	<?php }

	/**
	 * Check design enable
	 * @return bool
	 *
	 */
	protected
	function enable() {
		$enable = $this->settings->get_enable_design();
		if ( ! $enable ) {
			return false;
		}
		if ( $this->settings->is_checkout() ) {
			if ( is_checkout() ) {
				return false;
			}
		}
		if ( $this->settings->is_cart() ) {
			if ( is_cart() ) {
				return false;
			}
		}

		return true;
	}

}