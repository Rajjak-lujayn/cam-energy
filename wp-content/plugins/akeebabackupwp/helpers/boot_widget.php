<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Bootstrap file for Akeeba Solo for WordPress

/**
 * Make sure we are being called from WordPress itself
 */
defined('WPINC') or die;

defined('AKEEBASOLO') or define('AKEEBASOLO', 1);

// A trick to prevent raw views from rendering the entire WP back-end interface
if (defined('AKEEBA_SOLOWP_OBFLAG'))
{
	@ob_get_clean();
}

global $akeebaBackupWordPressLoadPlatform;
$akeebaBackupWordPressLoadPlatform = true;
/** @var \Solo\Container $container */
$container = require 'integration.php';

if ($container->input->get->getBool('_ak_reset_session', false))
{
	$container->session->clear();
}
