<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/icons_advanced.blade.php */ ?>
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
<?php if(AKEEBABACKUP_PRO): ?>
    <section class="akeeba-panel--info">
        <header class="akeeba-block-header">
            <h3>
                <span class="akion-wand"></span>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_HEADER_ADVANCED'); ?>
            </h3>
        </header>

        <div class="akeeba-grid">
            <?php if($this->canAccess('schedule', 'main')): ?>
                <a class="akeeba-action--teal" href="<?php echo $this->container->router->route('index.php?view=schedule'); ?>">
                    <span class="akion-calendar"></span>
                    <?php echo \Awf\Text\Text::_('COM_AKEEBA_SCHEDULE'); ?>
                </a>
            <?php endif; ?>
            <?php if($this->canAccess('discover', 'main')): ?>
                <a class="akeeba-action--orange" href="<?php echo $this->container->router->route('index.php?view=discover'); ?>">
                    <span class="akion-ios-download"></span>
                    <?php echo \Awf\Text\Text::_('COM_AKEEBA_DISCOVER'); ?>
                </a>
            <?php endif; ?>
            <?php if($this->canAccess('s3import', 'main')): ?>
                <a class="akeeba-action--orange" href="<?php echo $this->container->router->route('index.php?view=s3import'); ?>">
                    <span class="akion-ios-cloud-download"></span>
                    <?php echo \Awf\Text\Text::_('COM_AKEEBA_S3IMPORT'); ?>
                </a>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>