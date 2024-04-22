<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/default.blade.php */ ?>
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

<?php /* Display various possible warnings about issues which directly affect the user's experience */ ?>
<?php echo $this->loadAnyTemplate('Main/warnings'); ?>

<?php /* Update notification container */ ?>
<div id="soloUpdateNotification"></div>

<div class="akeeba-container--66-33">
	<div>
        <?php /* Active profile switch */ ?>
        <?php echo $this->loadAnyTemplate('Main/profile'); ?>

        <?php /* One Click Backup icons */ ?>
		<?php if(!empty($this->quickIconProfiles) && $this->canAccess('backup', 'main')): ?>
			<?php echo $this->loadAnyTemplate('Main/oneclick'); ?>
		<?php endif; ?>

        <?php /* Basic operations */ ?>
		<?php echo $this->loadAnyTemplate('Main/icons_basic'); ?>

		<?php echo $this->loadAnyTemplate('Main/paypal'); ?>

        <?php /* Troubleshooting */ ?>
        <?php echo $this->loadAnyTemplate('Main/icons_troubleshooting'); ?>

        <?php /* Advanced operations */ ?>
		<?php echo $this->loadAnyTemplate('Main/icons_advanced'); ?>

        <?php /* Include / Exclude data */ ?>
        <?php if($this->container->userManager->getUser()->getPrivilege('akeeba.configure')): ?>
	        <?php echo $this->loadAnyTemplate('Main/icons_includeexclude'); ?>
        <?php endif; ?>


		<?php if($this->container->userManager->getUser()->getPrivilege('akeeba.configure')): ?>
			<?php echo $this->loadAnyTemplate('Main/icons_system'); ?>
        <?php endif; ?>
	</div>

	<div>
		<?php echo $this->loadAnyTemplate('Main/status'); ?>

		<?php echo $this->loadAnyTemplate('Main/latest_backup'); ?>
	</div>
</div>

<?php
if ($this->statsIframe)
{
    echo $this->statsIframe;
}
?>
