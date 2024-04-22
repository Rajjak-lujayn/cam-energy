<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/warnings.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Html;
use Awf\Text\Text;

defined('_AKEEBA') or die();

// Used for type hinting
/** @var \Solo\View\Main\Html $this */

$router   = $this->container->router;
$inCMS    = $this->container->segment->get('insideCMS', false);
$token    = $this->container->session->getCsrfToken()->getValue();
?>

<?php /* Configuration Wizard pop-up */ ?>
<?php if($this->promptForConfigurationWizard): ?>
    <?php echo $this->loadAnyTemplate('Configuration/confwiz_modal'); ?>
<?php endif; ?>

<?php /* AdBlock warning */ ?>
<?php echo $this->loadAnyTemplate('Main/warning_adblock'); ?>

<?php /* Stuck database updates warning */ ?>
<?php if($this->stuckUpdates): ?>
	<?php $resetUrl = $router->route('index.php?view=Main&task=forceUpdateDb');	?>
    <div class="akeeba-block--failure">
        <p>
			<?php
			echo Text::sprintf('COM_AKEEBA_CPANEL_ERR_UPDATE_STUCK',
				$this->getContainer()->appConfig->get('prefix', 'solo_'),
				$resetUrl
			) ?>
        </p>
    </div>
<?php endif; ?>

<?php /* Potentially web accessible output directory */ ?>
<?php if($this->isOutputDirectoryUnderSiteRoot): ?>
    <!--
    Oh, hi there! It looks like you got curious and are peeking around your browser's developer tools – or just the
    source code of the page that loaded on your browser. Cool! May I explain what we are seeing here?

    Just to let you know, the next three DIVs (outDirSystem, insecureOutputDirectory and missingRandomFromFilename) are
    HIDDEN and their existence doesn't mean that your site has an insurmountable security issue. To the contrary.
    Whenever Akeeba Backup detects that the backup output directory is under your site's root it will CHECK its security
    i.e. if it's really accessible over the web. This check is performed with an AJAX call to your browser so if it
    takes forever or gets stuck you won't see a frustrating blank page in your browser. If AND ONLY IF a problem is
    detected said JavaScript will display one of the following DIVs, depending on what is applicable.

    So, to recap. These hidden DIVs? They don't indicate a problem with your site. If one becomes visible then – and
    ONLY then – should you do something about it, as instructed. But thank you for being curious. Curiosity is how you
    get involved with and better at web development. Stay curious!
    -->
    <?php /* Web accessible output directory that coincides with or is inside in a CMS system folder */ ?>
    <div class="akeeba-block--failure" id="outDirSystem" style="display: none">
        <h3><?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_HEAD_OUTDIR_INVALID'); ?></h3>
        <p>
            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_CPANEL_LBL_OUTDIR_LISTABLE', realpath($this->getModel()->getOutputDirectory())); ?>
        </p>
        <p>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_ISSYSTEM'); ?>
        </p>
        <p>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_ISSYSTEM_FIX'); ?>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_DELETEORBEHACKED'); ?>
        </p>
    </div>

    <?php /* Output directory can be listed over the web */ ?>
    <div class="akeeba-block--<?php echo $this->hasOutputDirectorySecurityFiles ? 'failure' : 'warning'; ?>" id="insecureOutputDirectory" style="display: none">
        <h3>
            <?php if($this->hasOutputDirectorySecurityFiles): ?>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_HEAD_OUTDIR_UNFIXABLE'); ?>
            <?php else: ?>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_HEAD_OUTDIR_INSECURE'); ?>
            <?php endif; ?>
        </h3>
        <p>
            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_CPANEL_LBL_OUTDIR_LISTABLE', realpath($this->getModel()->getOutputDirectory())); ?>
        </p>
        <?php if(!$this->hasOutputDirectorySecurityFiles): ?>
            <p>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_CLICKTHEBUTTON'); ?>
            </p>
            <p>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_FIX_SECURITYFILES'); ?>
            </p>

            <form action="<?php echo $this->container->router->route('index.php?view=Main&task=fixOutputDirectory'); ?>" method="POST" class="akeeba-form--inline">
                <input type="hidden" name="<?php echo $this->container->session->getCsrfToken()->getValue(); ?>" value="1">

                <button type="submit" class="akeeba-btn--block--green">
                    <span class="akion-hammer"></span>
                    <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_BTN_FIXSECURITY'); ?>
                </button>
            </form>
        <?php else: ?>
            <p>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_TRASHHOST'); ?>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_DELETEORBEHACKED'); ?>
            </p>
        <?php endif; ?>
    </div>

    <?php /* Output directory cannot be listed over the web but I can download files */ ?>
    <div class="akeeba-block--warning" id="missingRandomFromFilename" style="display: none">
        <h3>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_HEAD_OUTDIR_INSECURE_ALT'); ?>
        </h3>
        <p>
            <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_CPANEL_LBL_OUTDIR_FILEREADABLE', realpath($this->getModel()->getOutputDirectory())); ?>
        </p>
        <p>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_CLICKTHEBUTTON'); ?>
        </p>
        <p>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_LBL_OUTDIR_FIX_RANDOM'); ?>
        </p>

        <form action="<?php echo $this->container->router->route('index.php?view=Main&task=addRandomToFilename'); ?>" method="POST" class="akeeba-form--inline">
            <input type="hidden" name="<?php echo $this->container->session->getCsrfToken()->getValue(); ?>" value="1">

            <button type="submit" class="akeeba-btn--block--green">
                <span class="akion-hammer"></span>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_BTN_FIXSECURITY'); ?>
            </button>
        </form>
    </div>
