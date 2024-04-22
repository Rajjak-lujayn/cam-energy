<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Solo;

use Awf\Database\Installer;

class PostUpgradeScript
{
	/** @var \Awf\Container\Container|null The container of the application we are running in */
	protected $container = null;

	/**
	 * @var array Files to remove from all versions
	 */
	protected $removeFilesAllVersions = [
		'media/css/bootstrap-namespaced.css',
		'media/css/bootstrap-switch.css',
		'media/css/datepicker.css',
		'media/css/theme.css',
		'media/js/bootstrap-switch.js',
		'media/js/piecon.js',
		'media/js/solo/alice.js',
		'media/js/solo/backup.js',
		'media/js/solo/configuration.js',
		'media/js/solo/dbfilters.js',
		'media/js/solo/encryption.js',
		'media/js/solo/extradirs.js',
		'media/js/solo/fsfilters.js',
		'media/js/solo/gui-helpers.js',
		'media/js/solo/multidb.js',
		'media/js/solo/regexdbfilters.js',
		'media/js/solo/regexfsfilters.js',
		'media/js/solo/restore.js',
		'media/js/solo/setup.js',
		'media/js/solo/stepper.js',
		'media/js/solo/system.js',
		'media/js/solo/update.js',
		'media/js/solo/wizard.js',
		// Removed in version 1.2 (introducing Akeeba Engine 2)
		'Solo/engine/platform/abstract.php',
		'Solo/engine/platform/interface.php',
		'Solo/engine/platform/platform.php',
		// Removed with the introduction of the new S3v4 connector for Amazon S3
		'Solo/engine/Postproc/Connector/Amazons3.php',
		'Solo/engine/Postproc/S3.php',
		'Solo/engine/Postproc/s3.ini',
		'Solo/engine/Postproc/s3.json',
		// Dropbox v1 integration
		'Solo/engine/Postproc/dropbox.ini',
		'Solo/engine/Postproc/dropbox.json',
		'Solo/engine/Postproc/Dropbox.php',
		'Solo/engine/Postproc/Connector/Dropbox.php',
		// Obsolete Azure files
		'Solo/engine/Postproc/Connector/Azure/Credentials/Sharedsignature.php',
		// Obsolete Mautic integration
		'Solo/assets/installers/angie-mautic.ini',
		'Solo/assets/installers/angie-mautic.jpa',
		'Solo/Platform/Solo/Filter/MauticSkipDirs.php',
		'Solo/Platform/Solo/Filter/MauticSkipFiles.php',
		'Solo/Pythia/Oracle/Mautic.php',
		// Obsolete AES-128 CTR implementation in Javascript
		'media/js/solo/encryption.min.js',
		'media/js/solo/encryption.min.map',
		// PHP 7.2 compatibility
		'Solo/engine/Base/Object.php',
		// Bootstrap-based theme
		'media/css/bootstrap.css.map',
		'media/css/bootstrap.min.css',
		'media/css/bootstrap-joomla.min.css',
		'media/css/bootstrap-namespaced.min.css',
		'media/css/bootstrap-prestashop.min.css',
		'media/css/bootstrap-switch.min.css',
		'media/css/bootstrap-wordpress.min.css',
		'media/css/font-awesome.min.css',
		'media/fonts/FontAwesome.ttf',
		'media/fonts/akeeba-backup-origin.eot',
		'media/fonts/akeeba-backup-origin.svg',
		'media/fonts/akeeba-backup-origin.ttf',
		'media/fonts/akeeba-backup-origin.woff',
		'media/fonts/fontawesome-webfont.eot',
		'media/fonts/fontawesome-webfont.svg',
		'media/fonts/fontawesome-webfont.ttf',
		'media/fonts/fontawesome-webfont.woff',
		'media/fonts/glyphicons-halflings-regular.eot',
		'media/fonts/glyphicons-halflings-regular.svg',
		'media/fonts/glyphicons-halflings-regular.ttf',
		'media/fonts/glyphicons-halflings-regular.woff',
		'media/image/akeeba-ui-32.png',
		'media/image/quickicon-ok-48.png',
		'media/image/quickicon-warning-48.png',
		'media/js/bootstrap.min.js',
		'media/js/bootstrap-switch.min.js',
		'media/js/html5shiv.min.js',
		'media/js/respond.min.js',
		'media/js/selectize.min.js',

		// Removed platforms
		'Solo/Pythia/Oracle/Drupal7.php',
		'Solo/Pythia/Oracle/Drupal8.php',
		'Solo/Pythia/Oracle/Grav.php',
		'Solo/Pythia/Oracle/Magento.php',
		'Solo/Pythia/Oracle/Magento2.php',
		'Solo/Pythia/Oracle/Moodle.php',
		'Solo/Pythia/Oracle/Octobercms.php',
		'Solo/Pythia/Oracle/Pagekit.php',
		'Solo/Pythia/Oracle/Phpbb.php',
		'Solo/Platform/Solo/Filter/Drupal7TableData.php',
		'Solo/Platform/Solo/Filter/Drupal8TableData.php',
		'Solo/Platform/Solo/Filter/GravSkipDirs.php',
		'Solo/Platform/Solo/Filter/GravSkipFiles.php',
		'Solo/Platform/Solo/Filter/MagentoSkipDirs.php',
		'Solo/Platform/Solo/Filter/MagentoSkipFiles.php',
		'Solo/Platform/Solo/Filter/OctobercmsSkipDirs.php',
		'Solo/Platform/Solo/Filter/OctobercmsSkipFiles.php',
		'Solo/Platform/Solo/Filter/OctobercmsTableData.php',
		'Solo/Platform/Solo/Filter/PagekitSkipDirs.php',
		'Solo/Platform/Solo/Filter/PagekitSkipFiles.php',
		'Solo/Platform/Solo/Filter/PagekitTableData.php',
		'Solo/Platform/Solo/Filter/PrestashopSkipDirs.php',
		'Solo/Platform/Solo/Filter/PrestashopSkipFiles.php',
		'Solo/Platform/Solo/Filter/PrestashopTableData.php',

		// Migration of Akeeba Engine to JSON format
		"Solo/engine/Dump/native.ini",
		"Solo/engine/Dump/reverse.ini",
		"Solo/engine/Postproc/none.ini",
		"Solo/engine/Postproc/webdav.ini",
		"Solo/engine/Postproc/sugarsync.ini",
		"Solo/engine/Postproc/email.ini",
		"Solo/engine/Postproc/box.ini",
		"Solo/engine/Postproc/dropbox2.ini",
		"Solo/engine/Postproc/ovh.ini",
		"Solo/engine/Postproc/cloudme.ini",
		"Solo/engine/Postproc/idrivesync.ini",
		"Solo/engine/Postproc/ftpcurl.ini",
		"Solo/engine/Postproc/dreamobjects.ini",
		"Solo/engine/Postproc/azure.ini",
		"Solo/engine/Postproc/sftp.ini",
		"Solo/engine/Postproc/amazons3.ini",
		"Solo/engine/Postproc/cloudfiles.ini",
		"Solo/engine/Postproc/googlestorage.ini",
		"Solo/engine/Postproc/googlestoragejson.ini",
		"Solo/engine/Postproc/swift.ini",
		"Solo/engine/Postproc/sftpcurl.ini",
		"Solo/engine/Postproc/onedrive.ini",
		"Solo/engine/Postproc/googledrive.ini",
		"Solo/engine/Postproc/backblaze.ini",
		"Solo/engine/Postproc/ftp.ini",
		"Solo/engine/Archiver/zipnative.ini",
		"Solo/engine/Archiver/directftp.ini",
		"Solo/engine/Archiver/directsftpcurl.ini",
		"Solo/engine/Archiver/zip.ini",
		"Solo/engine/Archiver/directftpcurl.ini",
		"Solo/engine/Archiver/directsftp.ini",
		"Solo/engine/Archiver/jps.ini",
		"Solo/engine/Archiver/jpa.ini",
		"Solo/engine/Scan/smart.ini",
		"Solo/engine/Scan/large.ini",
		"Solo/engine/Filter/Stack/dateconditional.ini",
		"Solo/engine/Filter/Stack/errorlogs.ini",
		"Solo/engine/Filter/Stack/hoststats.ini",
		"Solo/engine/Core/04.quota.ini",
		"Solo/engine/Core/02.advanced.ini",
		"Solo/engine/Core/01.basic.ini",
		"Solo/engine/Core/scripting.ini",
		"Solo/engine/Core/05.tuning.ini",

		"Solo/Platform/Solo/Config/04.quota.ini",
		"Solo/Platform/Solo/Config/02.advanced.ini",
		"Solo/Platform/Solo/Config/Pro/04.quota.ini",
		"Solo/Platform/Solo/Config/Pro/02.advanced.ini",
		"Solo/Platform/Solo/Config/Pro/01.basic.ini",
		"Solo/Platform/Solo/Config/Pro/02.platform.ini",
		"Solo/Platform/Solo/Config/Pro/03.filters.ini",
		"Solo/Platform/Solo/Config/Pro/05.tuning.ini",
		"Solo/Platform/Solo/Config/01.basic.ini",
		"Solo/Platform/Solo/Config/05.tuning.ini",
		"Solo/Platform/Solo/Filter/Stack/myjoomla.ini",
		"Solo/Platform/Solo/Filter/Stack/actionlogs.ini",

		// PostgreSQL and MS SQL Server support
		'Solo/engine/Driver/Pgsql.php',
		'Solo/engine/Driver/Postgresql.php',
		'Solo/engine/Driver/Sqlazure.php',
		'Solo/engine/Driver/Sqlsrv.php',
		'Solo/engine/Driver/Query/Pgsql.php',
		'Solo/engine/Driver/Query/Postgresql.php',
		'Solo/engine/Driver/Query/Sqlazure.php',
		'Solo/engine/Driver/Query/Sqlsrv.php',
		'Solo/engine/Dump/reverse.json',
		'Solo/engine/Dump/Reverse.php',
		'Solo/engine/Dump/Native/Postgresql.php',
		'Solo/engine/Dump/Native/Sqlsrv.php',

		'Solo/assets/sql/xml/postgresql.xml',
		'Solo/assets/sql/xml/sqlsrv.xml',

		// Engine 7
		'Solo/engine/Base/BaseObject.php',

		// ALICE refactoring
		"media/js/solo/alice.min.js",
		"media/js/solo/alice.min.map",
		'media/js/solo/stepper.min.js',
		'media/js/solo/stepper.min.map',

		// Version 7 -- remove non-RAW JSON API encapsulation
		"Solo/Model/Json/Encapsulation/AesCbc128.php",
		"Solo/Model/Json/Encapsulation/AesCbc256.php",
		"Solo/Model/Json/Encapsulation/AesCtr128.php",
		"Solo/Model/Json/Encapsulation/AesCtr256.php",

		// Obsolete base views
		"Solo/View/DataHtml.php",
		"Solo/View/Html.php",

		// Obsolete loadScripts
		"media/js/solo/loadscripts.min.js",
		"media/js/solo/loadscripts.min.map",

		// Obsolete scripts
		"Solo/ViewTemplates/Backup/script.blade.php",

		// Obsolete copy of the cacert.pem file
		"Solo/engine/cacert.pem",

		// Changelog PNG images
		'media/image/changelog.png',

		// Remove iDriveSync — the service has been discontinued
		'Solo/engine/Postproc/idrivesync.json',
		'Solo/engine/Postproc/Idrivesync.php',
		'Solo/engine/Postproc/Connector/Idrivesync.php',

		// Remove piecon
		'media/js/piecon.min.js',

		// Removed legacy Azure connector
		'Solo/engine/Postproc/Connector/Azure.php',

		// Remove legacy filters
		"Solo/Platform/Solo/Filter/Stack/myjoomla.json",
		"Solo/Platform/Solo/Filter/Stack/StackMyjoomla.php",
	];

