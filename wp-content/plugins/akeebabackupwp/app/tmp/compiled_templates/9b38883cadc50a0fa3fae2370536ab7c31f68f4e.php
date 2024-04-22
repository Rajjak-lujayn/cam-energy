<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/status.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\Engine\Factory;
use Awf\Text\Text;

defined('_AKEEBA') or die();

// Used for type hinting
/** @var \Solo\View\Main\Html $this */

$router = $this->container->router;

?>
<div class="akeeba-panel">
    <header class="akeeba-block-header">
        <h3><?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LABEL_STATUSSUMMARY'); ?></h3>
    </header>
    <div>
        <?php /* Backup status summary */ ?>
        <?php echo $this->statusCell; ?>


        <?php /* Warnings */ ?>
        <?php if($this->countWarnings): ?>
            <div>
                <?php echo $this->detailsCell; ?>

            </div>
            <hr/>
        <?php endif; ?>

        <?php /* Version */ ?>
        <p class="ak_version">
            <?php echo \Awf\Text\Text::_('SOLO_APP_TITLE'); ?> <?php echo AKEEBABACKUP_PRO ? 'Professional ' : 'Core'; ?> <?php echo AKEEBABACKUP_VERSION; ?> (<?php echo AKEEBABACKUP_DATE; ?>)
        </p>

        <?php /* Changelog */ ?>
        <a href="#" id="btnchangelog" class="akeeba-btn--primary">CHANGELOG</a>

        <div id="akeeba-changelog" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
            <div class="akeeba-renderer-fef">
                <div class="akeeba-panel--info">
                    <header class="akeeba-block-header">
                        <h3>
                            <?php echo \Awf\Text\Text::_('CHANGELOG'); ?>
                        </h3>
                    </header>
                    <div id="DialogBody">
                        <?php echo $this->formattedChangelog; ?>

                    </div>
                </div>
            </div>
        </div>

        <?php /* Donation CTA */ ?>
        <?php if( ! (AKEEBABACKUP_PRO)): ?>
            <a
                    href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=3NTKQ3M2DYPYW&source=url"
                    class="akeeba-btn-green">
                Donate via PayPal
            </a>
        <?php endif; ?>

        <?php /* Pro upsell */ ?>
        <?php if(!AKEEBABACKUP_PRO && (time() - $this->lastUpsellDismiss < 1296000)): ?>
            <p style="margin-top: 0.5em">
                <?php if(!$this->getContainer()->segment->get('insideCMS', false)): ?>
                    <a href="https://www.akeeba.com/landing/akeeba-solo.html" class="akeeba-btn--ghost--small">
                        <span class="akion-ios-star"></span>
                        <?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_BTN_LEARNMORE'); ?>
                    </a>
                <?php else: ?>
                    <a href="https://www.akeeba.com/landing/akeeba-backup-wordpress.html" class="akeeba-btn--ghost--small">
                        <span class="akion-ios-star"></span>
                        <?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_BTN_LEARNMORE'); ?>
                    </a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
