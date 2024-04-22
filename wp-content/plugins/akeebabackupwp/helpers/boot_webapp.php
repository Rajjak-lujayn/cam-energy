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

/**
 * @param   \Awf\Application\Application  $application
 */
function akeebaBackupWPMainApplicationLoop($application)
{
	// Initialise the application
	$application->initialise();

	// Route the URL: parses the URL through routing rules, replacing the data in the app's input
	$application->route();

	// Dispatch the application
	$application->dispatch();

	// Render the output
	$application->render();

	// Persist messages if they exist.
	if (count($application->messageQueue))
	{
		$application->getContainer()->segment->setFlash('application_queue', $application->messageQueue);
	}

	$application->getContainer()->session->commit();

	if (defined('AKEEBA_SOLOWP_OBFLAG'))
	{
		@ob_start();
	}
}

/**
 * @param   Exception|Throwable           $exc
 * @param   \Awf\Application\Application  $application
 */
function akeebaBackupWPErrorHandler($exc, $application)
{
	$filename = null;

	if (is_object($application) && ($application instanceof \Awf\Application\Application))
	{
		$template = $application->getTemplate();

		if (file_exists(APATH_THEMES . '/' . $template . '/error.php'))
		{
			$filename = APATH_THEMES . '/' . $template . '/error.php';
		}
	}

	if (is_null($filename))
	{
		echo "<h1>Application Error</h1>\n";
		echo "<p>Please submit the following error message and trace in its entirety when requesting support</p>\n";
		echo "<div class=\"alert alert-danger\">" . get_class($exc) . ' &mdash; ' . $exc->getMessage() . "</div>\n";
		echo "<pre class=\"well\">\n";
		echo $exc->getTraceAsString();
		echo "</pre>\n";

		return;
	}

	include $filename;
}

if (version_compare(PHP_VERSION, '7.0.0', 'ge'))
{
	try
	{
		// Create the application
		$application = $container->application;

		akeebaBackupWPMainApplicationLoop($application);
	}
	catch (Throwable $exc)
	{
		akeebaBackupWPErrorHandler($exc, $application);
	}
}
else
{
	try
	{
		// Create the application
		$application = $container->application;

		akeebaBackupWPMainApplicationLoop($application);
	}
	catch (Exception $exc)
	{
		akeebaBackupWPErrorHandler($exc, $application);
	}
}