	/**
	 * @var array Files to remove from Pro
	 */
	protected $removeFilesPro = [

	];

	/**
	 * @var array Folders to remove from all versions
	 */
	protected $removeFoldersAllVersions = [
		// Removed in version 1.2 (introducing Akeeba Engine 2)
		'Solo/engine/platform/solo',
		'Solo/engine/abstract',
		'Solo/engine/drivers',
		'Solo/engine/engines',
		'Solo/engine/filters',
		'Solo/engine/plugins',
		'Solo/engine/utils',
		// Removed with new S3v4 connector for Amazon S3
		'Solo/engine/Postproc/Connector/Amazon',
		'Solo/engine/Postproc/Connector/Amazons3',
		// Dropbox v1 integration
		'Solo/engine/Postproc/Connector/Dropbox',
		// Bootstrap-based theme
		'media/css/selectize',
		'media/less',

		// ALICE refactoring
		"Solo/alice",

		// Conversion to Blade
		'Solo/View/Alice/tmpl',
		'Solo/View/Backup/tmpl',
		'Solo/View/Browser/tmpl',
		'Solo/View/Common',
		'Solo/View/Configuration/tmpl',
		'Solo/View/Dbfilters/tmpl',
		'Solo/View/Discover/tmpl',
		'Solo/View/Extradirs/tmpl',
		'Solo/View/Fsfilters/tmpl',
		'Solo/View/Log/tmpl',
		'Solo/View/Login/tmpl',
		'Solo/View/Main/tmpl',
		'Solo/View/Manage/tmpl',
		'Solo/View/Multidb/tmpl',
		'Solo/View/Phpinfo/tmpl',
		'Solo/View/Profiles/tmpl',
		'Solo/View/Regexdbfilters/tmpl',
		'Solo/View/Regexfsfilters/tmpl',
		'Solo/View/Remotefiles/tmpl',
		'Solo/View/Restore/tmpl',
		'Solo/View/S3import/tmpl',
		'Solo/View/Schedule/tmpl',
		'Solo/View/Setup/tmpl',
		'Solo/View/Sysconfig/tmpl',
		'Solo/View/Transfer/tmpl',
		'Solo/View/Update/tmpl',
		'Solo/View/Upload/tmpl',
		'Solo/View/Users/tmpl',
		'Solo/View/Wizard/tmpl',

		// Precompiled templates
		'Solo/PrecompiledTemplates',

		// Obsolete jQuery stuff
		'media/js/datepicker',
		'media/js/dist',

		// Removed the “Archive integrity check” feature.
		'Solo/Platform/Solo/Finalization',

		// Removed legacy Azure connector
		'Solo/engine/Postproc/Connector/Azure',

		// AWF is installed via Composer now
		'Awf',
	];

