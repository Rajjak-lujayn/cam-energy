<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Application\Application;
use Awf\Exception\App as AWFAppException;
use Awf\Mvc\Model;
use Awf\Registry\Registry;
use Awf\Text\Text;
use Solo\Container;
use Solo\Exception\Update\ConnectionError;
use Solo\Exception\Update\PlatformError;
use Solo\Exception\Update\StabilityError;
use Solo\Exception\Update\UpdateError;

/**
 * This class will take care of bridging WordPress update system and Akeeba Backup package, fetching the info from the
 * plugin and passing back to WordPress.
 */
abstract class AkeebaBackupWPUpdater
{
	/**
	 * Private static variable keys that belong to our frozen state, stored in a site transient.
	 */
	const STATE_KEYS = [
		'needsDownloadID', 'connectionError', 'platformError', 'downloadLink', 'cantUseWpUpdate', 'stabilityError',
	];

	/** @var bool Do I need the Download ID? */
	protected static $needsDownloadID = false;

	/** @var bool Did I have a connection error while */
	protected static $connectionError = false;

	/** @var bool Do I have a platform error? (Wrong PHP or WP version) */
	protected static $platformError = false;

	/** @var string    Stores the download link. In this way we can run our logic only on our download links */
	protected static $downloadLink;

	/** @var bool    Am I in an ancient version of WordPress, were the integrated system is not usable? */
	protected static $cantUseWpUpdate = false;

	/** @var bool    Do I have an update that's less stable than my preferred stability? */
	protected static $stabilityError = false;

	/**
	 * Retrieve the update information from Akeeba Backup for WordPress' update cache and report them back to WordPress
	 * in a format it understands.
	 *
	 * The returned information is cached by WordPress and used by checkinfo() to render the Akeeba Backup for WordPress
	 * update information in WordPress' Plugins page.
	 *
	 * @param   stdClass  $transient
	 *
	 * @return  stdClass
	 * @throws  AWFAppException
	 */
	public static function getupdates($transient)
	{
		global $wp_version;
		global $akeebaBackupWordPressLoadPlatform;

		if (is_multisite() && !is_network_admin())
		{
			return $transient;
		}

		/**
		 * On WordPress < 4.3 we can't use the integrated update system since the hook we're using to apply our Download
		 * ID is not available. Instead we warn the user and tell them to use our own update system.
		 */
		if (version_compare($wp_version, '4.3', 'lt'))
		{
			static::$cantUseWpUpdate = true;
			self::freezeState();

			return $transient;
		}

		/**
		 * When the plugin is deleted, Wordpress reloads the updates. Since this file was already in memory, its code
		 * runs even if Akeeba Backup's files are not installed anymore. The following is a sanity check to prevent
		 * a PHP fatal error during the uninstallation of the plugin.
		 */
		$akeebaBackupWordPressLoadPlatform = false;

		if (!file_exists(__DIR__ . '/../helpers/integration.php'))
		{
			self::freezeState();

			return $transient;
		}

		// Do I have to notify the user that the Download ID is missing?
		if (static::needsDownloadID())
		{
			static::$needsDownloadID = true;
		}

		$updateInfo = false;

		try
		{
			$updateInfo = static::getUpdateInfo();
		}
		catch (ConnectionError $e)
		{
			// mhm... an error occurred while connecting to the updates server. Let's notify the user
			static::$connectionError = true;
		}
		catch (PlatformError $e)
		{
			static::$platformError = true;
		}
		catch (StabilityError $e)
		{
			static::$stabilityError = true;
		}
		catch (AWFAppException $e)
		{
			static::$connectionError = true;
		}

		self::freezeState();

		if (!$updateInfo)
		{
			return $transient;
		}

		if (!$transient || !isset($transient->response))
		{
			// Double check that we actually have an object to interact with. Since the $transient data is pulled from the database
			// and could be manipulated by other plugins, we might have an unexpected value here
			if (!$transient)
			{
				$transient = new stdClass();
			}

			$transient->response = [];
		}

		$dirSlug = self::getPluginSlug();

		$obj              = new stdClass();
		$obj->slug        = $dirSlug;
		$obj->plugin      = $dirSlug . '/akeebabackupwp.php';
		$obj->new_version = $updateInfo->get('version');
		$obj->url         = $updateInfo->get('infourl');
		$obj->package     = $updateInfo->get('link');
		$obj->icons       = [
			'2x' => WP_PLUGIN_URL . '/' . AkeebaBackupWP::$dirName . '/app/media/logo/abwp-256.png',
			'1x' => WP_PLUGIN_URL . '/' . AkeebaBackupWP::$dirName . '/app/media/logo/abwp-128.png',
		];

		if ($updateInfo->get('hasUpdate', false))
		{
			$transient->response = $transient->response ?? [];
			$transient->response[$dirSlug . '/akeebabackupwp.php'] = $obj;
		}
		else
		{
			$transient->no_update = $transient->no_update ?? [];
			$transient->no_update[$dirSlug . '/akeebabackupwp.php'] = $obj;
		}

		/**
		 * Since the event we're hooking to is a global one (triggered for every plugin) we have to store a reference
		 * of our download link. This way we can apply our logic only on our stuff and don't interfere with third party
		 * plugins.
		 */
		static::$downloadLink = $updateInfo->get('link');

		return $transient;
	}

