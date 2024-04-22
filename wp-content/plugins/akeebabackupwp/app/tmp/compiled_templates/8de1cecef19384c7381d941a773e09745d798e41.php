<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Manage/manage_column.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Text\Text;
use Solo\Helper\Utils as AkeebaHelperUtils;

defined('_AKEEBA') or die();

/** @var  array $record */
/** @var  Solo\View\Manage\Html $this */

$router = $this->container->router;

if (!isset($record['remote_filename']))
{
	$record['remote_filename'] = '';
}

$archiveExists = $record['meta'] == 'ok';
$showManageRemote = $record['hasRemoteFiles'] && (AKEEBABACKUP_PRO == 1);
$engineForProfile = array_key_exists($record['profile_id'], $this->enginesPerProfile) ? $this->enginesPerProfile[$record['profile_id']] : 'none';
$showUploadRemote = $this->privileges['backup'] && $archiveExists && !$showManageRemote && ($engineForProfile != 'none') && ($record['meta'] != 'obsolete') && (AKEEBABACKUP_PRO == 1);
$showDownload = $this->privileges['download'] && $archiveExists;
$showViewLog = $this->privileges['backup'] && isset($record['backupid']) && !empty($record['backupid']);
$postProcEngine = '';
$thisPart = '';
$thisID = urlencode($record['id']);

if ($showUploadRemote)
{
	$postProcEngine = $engineForProfile ?: 'none';
	$showUploadRemote = !empty($postProcEngine);
}

?>
<div style="display: none">
    <div id="akeeba-buadmin-<?php echo (int)$record['id']; ?>" tabindex="-1" role="dialog">
        <div class="akeeba-renderer-fef <?php echo ($this->getContainer()->appConfig->get('darkmode', -1) == 1) ? 'akeeba-renderer-fef--dark' : ''; ?>">
            <h4><?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_BACKUPINFO'); ?></h4>

            <p>
                <strong><?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_ARCHIVEEXISTS'); ?></strong><br />
                <?php if($record['meta'] == 'ok'): ?>
                    <span class="akeeba-label--success">
					<?php echo \Awf\Text\Text::_('SOLO_YES'); ?>
				</span>
                <?php else: ?>
                    <span class="akeeba-label--failure">
					<?php echo \Awf\Text\Text::_('SOLO_NO'); ?>
				</span>
                <?php endif; ?>
            </p>
            <p>
                <strong><?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_ARCHIVEPATH' . ($archiveExists ? '' : '_PAST')); ?></strong>
                <br />
                <span class="akeeba-label--information">
		        <?php echo $this->escape(AkeebaHelperUtils::getRelativePath(APATH_BASE, dirname($record['absolute_path']))); ?>

		</span>
            </p>
            <p>
                <strong><?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_ARCHIVENAME' . ($archiveExists ? '' : '_PAST')); ?></strong>
                <br />
                <code>
                    <?php echo $this->escape($record['archivename']); ?>

                </code>
            </p>
        </div>
    </div>

    <?php if($showDownload): ?>
        <div id="akeeba-buadmin-download-<?php echo (int) $record['id']; ?>" tabindex="-2" role="dialog">
            <div class="akeeba-renderer-fef <?php echo ($this->getContainer()->appConfig->get('darkmode', -1) == 1) ? 'akeeba-renderer-fef--dark' : ''; ?>">
                <div class="akeeba-block--warning">
                    <?php if(defined('WPINC') && !$this->showBrowserDownload): ?>
                        <h4>
                            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_TITLE_NODOWNLOAD'); ?>
                        </h4>
                        <p>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD'); ?>
                        </p>
                        <p>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_REENABLE'); ?>
                        </p>
                        <p>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_ALTERNATIVE'); ?>
                        </p>
                    <?php elseif($this->phpErrorDisplay === -1): ?>
                        <h4>
                            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_TITLE_NODOWNLOAD_PHPERRORDISPLAY'); ?>
                        </h4>
                        <p>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_PHPERRORDISPLAY_UNKNOWN'); ?>
                        </p>
                    <?php elseif($this->phpErrorDisplay === 1): ?>
                        <h4>
                            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_TITLE_NODOWNLOAD_PHPERRORDISPLAY'); ?>
                        </h4>
                        <p>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_PHPERRORDISPLAY_ENABLED'); ?>
                        </p>
                        <?php if(defined('WP_DEBUG') && WP_DEBUG): ?>
                            <p><?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_PHPERRORDISPLAY_WPDEBUG'); ?></p>
                        <?php elseif(defined('AKEEBADEBUG')): ?>
                            <?php if(defined('WPINC')): ?>
                            <p>
                                <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_PHPERRORDISPLAY_AKEEBADEBUG', str_replace(WP_CONTENT_URL . '/', '', plugins_url('helpers', AkeebaBackupWP::$absoluteFileName))); ?>
                            </p>
                            <?php else: ?>
                            <p>
                                <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_PHPERRORDISPLAY_AKEEBADEBUG', 'app'); ?>
                            </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <h4>
                            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_TITLE'); ?>
                        </h4>
                        <?php if(defined('WPINC')): ?>
                            <p>
                                <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_WORDPRESS'); ?>
                            </p>
                        <?php endif; ?>
                        <p>
                            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING'); ?>
                        </p>
                    <?php endif; ?>
                </div>


                <?php if((defined('WPINC') && !$this->showBrowserDownload) || $this->phpErrorDisplay != 0): ?>
                    <div class="akeeba-block--info">
	                    <?php
	                    $archiveName = $record['archivename'];
	                    $extension = substr($archiveName, -4);
	                    $firstPart = substr($extension, 0, 2) . '01';
	                    $lastPart = substr($extension, 0, 2) . sprintf('%02u', min($record['multipart'] - 1, 1));
	                    ?>
                        <?php if($record['multipart'] < 2): ?>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_MULTIPART_1', $archiveName); ?>
                        <?php elseif($record['multipart'] < 3): ?>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_MULTIPART_2', substr($archiveName, 0, -4), $extension, $firstPart); ?>
                        <?php else: ?>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_WARNING_NODOWNLOAD_MULTIPART', $record['multipart'], substr($archiveName, 0, -4), $extension, $firstPart, $lastPart); ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if($record['multipart'] < 2): ?>
                        <a class="akeeba-btn--primary--small comAkeebaManageDownloadButton"
                           data-id="<?php echo $this->escape($record['id']); ?>">
                            <span class="akion-ios-download"></span>
                            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LOG_DOWNLOAD'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if($record['multipart'] >= 2): ?>
                        <div>
                            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LBL_DOWNLOAD_PARTS', $record['multipart']); ?>
                        </div>
                        <?php for($count = 0; $count < $record['multipart']; $count++): ?>
                            <?php if($count > 0): ?>
                            &bull;
                            <?php endif; ?>
                            <a class="akeeba-btn--small--dark comAkeebaManageDownloadButton"
                               data-id="<?php echo $this->escape($record['id']); ?>"
                               data-part="<?php echo $this->escape($count); ?>">
                                <span class="akion-android-download"></span>
                                <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LABEL_PART', $count); ?>
                            </a>
                        <?php endfor; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if($showManageRemote): ?>
    <div style="padding-bottom: 3pt;">
        <a class="akeeba-btn--primary akeeba_remote_management_link"
           data-management="<?php echo $this->escape($router->route('index.php?view=Remotefiles&tmpl=component&task=listActions&id=' . (int)$record['id'])); ?>"
           data-reload="<?php echo $this->escape($router->route('index.php?view=Manage')); ?>"
        >
            <span class="akion-cloud"></span>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_REMOTEFILEMGMT'); ?>
        </a>
    </div>
