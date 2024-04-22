<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/icons_basic.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die();

// Used for type hinting
/** @var \Solo\View\Main\Html $this */

$router = $this->container->router;

?>
<section class="akeeba-panel--info">
    <header class="akeeba-block-header">
        <h3>
            <span class="akion-home"></span>
            <?php echo \Awf\Text\Text::_('SOLO_MAIN_LBL_HEAD_BACKUPOPS'); ?>
        </h3>
    </header>

    <div class="akeeba-grid">
        <?php if($this->canAccess('backup', 'main')): ?>
            <a class="akeeba-action--green" href="<?php echo $this->container->router->route('index.php?view=backup'); ?>">
                <span class="akion-play"></span>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_BACKUP'); ?>
            </a>
        <?php endif; ?>

        <?php if(AKEEBABACKUP_PRO && $this->canAccess('transfer', 'main')): ?>
            <a class="akeeba-action--green" href="<?php echo $this->container->router->route('index.php?view=transfer'); ?>">
                <span class="akion-android-open"></span>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_TRANSFER'); ?>
            </a>
        <?php endif; ?>

        <?php if($this->canAccess('manage', 'main')): ?>
            <a class="akeeba-action--teal" href="<?php echo $this->container->router->route('index.php?view=manage'); ?>">
                <span class="akion-ios-list"></span>
                <span class="title"><?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN'); ?></span>
            </a>
        <?php endif; ?>
        <?php if($this->canAccess('configuration', 'main')): ?>
            <a class="akeeba-action--teal" href="<?php echo $this->container->router->route('index.php?view=configuration'); ?>">
                <span class="akion-ios-gear"></span>
                <span class="title"><?php echo \Awf\Text\Text::_('COM_AKEEBA_CONFIG'); ?></span>
            </a>
        <?php endif; ?>
        <?php if($this->canAccess('profiles', 'main')): ?>
            <a class="akeeba-action--teal" href="<?php echo $this->container->router->route('index.php?view=profiles'); ?>">
                <span class="akion-person-stalker"></span>
                <span class="title"><?php echo \Awf\Text\Text::_('COM_AKEEBA_PROFILES'); ?></span>
            </a>
        <?php endif; ?>

        <?php if(!$this->needsDownloadId && $this->canAccess('update', 'main')): ?>
            <a class="akeeba-action--orange" href="<?php echo $this->container->router->route('index.php?view=update'); ?>" id="soloUpdateContainer">
                <span class="akion-checkmark-circled" id="soloUpdateIcon"></span>
                <span id="soloUpdateAvailable" style="display: none">
                        <?php echo \Awf\Text\Text::_('SOLO_UPDATE_SUBTITLE_UPDATEAVAILABLE'); ?>
                </span>
                <span id="soloUpdateUpToDate" style="display: none">
                        <?php echo \Awf\Text\Text::_('SOLO_UPDATE_SUBTITLE_UPTODATE'); ?>
                </span>
            </a>
        <?php endif; ?>
    </div>
</section>
