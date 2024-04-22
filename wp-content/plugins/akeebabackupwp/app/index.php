<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Minimum PHP version check
if (!defined('AKEEBA_COMMON_WRONGPHP'))
{
	define('AKEEBA_COMMON_WRONGPHP', 1);
}
$minPHPVersion         = '7.2.0';
$recommendedPHPVersion = '7.4';
$softwareName          = 'Akeeba Solo';

if (version_compare(PHP_VERSION, $minPHPVersion, 'lt'))
{
	die(sprintf('%s requires PHP %s or later. Your site is currently using PHP %s', $softwareName, $minPHPVersion, PHP_VERSION));
}

// HHVM made sense in 2013, now PHP 7 is a way better solution than an hybrid PHP interpreter
if (defined('HHVM_VERSION'))
{
	(include_once __DIR__ . '/hhvm.php') or die('We have detected that you are running HHVM instead of PHP. This software WILL NOT WORK properly on HHVM. Please switch to PHP 7 instead.');

	return;
}

unset($minimumPHP);

// Include dependencies

use Akeeba\Engine\Platform;
use Awf\Autoloader\Autoloader;

// Include the Composer autoloader
if (function_exists('opcache_invalidate'))
{
	@opcache_invalidate(__DIR__ . '/vendor/autoload.php', true);
	@opcache_invalidate(__DIR__ . '/vendor/composer/autoload_classmap.php', true);
	@opcache_invalidate(__DIR__ . '/vendor/composer/autoload_namespaces.php', true);
	@opcache_invalidate(__DIR__ . '/vendor/composer/autoload_psr4.php', true);
	@opcache_invalidate(__DIR__ . '/vendor/composer/autoload_real.php', true);
	@opcache_invalidate(__DIR__ . '/vendor/composer/autoload_static.php', true);
}

if (false === include __DIR__ . '/vendor/autoload.php')
{
	echo 'ERROR: Composer Autoloader not found' . PHP_EOL;

	exit(1);
}

// Load the integration script
defined('AKEEBASOLO') || define('AKEEBASOLO', 1);
$dirParts = [];

if (isset($_SERVER['SCRIPT_FILENAME']))
{
	$scriptFilename = $_SERVER['SCRIPT_FILENAME'];

	if (substr(PHP_OS, 0, 3) == 'WIN')
	{
		$scriptFilename = str_replace('\\', '/', $scriptFilename);

		if (substr($scriptFilename, 0, 2) == '//')
		{
			$scriptFilename = '\\' . substr($scriptFilename, 2);
		}
	}

	$dirParts = explode('/', $_SERVER['SCRIPT_FILENAME']);
}

if (count($dirParts) > 2)
{
	$dirParts = array_splice($dirParts, 0, -2);
	$myDir    = implode(DIRECTORY_SEPARATOR, $dirParts);
}

if (@file_exists(__DIR__ . '/../helpers/integration.php'))
{
	$container = require __DIR__ . '/../helpers/integration.php';
}
elseif (@file_exists('../helpers/integration.php'))
{
	$container = require '../helpers/integration.php';
}
elseif (@file_exists($myDir . '/helpers/integration.php'))
{
	$container = require $myDir . '/helpers/integration.php';
}

// Load the platform defines
if (!defined('APATH_BASE'))
{
	require_once __DIR__ . '/defines.php';
}

// Should I enable debug?
if (defined('AKEEBADEBUG') && defined('AKEEBADEBUG_ERROR_DISPLAY'))
{
	error_reporting(E_ALL | E_NOTICE | E_DEPRECATED);
	ini_set('display_errors', 1);
}

// Add our app to the autoloader, if it's not already set
$prefixes = Autoloader::getInstance()->getPrefixes();
if (!array_key_exists('Solo\\', $prefixes))
{
	Autoloader::getInstance()->addMap('Solo\\', APATH_BASE . '/Solo');
}

// Tell the Akeeba Engine where to find a valid cacert.pem file
defined('AKEEBA_CACERT_PEM') || define('AKEEBA_CACERT_PEM', \Composer\CaBundle\CaBundle::getBundledCaBundlePath());

// Include the Akeeba Engine factory
if (!defined('AKEEBAENGINE'))
{
	define('AKEEBAENGINE', 1);
	require_once __DIR__ . '/Solo/engine/Factory.php';

	Platform::addPlatform('Solo', __DIR__ . '/Solo/Platform/Solo');
	Platform::getInstance()->load_version_defines();
	Platform::getInstance()->apply_quirk_definitions();
}

/**
 * @param   \Awf\Application\Application  $application
 */
function akeebaSoloMainApplicationLoop($application)
{
	// Initialise the application
	$application->initialise();

	// Route the URL: parses the URL through routing rules, replacing the data in the app's input
	$application->route();

	// Dispatch the application
	$application->dispatch();

	// Render the output
	$application->render();

	// Clean-up and shut down
	$application->close();
}

/**
 * @param   Exception|Throwable  $exc
 * @param   null                 $application
 */
function akeebaSoloErrorHandler($exc, $application = null)
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
		// Create the container if it doesn't already exist
		if (!isset($container))
		{
			$container = new \Solo\Container([
				'application_name' => 'Solo',
			]);
		}

		// Create the application
		$application = $container->application;

		akeebaSoloMainApplicationLoop($application);
	}
	catch (Throwable $exc)
	{
		akeebaSoloErrorHandler($exc, isset($application) ? $application : null);
	}
}
else
{
	try
	{
		// Create the container if it doesn't already exist
		if (!isset($container))
		{
			$container = new \Solo\Container([
				'application_name' => 'Solo',
			]);
		}

		// Create the application
		$application = $container->application;

		akeebaSoloMainApplicationLoop($application);
	}
	catch (Exception $exc)
	{
		akeebaSoloErrorHandler($exc, isset($application) ? $application : null);
	}
}