<?php
/**
 * Created by PhpStorm.
 * User: Villatheme-Thanh
 * Date: 30-09-19
 * Time: 8:18 AM
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//$_SERVER['HTTP_USER_AGENT']='/google.com';

class WOOMULTI_CURRENCY_Plugin_Google_Index {
	protected $settings;

	public function __construct() {
		$this->settings = WOOMULTI_CURRENCY_Data::get_ins();
		add_action( 'init', array( $this, 'set_default_currency_if_isbot' ), 999 );
	}

	public function set_default_currency_if_isbot() {
		if ( $this->settings->get_enable() ) {
			$this->set_origin_currency();
		}
	}

	public function set_origin_currency() {
		if ( $this->isBot() ) {
			$this->settings->set_current_currency( apply_filters( 'wmc_set_currency_for_bot_index', $this->settings->get_default_currency() ) );
		}
	}

	public function isBot( ) {
		$bots = array(
			'pixel',
			'facebook',
			'rambler',
			'googlebot',
			'aport',
			'yahoo',
			'msnbot',
			'turtle',
			'mail.ru',
			'omsktele',
			'yetibot',
			'picsearch',
			'sape.bot',
			'sape_context',
			'gigabot',
			'snapbot',
			'alexa.com',
			'megadownload.net',
			'askpeter.info',
			'igde.ru',
			'ask.com',
			'qwartabot',
			'yanga.co.uk',
			'scoutjet',
			'similarpages',
			'oozbot',
			'shrinktheweb.com',
			'aboutusbot',
			'followsite.com',
			'dataparksearch',
			'google-sitemaps',
			'appEngine-google',
			'feedfetcher-google',
			'liveinternet.ru',
			'xml-sitemaps.com',
			'agama',
			'metadatalabs.com',
			'h1.hrn.ru',
			'googlealert.com',
			'seo-rus.com',
			'yaDirectBot',
			'yandeG',
			'yandex',
			'yandexSomething',
			'Copyscape.com',
			'AdsBot-Google',
			'domaintools.com',
			'Nigma.ru',
			'bing.com',
			'dotnetdotcom',
			'google'
		);
		foreach ( $bots as $bot ) {
			if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ( stripos( $_SERVER['HTTP_USER_AGENT'], $bot ) !== false || preg_match( '/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'] ) ) ) {
				return true;
			}
		}

		return false;
	}
}