	/**
	 * @var array Folders to remove from Core
	 */
	protected $removeFoldersCore = [
		// CLI scripts
		'cli',
		// Pro engine features
		'Solo/engine/plugins',
		'Solo/engine/Postproc/Connector',
		'Solo/Platform/Solo/Config/Pro',

		// Pro application features
		'Solo/AliceChecks',

		'Solo/Model/Json',

		'Solo/View/Alice',
		'Solo/View/Discover',
		'Solo/View/Extradirs',
		'Solo/View/Multidb',
		'Solo/View/Regexdbfilters',
		'Solo/View/Regexfsfilters',
		'Solo/View/Remotefiles',
		'Solo/View/Restore',
		'Solo/View/S3import',
		'Solo/View/Schedule',
		'Solo/View/Transfer',
		'Solo/View/Upload',

		'Solo/ViewTemplates/Alice',
		'Solo/ViewTemplates/Discover',
		'Solo/ViewTemplates/Extradirs',
		'Solo/ViewTemplates/Multidb',
		'Solo/ViewTemplates/Regexdbfilters',
		'Solo/ViewTemplates/Regexfsfilters',
		'Solo/ViewTemplates/Remotefiles',
		'Solo/ViewTemplates/Restore',
		'Solo/ViewTemplates/S3import',
		'Solo/ViewTemplates/Schedule',
		'Solo/ViewTemplates/Transfer',
		'Solo/ViewTemplates/Upload',

		// Version 7 -- JSON and legacy API
		'Solo/Model/Json',

	];

