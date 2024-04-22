<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Bootstrap file for Akeeba Solo for WordPress
use Akeeba\Engine\Platform as EnginePlatform;
use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Input\Input;
use Solo\Application\AppConfig;
use Solo\Application\UserManager;
use Solo\Pythia\Oracle\Wordpress;

/**
 * Make sure we are being called from Akeeba Solo
 */
defined('AKEEBASOLO') or die;

// Makes sure we have PHP 7.2.0 or later
if (version_compare(PHP_VERSION, '7.2.0', 'lt'))
{
	echo sprintf('Akeeba Backup for WordPress requires PHP 7.2.0 or later but your server only has PHP %s.', PHP_VERSION);
}

global $akeebaBackupWordPressContainer;
global $akeebaBackupWordPressLoadPlatform;

if (!isset($akeebaBackupWordPressLoadPlatform))
{
	$akeebaBackupWordPressLoadPlatform = true;
}

// Get the root to the Solo app itself
$akeebaBackupWpRoot = __DIR__ . '/../app/';

// Load the platform defines
if (!defined('APATH_BASE'))
{
	require_once __DIR__ . '/defines.php';
}

// Load the Composer autoloader, if not present. Normally, it SHOULD be included already.
$abwpAutoloader = include $akeebaBackupWpRoot . 'vendor/autoload.php';

if (!$abwpAutoloader instanceof \Composer\Autoload\ClassLoader)
{
	echo 'ERROR: Composer Autoloader not found' . PHP_EOL;

	exit(1);
}

// Tell the Akeeba Engine where to find a valid cacert.pem file
defined('AKEEBA_CACERT_PEM') || define('AKEEBA_CACERT_PEM', \Composer\CaBundle\CaBundle::getBundledCaBundlePath());


// Add our app to the autoloader. We use setMap instead of addMap to improve its performance.
$abwpAutoloader->setPsr4('Solo\\', [
	__DIR__ . '/Solo',
	__DIR__ . '/../app/Solo',
]);

// If we are not called from inside WordPress itself we will need to import its configuration
if (!defined('WPINC'))
{
	$foundWpConfig = false;

	$dirParts      = explode(DIRECTORY_SEPARATOR, __DIR__);
	$dirParts      = array_splice($dirParts, 0, -4);
	$filePath      = implode(DIRECTORY_SEPARATOR, $dirParts);
	$foundWpConfig = file_exists($filePath . '/wp-config.php');

	if (!$foundWpConfig)
	{
		$dirParts      = array_splice($dirParts, 0, -1);
		$altFilePath   = implode(DIRECTORY_SEPARATOR, $dirParts);
		$foundWpConfig = file_exists($altFilePath . '/wp-config.php');
	}

	if (!$foundWpConfig)
	{
		$possibleDirs = [getcwd()];

		if (isset($_SERVER['SCRIPT_FILENAME']))
		{
			$possibleDirs[] = dirname($_SERVER['SCRIPT_FILENAME']);
		}

		foreach ($possibleDirs as $scriptFolder)
		{
			// Can't use realpath() because in our dev environment it will resolve the symlinks outside the site root
			$dirParts = explode(DIRECTORY_SEPARATOR, $scriptFolder);

			$filePath = $scriptFolder;

			if (!is_file($filePath . '/wp-config.php'))
			{
				$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -2));
			}

			if (!is_file($filePath . '/wp-config.php'))
			{
				$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -3));
			}

			if (!is_file($filePath . '/wp-config.php'))
			{
				$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -4));
			}

			if (!is_file($filePath . '/wp-config.php'))
			{
				$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -5));
			}

			$foundWpConfig = file_exists($filePath . '/wp-config.php');

			if ($foundWpConfig)
			{
				$filePath = dirname(realpath($filePath . '/wp-config.php'));

				break;
			}
		}
	}

	$noWpConfig = (isset($_REQUEST) && isset($_REQUEST['no-wp-config']))
		|| (isset($argv) && in_array('--no-wp-config', $argv))
		|| @file_exists(__DIR__ . '/private/no-wp-config.txt')
		|| @file_exists(__DIR__ . '/private/wp-config.php');

	$oracle = new Wordpress($filePath);

	if ($noWpConfig)
	{
		$oracle->setLoadWPConfig(false);
	}

	if (!$oracle->isRecognised())
	{
		$filePath = realpath($filePath . '/..');
		$oracle   = new Wordpress($filePath);
	}

	if (!$oracle->isRecognised())
	{
		$curDir = __DIR__;
		echo <<< ENDTEXT
ERROR: Could not find wp-config.php

Technical information
--
integration.php directory	$curDir
filePath					$filePath
isRecognised				false
--

ENDTEXT;
		exit(1);
	}

	define('ABSPATH', $filePath);

	if (!defined('AKEEBA_SOLOWP_PATH'))
	{
		$absPluginPath = realpath(__DIR__ . '/..');
		$absPluginPath = @is_dir($absPluginPath) ? $absPluginPath : ABSPATH . '/wp-content/plugins/akeebabackupwp';

		define('AKEEBA_SOLOWP_PATH', ABSPATH . '/wp-content/plugins/akeebabackupwp');
	}

	$dbInfo = $oracle->getDbInformation();

	if (@file_exists(__DIR__ . '/private/wp-config.php'))
	{
		include_once __DIR__ . '/private/wp-config.php';
	}

	if (!defined('DB_NAME')) define('DB_NAME', $dbInfo['name']);
	if (!defined('DB_USER')) define('DB_USER', $dbInfo['username']);
	if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $dbInfo['password']);
	if (!defined('DB_HOST')) define('DB_HOST', $dbInfo['host']);

	global $table_prefix;

	// Apply the table prefix only if it hasn't been already defined before (ie from our saved configuration file)
	$table_prefix = $table_prefix ?? ($dbInfo['prefix'] ?? '');

	// Also apply detected proxy settings
	if (!empty($dbInfo['proxy_host']) && !defined('WP_PROXY_HOST')) define('WP_PROXY_HOST', $dbInfo['proxy_host']);
	if (!empty($dbInfo['proxy_port']) && !defined('WP_PROXY_PORT')) define('WP_PROXY_PORT', $dbInfo['proxy_port']);
	if (!empty($dbInfo['proxy_user']) && !defined('WP_PROXY_USERNAME')) define('WP_PROXY_USERNAME', $dbInfo['proxy_user']);
	if (!empty($dbInfo['proxy_pass']) && !defined('WP_PROXY_PASSWORD')) define('WP_PROXY_PASSWORD', $dbInfo['proxy_pass']);
}

