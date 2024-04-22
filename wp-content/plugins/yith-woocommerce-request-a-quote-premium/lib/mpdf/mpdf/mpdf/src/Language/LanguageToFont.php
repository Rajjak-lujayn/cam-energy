<?php

namespace Mpdf\Language;

class LanguageToFont implements \Mpdf\Language\LanguageToFontInterface
{

	public function getLanguageOptions($llcc, $adobeCJK)
	{
		$tags = explode('-', $llcc);
		$lang = strtolower($tags[0]);
		$country = '';
		$script = '';
		if (!empty($tags[1])) {
			if (strlen($tags[1]) === 4) {
				$script = strtolower($tags[1]);
			} else {
				$country = strtolower($tags[1]);
			}
		}
		if (!empty($tags[2])) {
			$country = strtolower($tags[2]);
		}

		$unifont = '';
		$coreSuitable = false;

		switch ($lang) {
			/* European */
			case 'en':
			case 'eng': // English		// LATIN
			case 'eu':
			case 'eus': // Basque
			case 'br':
			case 'bre': // Breton
			case 'ca':
			case 'cat': // Catalan
			case 'co':
			case 'cos': // Corsican
			case 'kw':
			case 'cor': // Cornish
			case 'cy':
			case 'cym': // Welsh
			case 'cs':
			case 'ces': // Czech
			case 'da':
			case 'dan': // Danish
			case 'nl':
			case 'nld': // Dutch
			case 'et':
			case 'est': // Estonian
			case 'fo':
			case 'fao': // Faroese
			case 'fi':
			case 'fin': // Finnish
			case 'fr':
			case 'fra': // French
			case 'gl':
			case 'glg': // Galician
			case 'de':
			case 'deu': // German
			case 'ht':
			case 'hat': // Haitian; Haitian Creole
			case 'hu':
			case 'hun': // Hungarian
			case 'ga':
			case 'gle': // Irish
			case 'is':
			case 'isl': // Icelandic
			case 'it':
			case 'ita': // Italian
			case 'la':
			case 'lat': // Latin
			case 'lb':
			case 'ltz': // Luxembourgish
			case 'li':
			case 'lim': // Limburgish
			case 'lt':
			case 'lit': // Lithuanian
			case 'lv':
			case 'lav': // Latvian
			case 'gv':
			case 'glv': // Manx
			case 'no':
			case 'nor': // Norwegian
			case 'nn':
			case 'nno': // Norwegian Nynorsk
			case 'nb':
			case 'nob': // Norwegian Bokmål
			case 'pl':
			case 'pol': // Polish
			case 'pt':
			case 'por': // Portuguese
			case 'ro':
			case 'ron': // Romanian
			case 'gd':
			case 'gla': // Scottish Gaelic
			case 'es':
			case 'spa': // Spanish
			case 'sv':
			case 'swe': // Swedish
			case 'sl':
			case 'slv': // Slovene
			case 'sk':
			case 'slk': // Slovak
				$coreSuitable = true;
				break;

			case 'ru':
			case 'rus': // Russian	// CYRILLIC
			case 'ab':
			case 'abk': // Abkhaz
			case 'av':
			case 'ava': // Avaric
			case 'ba':
			case 'bak': // Bashkir
			case 'be':
			case 'bel': // Belarusian
			case 'bg':
			case 'bul': // Bulgarian
			case 'ce':
			case 'che': // Chechen
			case 'cv':
			case 'chv': // Chuvash
			case 'kk':
			case 'kaz': // Kazakh
			case 'kv':
			case 'kom': // Komi
			case 'ky':
			case 'kir': // Kyrgyz
			case 'mk':
			case 'mkd': // Macedonian
			case 'cu':
			case 'chu': // Old Church Slavonic
			case 'os':
			case 'oss': // Ossetian
			case 'sr':
			case 'srp': // Serbian
			case 'tg':
			case 'tgk': // Tajik
			case 'tt':
			case 'tat': // Tatar
			case 'tk':
			case 'tuk': // Turkmen
			case 'uk':
			case 'ukr': // Ukrainian
				$unifont = 'dejavusanscondensed'; /* freeserif best coverage for supplements etc. */
				break;

			case 'hy':
			case 'hye': // ARMENIAN
				$unifont = 'dejavusans';
				break;
			case 'ka':
			case 'kat': // GEORGIAN
				$unifont = 'dejavusans';
				break;

			case 'el':
			case 'ell': // GREEK
				$unifont = 'dejavusanscondensed';
				break;

			/* African */
			case 'nqo':  // NKO
				$unifont = 'dejavusans';
				break;

			//CASE 'mn':  CASE 'mon':	// MONGOLIAN	(Vertical script)
			//CASE 'ug':  CASE 'uig':	// Uyghur
			//CASE 'uz':  CASE 'uzb':	// Uzbek
			//CASE 'az':  CASE 'azb':	// South Azerbaijani


			// VIETNAMESE
			case 'vi':
			case 'vie': // Vietnamese
				$unifont = 'dejavusanscondensed';
				break;

			default:
				$unifont = 'dejavusans';
		}

		return [$coreSuitable, $unifont];
	}

	protected function fontByScript($script, $adobeCJK)
	{
		switch ($script) {
			/* European */
			case 'latn': // LATIN
				return 'dejavusanscondensed';
			case 'cyrl': // CYRILLIC
				return 'dejavusanscondensed';
			case 'ogam': // OGHAM
				return 'dejavusans';

			case 'tfng': // TIFINAGH
				return 'dejavusans';

			/* Other */
			case 'brai': // BRAILLE
				return 'dejavusans';
			default:
				return 'dejavusans';
		}

		return null;
	}

}