	/**
	 * @var array Files to remove from Core
	 */
	protected $removeFilesCore = [
		// Pro engine features
		// -- Archivers
		'Solo/engine/Archiver/directftp.ini',
		'Solo/engine/Archiver/directftp.json',
		'Solo/engine/Archiver/Directftp.php',
		'Solo/engine/Archiver/directftpcurl.ini',
		'Solo/engine/Archiver/directftpcurl.json',
		'Solo/engine/Archiver/Directftpcurl.php',
		'Solo/engine/Archiver/directsftp.ini',
		'Solo/engine/Archiver/directsftp.json',
		'Solo/engine/Archiver/Directsftp.php',
		'Solo/engine/Archiver/directsftpcurl.ini',
		'Solo/engine/Archiver/directsftpcurl.json',
		'Solo/engine/Archiver/Directsftpcurl.php',
		'Solo/engine/Archiver/jps.ini',
		'Solo/engine/Archiver/jps.json',
		'Solo/engine/Archiver/Jps.php',
		'Solo/engine/Archiver/zipnative.ini',
		'Solo/engine/Archiver/zipnative.json',
		'Solo/engine/Archiver/Zipnative.php',
		// -- Filters
		'Solo/engine/Filter/Extradirs.php',
		'Solo/engine/Filter/Multidb.php',
		'Solo/engine/Filter/Regexdirectories.php',
		'Solo/engine/Filter/Regexfiles.php',
		'Solo/engine/Filter/Regexskipdirs.php',
		'Solo/engine/Filter/Regexskipfiles.php',
		'Solo/engine/Filter/Regexskiptabledata.php',
		'Solo/engine/Filter/Regexskiptables.php',
		// -- Post-processing engines
		'Solo/engine/Postproc/amazons3.ini',
		'Solo/engine/Postproc/amazons3.json',
		'Solo/engine/Postproc/Amazons3.php',
		'Solo/engine/Postproc/azure.ini',
		'Solo/engine/Postproc/azure.json',
		'Solo/engine/Postproc/Azure.php',
		'Solo/engine/Postproc/backblaze.ini',
		'Solo/engine/Postproc/backblaze.json',
		'Solo/engine/Postproc/Backblaze.php',
		'Solo/engine/Postproc/box.ini',
		'Solo/engine/Postproc/box.json',
		'Solo/engine/Postproc/Box.php',
		'Solo/engine/Postproc/cloudfiles.ini',
		'Solo/engine/Postproc/cloudfiles.json',
		'Solo/engine/Postproc/Cloudfiles.php',
		'Solo/engine/Postproc/cloudme.ini',
		'Solo/engine/Postproc/cloudme.json',
		'Solo/engine/Postproc/Cloudme.php',
		'Solo/engine/Postproc/dreamobjects.ini',
		'Solo/engine/Postproc/dreamobjects.json',
		'Solo/engine/Postproc/Dreamobjects.php',
		'Solo/engine/Postproc/dropbox.ini',
		'Solo/engine/Postproc/dropbox.json',
		'Solo/engine/Postproc/Dropbox.php',
		'Solo/engine/Postproc/dropbox2.ini',
		'Solo/engine/Postproc/dropbox2.json',
		'Solo/engine/Postproc/Dropbox2.php',
		'Solo/engine/Postproc/ftp.ini',
		'Solo/engine/Postproc/ftp.json',
		'Solo/engine/Postproc/Ftp.php',
		'Solo/engine/Postproc/ftpcurl.ini',
		'Solo/engine/Postproc/ftpcurl.json',
		'Solo/engine/Postproc/Ftpcurl.php',
		'Solo/engine/Postproc/googledrive.ini',
		'Solo/engine/Postproc/googledrive.json',
		'Solo/engine/Postproc/Googledrive.php',
		'Solo/engine/Postproc/googlestorage.ini',
		'Solo/engine/Postproc/googlestorage.json',
		'Solo/engine/Postproc/Googlestorage.php',
		'Solo/engine/Postproc/googlestoragejson.ini',
		'Solo/engine/Postproc/googlestoragejson.json',
		'Solo/engine/Postproc/Googlestoragejson.php',
		'Solo/engine/Postproc/idrivesync.ini',
		'Solo/engine/Postproc/idrivesync.json',
		'Solo/engine/Postproc/Idrivesync.php',
		'Solo/engine/Postproc/onedrive.ini',
		'Solo/engine/Postproc/onedrive.json',
		'Solo/engine/Postproc/Onedrive.php',
		'Solo/engine/Postproc/onedrivebusiness.ini',
		'Solo/engine/Postproc/onedrivebusiness.json',
		'Solo/engine/Postproc/Onedrivebusiness.php',
		'Solo/engine/Postproc/ovh.ini',
		'Solo/engine/Postproc/ovh.json',
		'Solo/engine/Postproc/Ovh.php',
		'Solo/engine/Postproc/pcloud.ini',
		'Solo/engine/Postproc/pcloud.json',
		'Solo/engine/Postproc/Pcloud.php',
		'Solo/engine/Postproc/Connector/Pcloud.php',
		'Solo/engine/Postproc/s3.ini',
		'Solo/engine/Postproc/s3.json',
		'Solo/engine/Postproc/S3.php',
		'Solo/engine/Postproc/sftp.ini',
		'Solo/engine/Postproc/sftp.json',
		'Solo/engine/Postproc/Sftp.php',
		'Solo/engine/Postproc/sftpcurl.ini',
		'Solo/engine/Postproc/sftpcurl.json',
		'Solo/engine/Postproc/Sftpcurl.php',
		'Solo/engine/Postproc/sugarsync.ini',
		'Solo/engine/Postproc/sugarsync.json',
		'Solo/engine/Postproc/Sugarsync.php',
		'Solo/engine/Postproc/swift.ini',
		'Solo/engine/Postproc/swift.json',
		'Solo/engine/Postproc/Swift.php',
		'Solo/engine/Postproc/webdav.ini',
		'Solo/engine/Postproc/webdav.json',
		'Solo/engine/Postproc/Webdav.php',
		// Pro application features
		'Solo/Controller/Alice.php',
		'Solo/Controller/Api.php',
		'Solo/Controller/Check.php',
		'Solo/Controller/Discover.php',
		'Solo/Controller/Extradirs.php',
		'Solo/Controller/Json.php',
		'Solo/Controller/Multidb.php',
		'Solo/Controller/Regexdbfilters.php',
		'Solo/Controller/Regexfsfilters.php',
		'Solo/Controller/Remote.php',
		'Solo/Controller/Remotefiles.php',
		'Solo/Controller/Restore.php',
		'Solo/Controller/S3import.php',
		'Solo/Controller/Schedule.php',
		'Solo/Controller/Transfer.php',
		'Solo/Controller/Upload.php',

		'Solo/Model/Alice.php',
		'Solo/Model/Discover.php',
		'Solo/Model/Extradirs.php',
		'Solo/Model/Json.php',
		'Solo/Model/Multidb.php',
		'Solo/Model/Regexdbfilters.php',
		'Solo/Model/Regexfsfilters.php',
		'Solo/Model/Remotefiles.php',
		'Solo/Model/Restore.php',
		'Solo/Model/S3import.php',
		'Solo/Model/Schedule.php',
		'Solo/Model/Transfers.php',
		'Solo/Model/Upload.php',

		'media/js/solo/alice.min.js',
		'media/js/solo/alice.min.map',
		'media/js/solo/extradirs.min.js',
		'media/js/solo/extradirs.min.map',
		'media/js/solo/multidb.min.js',
		'media/js/solo/multidb.min.map',
		'media/js/solo/regexdbfilters.min.js',
		'media/js/solo/regexdbfilters.min.map',
		'media/js/solo/regexfsfilters.min.js',
		'media/js/solo/regexfsfilters.min.map',
		'media/js/solo/restore.min.js',
		'media/js/solo/restore.min.map',
		'media/js/solo/transfer.min.js',
		'media/js/solo/transfer.min.map',

		// Version 7 -- JSON and legacy API
		'remote.php',
		// NEVER DELETE restore.php – IT IS REQUIRED FOR INSTALLING UPDATES
		// 'restore.php',

		// Obsolete jQuery stuff
		'media/css/datepicker.min.css',
		'media/js/akjqnamespace.min.js',
		'media/js/jquery.min.js',
		'media/js/jquery.min.map',
		'media/js/jquery-migrate.min.js',
	];