// Include the Akeeba Engine and ALICE factories, if required
if ($akeebaBackupWordPressLoadPlatform && !defined('AKEEBAENGINE'))
{
	define('AKEEBAENGINE', 1);
	$factoryPath = $akeebaBackupWpRoot . 'Solo/engine/Factory.php';
	$alicePath   = $akeebaBackupWpRoot . 'Solo/alice/factory.php';

	// Load the engine
	if (!file_exists($factoryPath))
	{
		echo "ERROR!\n";
		echo "Could not load the backup engine; file does not exist. Technical information:\n";
		echo "Path to " . basename(__FILE__) . ": " . __DIR__ . "\n";
		echo "Path to factory file: $factoryPath\n";
		die("\n");
	}
	else
	{
		try
		{
			require_once $factoryPath;
		}
		catch (Exception $e)
		{
			echo "ERROR!\n";
			echo "Backup engine returned an error. Technical information:\n";
			echo "Error message:\n\n";
			echo $e->getMessage() . "\n\n";
			echo "Path to " . basename(__FILE__) . ":" . __DIR__ . "\n";
			echo "Path to factory file: $factoryPath\n";
			die("\n");
		}
	}

	if (file_exists($alicePath))
	{
		require_once $alicePath;
	}

	EnginePlatform::addPlatform('Wordpress', __DIR__ . '/Platform/Wordpress');
	EnginePlatform::getInstance()->load_version_defines();
	EnginePlatform::getInstance()->apply_quirk_definitions();
}

// If I already have a Container return it and exit early.
if (isset($akeebaBackupWordPressContainer))
{
	return $akeebaBackupWordPressContainer;
}

// Should I enable debug?
if (defined('AKEEBADEBUG') && defined('AKEEBADEBUG_ERROR_DISPLAY'))
{
	error_reporting(E_ALL | E_NOTICE | E_DEPRECATED);
	ini_set('display_errors', 1);
}

// Create the Container
try
{
	if (!defined('AKEEBA_SOLOWP_PATH'))
	{
		$absPluginPath = realpath(__DIR__ . '/..');
		$absPluginPath = @is_dir($absPluginPath) ? $absPluginPath : ABSPATH . '/wp-content/plugins/akeebabackupwp';

		define('AKEEBA_SOLOWP_PATH', ABSPATH . '/wp-content/plugins/akeebabackupwp');
	}

	// Create objects
	$akeebaBackupWordPressContainer = new \Solo\Container([
		'appConfig'        => function (Container $c) {
			return new AppConfig($c);
		},
		'userManager'      => function (Container $c) {
			return new UserManager($c);
		},
		'input'            => function (Container $c) {
			// WordPress is always escaping the input. WTF!
			// See http://stackoverflow.com/questions/8949768/with-magic-quotes-disabled-why-does-php-wordpress-continue-to-auto-escape-my

			global $_REAL_REQUEST;

			if (isset($_REAL_REQUEST))
			{
				return new Input($_REAL_REQUEST, ['magicQuotesWorkaround' => true]);
			}
			elseif (defined('WPINC'))
			{
				$fakeRequest = array_map('stripslashes_deep', $_REQUEST);

				return new Input($fakeRequest, ['magicQuotesWorkaround' => true]);
			}
			else
			{
				return new Input();
			}
		},
		'application_name' => 'Solo',
		'filesystemBase'   => AKEEBA_SOLOWP_PATH . '/app',
		'updateStreamURL'  => 'http://cdn.akeeba.com/updates/backupwpcore.ini',
		'changelogPath'    => AKEEBA_SOLOWP_PATH . 'CHANGELOG.php',
	]);

	$downloadId = $akeebaBackupWordPressContainer->appConfig->get('options.update_dlid', '');
	$hasPro     = AKEEBABACKUP_PRO ? true : !empty($downloadId);
	unset($downloadId);
	if ($hasPro)
	{
		$akeebaBackupWordPressContainer['updateStreamURL'] = 'http://cdn.akeeba.com/updates/backupwppro.ini';
	}
	unset($hasPro);
}
catch (Exception $exc)
{
	unset($akeebaBackupWordPressContainer);

	$filename = null;

	if (isset($application))
	{
		if ($application instanceof Application)
		{
			$template = $application->getTemplate();

			if (file_exists(APATH_THEMES . '/' . $template . '/error.php'))
			{
				$filename = APATH_THEMES . '/' . $template . '/error.php';
			}
		}
	}

	if (is_null($filename))
	{
		die($exc->getMessage());
	}

	include $filename;
}

if (!$akeebaBackupWordPressLoadPlatform)
{
	$temp_akeebaBackupWordPressContainer = $akeebaBackupWordPressContainer;
	unset($akeebaBackupWordPressContainer);

	return $temp_akeebaBackupWordPressContainer;
}

return $akeebaBackupWordPressContainer;