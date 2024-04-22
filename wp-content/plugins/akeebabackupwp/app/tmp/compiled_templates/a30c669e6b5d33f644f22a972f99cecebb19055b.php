<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/paypal.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_AKEEBA') or die();

// Used for type hinting
/** @var \Solo\View\Main\Html $this */


// Only show in the Core version with a 10% probability
if (AKEEBABACKUP_PRO) return;

// Only show if it's at least 15 days since the last time the user dismissed the upsell
if (time() - $this->lastUpsellDismiss < 1296000) return;

?>
<div class="akeeba-panel--orange">
    <header class="akeeba-block-header">
        <h3>
            <span class="akion-ios-star"></span>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_HEAD_PROUPSELL'); ?>
        </h3>
    </header>

    <p><?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_HEAD_LBL_PROUPSELL_1'); ?></p>

    <p class="akeeba-block--info"><?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_CONTROLPANEL_HEAD_LBL_DISCOUNT',
        base64_decode('SVdBTlRJVEFMTA==')); ?></p>

    <p><?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_HEAD_LBL_PROUPSELL_2'); ?></p>

    <p>
        <?php if(!$this->getContainer()->segment->get('insideCMS', false)): ?>
            <a href="https://www.akeeba.com/landing/akeeba-solo.html" class="akeeba-btn--large--primary">
                <span class="aklogo-solo"></span>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_BTN_LEARNMORE'); ?>
            </a>
        <?php else: ?>
            <a href="https://www.akeeba.com/landing/akeeba-backup-wordpress.html"
               class="akeeba-btn--large--primary">
                <span class="aklogo-backup-wp"></span>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_BTN_LEARNMORE'); ?>
            </a>
        <?php endif; ?>

        <a href="<?php echo $this->container->router->route('index.php?view=Main&task=dismissUpsell'); ?>" class="akeeba-btn--ghost--small">
            <span class="akion-ios-alarm"></span>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CONTROLPANEL_BTN_HIDE'); ?>
        </a>
    </p>
</div>