<?php elseif($showUploadRemote): ?>
    <a class="akeeba-btn--primary akeeba_upload"
       data-upload="<?php echo $this->escape($router->route('index.php?view=Upload&tmpl=component&task=start&id=' . $record['id'])); ?>"
       data-reload="<?php echo $this->escape($router->route('index.php?view=Manage')); ?>"
       title="<?php echo Text::sprintf('COM_AKEEBA_TRANSFER_DESC', Text::_("ENGINE_POSTPROC_{$postProcEngine}_TITLE")) ?>"
    >
        <span class="akion-android-upload"></span>
        <?php echo \Awf\Text\Text::_('COM_AKEEBA_TRANSFER_TITLE'); ?>
        (<em><?php echo $postProcEngine; ?></em>)
    </a>
<?php endif; ?>

<div style="padding-bottom: 3pt">
    <?php if($showDownload): ?>
        <a class="akeeba-btn--<?php echo $showManageRemote || $showUploadRemote ? 'small--grey' : 'green'; ?> akeeba_download_button"
           data-dltarget="#akeeba-buadmin-download-<?php echo (int)$record['id']; ?>"
        >
            <span class="akion-android-download"></span>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LOG_DOWNLOAD'); ?>
        </a>
    <?php endif; ?>

    <?php if($showViewLog): ?>
        <a class="akeeba-btn--grey akeebaCommentPopover"
           <?php echo ($record['meta'] != 'obsolete') ? '' : 'disabled="disabled"'; ?>

           href="<?php echo $this->container->router->route('index.php?view=Log&tag=' . $this->escape($record['tag']) . '.' . $this->escape($record['backupid']) . '&task=start&profileid=' . $record['profile_id']); ?>"
           data-original-title="<?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_LOGFILEID'); ?>"
           data-content="<?php echo $this->escape($record['backupid']); ?>"
        >
            <span class="akion-ios-search-strong"></span>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_LOG'); ?>
        </a>
    <?php endif; ?>

    <a class="akeeba-btn--grey--small akeebaCommentPopover akeeba_showinfo_link"
       data-infotarget="#akeeba-buadmin-<?php echo (int)$record['id']; ?>"
       data-content="<?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LBL_BACKUPINFO'); ?>"
    >
        <span class="akion-information-circled"></span>
    </a>
</div>