	/**
	 * Used to render "View version x.x.x details" link from the plugins page.
	 * We hook to this event to redirect the connection from the WordPress directory to our site for updates
	 *
	 * @param $cur_info
	 * @param $action
	 * @param $arg
	 *
	 * @return object
	 */
	public static function checkinfo($cur_info, $action, $arg)
	{
		$dirSlug = self::getPluginSlug();

		if (!isset($arg->slug))
		{
			return $cur_info;
		}

		if ($arg->slug !== $dirSlug)
		{
			return $cur_info;
		}

		try
		{
			$updateInfo = static::getUpdateInfo();
		}
		catch (UpdateError $e)
		{
			$updateInfo = false;
		}
		catch (AWFAppException $e)
		{
			$updateInfo = false;
		}

		/**
		 * Sanity check.
		 *
		 * This if-block should never be triggered. We only ever reach this code if we have already determined there is
		 * an update available.
		 */
		if (!$updateInfo)
		{
			return $cur_info;
		}

		/**
		 * This is the information WordPress is using to render the Akeeba Backup for WordPress row in its Plugins page.
		 */
		$information = [
			// We leave the "name" index empty, so WordPress won't display the ugly title on top of our banner
			'name'          => '',
			'slug'          => $dirSlug,
			'author'        => 'Akeeba Ltd.',
			'homepage'      => 'https://www.akeeba.com',
			'last_updated'  => $updateInfo->get('date'),
			'version'       => $updateInfo->get('version'),
			'download_link' => $updateInfo->get('link'),
			'requires'      => '5.2',
			'tested'        => get_bloginfo('version'),
			'sections'      => [
				'release_notes' => $updateInfo->get('releasenotes'),
			],
			'banners'       => [
				'low'  => plugins_url() . '/' . $dirSlug . '/app/media/image/wordpressupdate_banner.jpg',
				'high' => false,
			],
		];

		return (object) $information;
	}

	/**
	 * @param   bool         $bailout
	 * @param   string       $package
	 * @param   WP_Upgrader  $upgrader
	 *
	 * @return WP_Error|false    An error if anything goes wrong or is missing, either case FALSE to keep the update
	 *                           process going
	 */
	public static function addDownloadID($bailout, $package, $upgrader)
	{
		// Process only our download links
		if ($package != static::$downloadLink)
		{
			return false;
		}

		// Do we need the Download ID (ie Pro version)?
		if (static::needsDownloadID())
		{
			return new WP_Error(403, 'Please insert your Download ID inside Akeeba Backup to fetch the updates for the Pro version');
		}

		// Our updater automatically sets the Download ID in the link, so there's no need to change anything inside the URL
		return false;
	}

	/**
	 * Helper function to change some update options on the fly. By default WordPress will delete the entire folder
	 * and abort if the folder already exists; by tweaking the options we can force WordPress to extract on top of the
	 * existing folder without deleting it first.
	 *
	 * @param   array  $options  Options to be used while upgrading our plugin
	 *
	 * @return    array    Updated options
	 */
	public static function packageOptions($options)
	{
		if (isset($options['hook_extra']) && isset($options['hook_extra']['plugin']))
		{
			// If this is our package, let's tell WordPress to extract on top of the existing folder,
			// without deleting anything
			if (stripos($options['hook_extra']['plugin'], 'akeebabackupwp.php') !== false)
			{
				$options['clear_destination']           = false;
				$options['abort_if_destination_exists'] = false;
			}
		}

		return $options;
	}