	/**
	 * @var array Folders to remove from Pro
	 */
	protected $removeFoldersPro = [

	];

	/**
	 * Class constructor
	 *
	 * @param   \Awf\Container\Container  $container  The container of the application we are running in
	 */
	public function __construct(\Awf\Container\Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Execute the post-upgrade actions
	 */
	public function execute()
	{
		// Do not execute the post-upgrade script in the development environment
		$realPath = realpath(__DIR__);

		if (@file_exists($realPath . '/../../.nopostupgrade'))
		{
			return;
		}

		// Invalidate the Composer files' OPCache.
		if (function_exists('opcache_invalidate'))
		{
			@opcache_invalidate(APATH_BASE . '/../vendor/autoload.php', true);
			@opcache_invalidate(APATH_BASE . '/../vendor/composer/autoload_classmap.php', true);
			@opcache_invalidate(APATH_BASE . '/../vendor/composer/autoload_namespaces.php', true);
			@opcache_invalidate(APATH_BASE . '/../vendor/composer/autoload_psr4.php', true);
			@opcache_invalidate(APATH_BASE . '/../vendor/composer/autoload_real.php', true);
			@opcache_invalidate(APATH_BASE . '/../vendor/composer/autoload_static.php', true);
		}

		// Special handling for running the Solo application inside WordPress.
		if ($this->container->segment->get('insideCMS', false))
		{
			if (defined('WPINC'))
			{
				$this->_WordPressActions();
			}
		}

		// Remove obsolete files
		$this->processRemoveFiles();

		// Remove obsolete folders
		$this->processRemoveFolders();

		// Migrate profiles
		$this->migrateProfiles();

		// Migrate front-end API activation options
		$this->upgradeFrontendEnable();

	}

	/**
	 * Removes obsolete files, depending on the edition (core or pro)
	 */
	protected function processRemoveFiles()
	{
		$removeFiles = $this->removeFilesAllVersions;

		if (defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO)
		{
			$removeFiles = array_merge($removeFiles, $this->removeFilesPro);
		}
		else
		{
			$removeFiles = array_merge($removeFiles, $this->removeFilesCore);
		}

		$this->_removeFiles($removeFiles);
	}

	/**
	 * Removes obsolete folders, depending on the edition (core or pro)
	 */
	protected function processRemoveFolders()
	{
		$removeFolders = $this->removeFoldersAllVersions;

		if (defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO)
		{
			$removeFolders = array_merge($removeFolders, $this->removeFoldersPro);
		}
		else
		{
			$removeFolders = array_merge($removeFolders, $this->removeFoldersCore);
		}

		$this->_removeFolders($removeFolders);
	}

	/**
	 * Specific actions to execute when we are running inside WordPress
	 */
	private function _WordPressActions()
	{
		$this->_WordPressUpgradeToUtf8mb4();
		$this->_WordPressRemoveFolders();
		$this->_WordPressRemoveFiles();
	}

	/**
	 * Remove obsolete folders from the WordPress installation
	 *
	 * @return  void
	 */
	private function _WordPressRemoveFolders()
	{
		$removeFolders = [
			// Standalone platform
			'app/Solo/Platform',
			// Obsolete folders after the introduction of Akeeba Engine 2
			'helpers/platform/solowp',
		];

		// Remove WordPress-specific features from the Core release
		if (defined('AKEEBABACKUP_PRO') && !AKEEBABACKUP_PRO)
		{
			$removeFolders = array_merge([
				'helpers/assets/mu-plugins',
				'wpcli'

			], $removeFolders);
		}

		$fsBase = rtrim($this->container->filesystemBase, '/' . DIRECTORY_SEPARATOR) . '/../';
		$fs     = $this->container->fileSystem;

		foreach ($removeFolders as $folder)
		{
			$fs->rmdir($fsBase . $folder, true);
		}
	}

	/**
	 * Remove obsolete files from the WordPress installation
	 *
	 * @return  void
	 */
	private function _WordPressRemoveFiles()
	{
		$removeFiles = [
			// Migrating INI files to .json files
			"helpers/Platform/Wordpress/Config/04.quota.ini",
			"helpers/Platform/Wordpress/Config/02.advanced.ini",
			"helpers/Platform/Wordpress/Config/Pro/04.quota.ini",
			"helpers/Platform/Wordpress/Config/Pro/02.advanced.ini",
			"helpers/Platform/Wordpress/Config/Pro/01.basic.ini",
			"helpers/Platform/Wordpress/Config/Pro/02.platform.ini",
			"helpers/Platform/Wordpress/Config/Pro/03.filters.ini",
			"helpers/Platform/Wordpress/Config/Pro/05.tuning.ini",
			"helpers/Platform/Wordpress/Config/01.basic.ini",
			"helpers/Platform/Wordpress/Config/02.platform.ini",
			"helpers/Platform/Wordpress/Config/05.tuning.ini",
		];

		// Remove WordPress-specific features from the Core release
		if (defined('AKEEBABACKUP_PRO') && !AKEEBABACKUP_PRO)
		{
			$additionalFiles = [
				'helpers/boot_wpcli.php',
			];

			$removeFiles = array_merge($removeFiles, $additionalFiles);
		}

		if (empty($removeFiles))
		{
			return;
		}

		$fsBase = rtrim($this->container->filesystemBase, '/' . DIRECTORY_SEPARATOR) . '/../';
		$fs     = $this->container->fileSystem;

		foreach ($removeFiles as $file)
		{
			$fs->delete($fsBase . $file);
		}
	}

	/**
	 * Update WordPress tables to utf8mb4 if required
	 */
	private function _WordPressUpgradeToUtf8mb4()
	{
		/** @var  wpdb $wpdb */
		global $wpdb;

		// Is it really WordPress?
		if (!is_object($wpdb))
		{
			return;
		}

		// Is it really WordPress?
		if (!method_exists($wpdb, 'has_cap'))
		{
			return;
		}

		// Does the database support utf8mb4 at all?
		if (!$wpdb->has_cap('utf8mb4'))
		{
			return;
		}

		// Is the actual charset set to utf8mb4?
		$charset = strtolower($wpdb->charset);

		if ($charset != 'utf8mb4')
		{
			return;
		}

		// OK, all conditions met, let's upgrade the tables to utf8mb4
		$dbInstaller = new Installer($this->container);
		$dbInstaller->setForcedFile($this->container->basePath . '/assets/sql/xml/utf8mb4_update.xml');
		$dbInstaller->updateSchema();

		return;
	}

	/**
	 * Removes obsolete files given on a list
	 *
	 * @param   array  $removeFiles  List of files to remove
	 *
	 * @return void
	 */
	private function _removeFiles(array $removeFiles)
	{
		if (empty($removeFiles))
		{
			return;
		}

		$fsBase = rtrim($this->container->filesystemBase, '/' . DIRECTORY_SEPARATOR) . '/';
		$fs     = $this->container->fileSystem;

		foreach ($removeFiles as $file)
		{
			$fs->delete($fsBase . $file);
		}
	}

	/**
	 * Removes obsolete folders given on a list
	 *
	 * @param   array  $removeFolders  List of folders to remove
	 *
	 * @return void
	 */
	private function _removeFolders(array $removeFolders)
	{
		if (empty($removeFolders))
		{
			return;
		}

		$fsBase = rtrim($this->container->filesystemBase, '/' . DIRECTORY_SEPARATOR) . '/';
		$fs     = $this->container->fileSystem;

		foreach ($removeFolders as $folder)
		{
			$fs->rmdir($fsBase . $folder, true);
		}
	}

	/**
	 * Migrates existing backup profiles. The changes currently made are:
	 * – Change post-processing from "s3" (legacy) to "amazons3" (current version)
	 * – Fix profiles with invalid embedded installer settings
	 *
	 * @return  void
	 */
	private function migrateProfiles()
	{
		// Get a list of backup profiles
		$db       = $this->container->db;
		$query    = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__ak_profiles'));
		$profiles = $db->setQuery($query)->loadColumn();

		// Normally this should never happen as we're supposed to have at least profile #1
		if (empty($profiles))
		{
			return;
		}

		// Migrate each profile
		foreach ($profiles as $profile)
		{
			// Initialization
			$dirty = false;

			// Load the profile configuration
			\Akeeba\Engine\Platform::getInstance()->load_configuration($profile);
			$config = \Akeeba\Engine\Factory::getConfiguration();

			// -- Migrate obsolete "s3" engine to "amazons3"
			$postProcType = $config->get('akeeba.advanced.postproc_engine', '');

			if ($postProcType == 's3')
			{
				$config->setKeyProtection('akeeba.advanced.postproc_engine', false);
				$config->setKeyProtection('engine.postproc.amazons3.signature', false);
				$config->setKeyProtection('engine.postproc.amazons3.accesskey', false);
				$config->setKeyProtection('engine.postproc.amazons3.secretkey', false);
				$config->setKeyProtection('engine.postproc.amazons3.usessl', false);
				$config->setKeyProtection('engine.postproc.amazons3.bucket', false);
				$config->setKeyProtection('engine.postproc.amazons3.directory', false);
				$config->setKeyProtection('engine.postproc.amazons3.rrs', false);
				$config->setKeyProtection('engine.postproc.amazons3.customendpoint', false);
				$config->setKeyProtection('engine.postproc.amazons3.legacy', false);

				$config->set('akeeba.advanced.postproc_engine', 'amazons3');
				$config->set('engine.postproc.amazons3.signature', 's3');
				$config->set('engine.postproc.amazons3.accesskey', $config->get('engine.postproc.s3.accesskey'));
				$config->set('engine.postproc.amazons3.secretkey', $config->get('engine.postproc.s3.secretkey'));
				$config->set('engine.postproc.amazons3.usessl', $config->get('engine.postproc.s3.usessl'));
				$config->set('engine.postproc.amazons3.bucket', $config->get('engine.postproc.s3.bucket'));
				$config->set('engine.postproc.amazons3.directory', $config->get('engine.postproc.s3.directory'));
				$config->set('engine.postproc.amazons3.rrs', $config->get('engine.postproc.s3.rrs'));
				$config->set('engine.postproc.amazons3.customendpoint', $config->get('engine.postproc.s3.customendpoint'));
				$config->set('engine.postproc.amazons3.legacy', $config->get('engine.postproc.s3.legacy'));

				$dirty = true;
			}

			// Fix profiles with invalid embedded installer settings
			$embeddedInstaller = $config->get('akeeba.advanced.embedded_installer');

			if (empty($embeddedInstaller) || ($embeddedInstaller == 'angie-joomla') || (
					(substr($embeddedInstaller, 0, 5) != 'angie') && ($embeddedInstaller != 'none')
				))
			{
				$config->setKeyProtection('akeeba.advanced.embedded_installer', false);
				$config->set('akeeba.advanced.embedded_installer', 'angie');
				$dirty = true;
			}

			// Save dirty records
			if ($dirty)
			{
				\Akeeba\Engine\Platform::getInstance()->save_configuration($profile);
			}
		}
	}

	/**
	 * Upgrades the frontend_enable option into the two separate legacyapi_enabled and jsonapi_enabled options.
	 *
	 * Before version 7 we had a single option to control both frontend backup APIs. Starting version 7 we can enable
	 * and disable them separately.
	 */
	public function upgradeFrontendEnable()
	{
		$currentValue = $this->container->appConfig->get('options.frontend_enable', null);

		if (is_null($currentValue))
		{
			return;
		}

		$this->container->appConfig->set('options.frontend_enable', null);
		$this->container->appConfig->set('options.legacyapi_enabled', $currentValue);
		$this->container->appConfig->set('options.jsonapi_enabled', $currentValue);

		$this->container->appConfig->saveConfiguration();
	}

}