<?php endif; ?>

<?php /* mbstring warning */ ?>
<?php if ( ! ($this->checkMbstring)): ?>
    <div class="akeeba-block--failure">
		<?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_CPANEL_ERR_MBSTRING_' . ($inCMS ? 'WORDPRESS' : 'SOLO'), PHP_VERSION); ?>
    </div>
<?php endif; ?>

<?php /* Front-end backup secret word reminder */ ?>
<?php if ( ! (empty($this->frontEndSecretWordIssue))): ?>
    <div class="akeeba-block--warning">
        <h3><?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_ERR_FESECRETWORD_HEADER'); ?></h3>
        <p><?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_ERR_FESECRETWORD_INTRO'); ?></p>
        <p><?php echo $this->frontEndSecretWordIssue; ?></p>
        <p>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_ERR_FESECRETWORD_WHATTODO_SOLO'); ?>
			<?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_CPANEL_ERR_FESECRETWORD_WHATTODO_COMMON', $this->newSecretWord); ?>
        </p>
        <p>
            <a class="akeeba-btn--green--large"
               href="<?php echo $this->container->router->route('index.php?view=Main&task=resetSecretWord&' . $token . '=1'); ?>">
                <span class="akion-android-refresh"></span>
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_BTN_FESECRETWORD_RESET'); ?>
            </a>
        </p>
    </div>
<?php endif; ?>

<?php /* You need to enter your Download ID */ ?>
<?php if($this->needsDownloadId): ?>
    <div class="akeeba-block--success">
        <h3>
            <?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_MSG_MUSTENTERDLID'); ?>
        </h3>
        <p>
			<?php if($inCMS): ?>
				<?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_LBL_CPANEL_NEEDSDLID', 'https://www.akeeba.com/instructions/1557-akeeba-solo-download-id-2.html'); ?>
			<?php else: ?>
				<?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_LBL_CPANEL_NEEDSDLID', 'https://www.akeeba.com/instructions/1539-akeeba-solo-download-id.html'); ?>
			<?php endif; ?>
        </p>
        <form name="dlidform" action="<?php echo $this->container->router->route('index.php?view=main'); ?>" method="post"
              class="akeeba-form--inline">
            <input type="hidden" name="task" value="applyDownloadId"/>
            <input type="hidden" name="token"
                   value="<?php echo $this->container->session->getCsrfToken()->getValue(); ?>">

            <div class="akeeba-form-group">
                <label for="dlid">
					<?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_MSG_PASTEDLID'); ?>
                </label>
                <input type="text" id="dlid" name="dlid"
                       placeholder="<?php echo \Awf\Text\Text::_('COM_AKEEBA_CONFIG_DOWNLOADID_LABEL'); ?>" class="form-control">
            </div>
            <div class="akeeba-form-group--actions">
                <button type="submit" class="akeeba-btn--green">
                    <span class="akion-checkmark"></span>
					<?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_MSG_APPLYDLID'); ?>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php /* You have CORE; you need to upgrade, not just enter a Download ID */ ?>
<?php if($this->warnCoreDownloadId): ?>
    <div class="akeeba-block--failure">
		<?php echo \Awf\Text\Text::_('SOLO_MAIN_LBL_NEEDSUPGRADE'); ?>
    </div>
<?php endif; ?>

<div class="akeeba-block--failure" style="display: none;" id="cloudFlareWarn">
    <h3><?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_MSG_CLOUDFLARE_WARN'); ?></h3>
    <p><?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_CPANEL_MSG_CLOUDFLARE_WARN1', 'https://support.cloudflare.com/hc/en-us/articles/200169456-Why-is-JavaScript-or-jQuery-not-working-on-my-site-'); ?></p>
</div>