	/**
	 * Helper function to display some custom text AFTER the row regarding our update.
	 *
	 * This is typically used to communicate problems preventing the update information from being retrieved or used,
	 * meaning updates for our plugin are essentially broken.
	 *
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $status
	 */
	public static function updateMessage($plugin_file, $plugin_data, $status)
	{
		self::thawState();

		// Load enough of our plugin to display translated strings
		try
		{
			$container = static::loadAkeebaBackup();
			Text::loadLanguage(null, 'akeebabackup', '.com_akeebabackup.ini', false, $container->languagePath);
			Text::loadLanguage('en-GB', 'akeebabackup', '.com_akeebabackup.ini', false, $container->languagePath);

		}
		catch (Exception $e)
		{
			return;
		}

		$html     = '';
		$warnings = [];
		$dirSlug  = self::getPluginSlug();

		if (static::$cantUseWpUpdate)
		{
			$updateUrl = 'admin.php?page=' . $dirSlug . '/akeebabackupwp.php&view=update&force=1';

			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-noconnection\">%s</p>",
				Text::sprintf('SOLO_UPDATE_WORDPRESS_OLDER_THAN_43', $updateUrl)
			);
		}
		elseif (static::$needsDownloadID)
		{
			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-nodownloadid\">%s</p>",
				Text::_('SOLO_UPDATE_ERROR_NEEDSAUTH')
			);
		}
		elseif (static::$connectionError)
		{
			$updateUrl = 'admin.php?page=' . $dirSlug . '/akeebabackupwp.php&view=update&force=1';

			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-noconnection\">%s</p>",
				Text::sprintf('SOLO_UPDATE_WORDPRESS_CONNECTION_ERROR', $updateUrl)
			);
		}
		elseif (static::$platformError)
		{
			$warnings[] = sprintf(
				"<p id=\"akeebabackupwp-error-update-platform-mismatch\">%s</p>",
				Text::_('SOLO_UPDATE_PLATFORM_HEAD')
			);
		}
		elseif (static::$stabilityError)
		{
			/**
			 * There is an update available but it's less stable than the minimum stability preference.
			 *
			 * For example: a Beta is available but we are asked to only report stable versions.
			 *
			 * We deliberately don't show a warning. The whole point of the stability preference is to stop buggering
			 * the poor user during our pre-release runs (alphas, betas and occasional RC). In this case we just pretend
			 * there is no update available, just like we do in the interface of our plugin.
			 */
		}

		if ($warnings)
		{
			$warnings = implode('', $warnings);
			$msg      = Text::_('SOLO_UPDATE_WORDPRESS_WARNING');

			$html = <<<HTML
<tr class="">
	<th></th>
	<td></td>
	<td>
		<div style="border: 1px solid #F0AD4E;border-radius: 3px;background: #fdf5e9;padding:10px">
			<strong>$msg</strong><br/>
			$warnings		
		</div>
	</td>
</tr>
HTML;
		}

		if ($html)
		{
			echo $html;
		}
	}

	/**
	 * Includes all the required pieces to load Akeeba Backup from within a standard WordPress page
	 *
	 * @return Container|false
	 *
	 * @throws AWFAppException
	 */
	public static function loadAkeebaBackup()
	{
		static $localContainer;

		// Do not run the whole logic if we already have a valid Container
		if ($localContainer)
		{
			return $localContainer;
		}

		defined('AKEEBASOLO') || define('AKEEBASOLO', 1);

		@include_once __DIR__ . '/../app/version.php';
		
		global $akeebaBackupWordPressLoadPlatform;
		$akeebaBackupWordPressLoadPlatform = false;

		if (!file_exists(__DIR__ . '/../helpers/integration.php'))
		{
			return false;
		}

		/** @var Container $container */
		$container = require __DIR__ . '/../helpers/integration.php';

		// Ok, really don't know why but this function gets called TWICE. It seems to completely ignore the first result
		// (even if we report that there's an update) and calls it again. This means that the require_once above will be ignored.
		// I can't simply return the current $transient because it doesn't contain the updated info.
		// So I'll save a previous copy of the container and then use it later.
		if (!$localContainer)
		{
			$localContainer = $container;
		}

		if (!$localContainer)
		{
			return false;
		}

		// Get all info saved inside the configuration
		$dirSlug = self::getPluginSlug();
		$container->appConfig->loadConfiguration();
		$container->basePath = WP_PLUGIN_DIR . '/' . $dirSlug . '/app/Solo';

		// Require the application for the first time by passing all values. In this way we prime the internal cache and
		// we will be covered on cases where we fetch the application from the getInstance method instead of using the container
		$application = Application::getInstance('Solo', $container);

		// Set the update platform
		$application->setupUpdatePlatform();

		return $localContainer;
	}

	/**
	 * Fetches the update information from the remote server
	 *
	 * @return Registry|bool
	 * @throws AWFAppException
	 * @throws UpdateError
	 */
	private static function getUpdateInfo()
	{
		static $updates;

		// If I already have some update info, simply return them
		if ($updates)
		{
			return $updates;
		}

		$container = static::loadAkeebaBackup();

		if (!$container)
		{
			return false;
		}

		/** @var \Solo\Model\Update $updateModel */
		$updateModel = Model::getInstance($container->application_name, 'Update', $container);
		$updateModel->load(true);

		// No updates? Let's stop here
		$hasUpdate  = $updateModel->hasUpdate();
		$updateInfo = $updateModel->getUpdateInformation();

		if (!$hasUpdate)
		{
			// Did we get a connection error?
			if ($updateInfo->get('loadedUpdate') == false)
			{
				throw new ConnectionError();
			}

			// We might have an update that does not match the stability preference, e.g. RC with min. stability Stable.
			if ($updateInfo->get('minstabilityMatch') == false)
			{
				throw new StabilityError();
			}

			// mhm... maybe we're on a old WordPress version?
			if (!$updateInfo->get('platformMatch', 0))
			{
				throw new PlatformError();
			}
		}

		return $updateInfo;
	}

	private static function needsDownloadID()
	{
		$container = static::loadAkeebaBackup();

		// With the core version we're always good to go
		if (!AKEEBABACKUP_PRO)
		{
			return false;
		}

		// Do we need the Download ID (ie Pro version)?
		$dlid = $container->appConfig->get('options.update_dlid');

		if (!preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid))
		{
			return true;
		}

		return false;
	}

	/**
	 * Freeze the update warnings state in carbonite
	 *
	 * Just joking. We create an array with the update warnings flags and save it as a site transient.
	 */
	private static function freezeState()
	{
		$frozenState = [];

		foreach (self::STATE_KEYS as $key)
		{
			if (isset(self::${$key}))
			{
				$frozenState[$key] = self::${$key};
			}
		}

		set_site_transient('akeebabackupwp_pluginupdate_frozenstate', $frozenState);
	}

	/**
	 * Unfreeze the update warnings state
	 *
	 * We read the site transient and restore the update warnings flags from it, if it's set.
	 */
	private static function thawState()
	{
		$frozenState = get_site_transient('akeebabackupwp_pluginupdate_frozenstate');

		if (empty($frozenState) || !is_array($frozenState))
		{
			return;
		}

		foreach (self::STATE_KEYS as $key)
		{
			if (isset(self::${$key}) && isset($frozenState[$key]))
			{
				self::${$key} = $frozenState[$key];
			}
		}
	}

	/**
	 * @return mixed|string
	 */
	private static function getPluginSlug()
	{
		$pluginsUrl   = plugins_url('', realpath(__DIR__ . '/../akeebabackupwp.php'))
			?: realpath(__DIR__ . '/..');
		$baseUrlParts = explode('/', $pluginsUrl);
		$dirSlug      = end($baseUrlParts);

		if (!empty($dirSlug) && ($dirSlug != '..'))
		{
			return $dirSlug;
		}

		$fullDir  = __DIR__;
		$dirParts = explode(DIRECTORY_SEPARATOR, $fullDir, 3);
		$dirSlug  = $dirParts[1] ?? 'akeebabackup';

		return $dirSlug;
	}

}
