<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/icons_system.blade.php */ ?>
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

?>
<section class="akeeba-panel--info">
	<header class="akeeba-block-header">
		<h3>
			<span class="akion-ios-cog"></span>
			<?php echo \Awf\Text\Text::_('SOLO_MAIN_LBL_SYSMANAGEMENT'); ?>
		</h3>
	</header>
	<div class="akeeba-grid">
		<?php if(!$inCMS): ?>
			<?php if($this->canAccess('users', 'main')): ?>
				<a class="akeeba-action--teal" href="<?php echo $this->container->router->route('index.php?view=users'); ?>">
					<span class="akion-ios-people"></span>
					<?php echo \Awf\Text\Text::_('SOLO_MAIN_LBL_USERS'); ?>
				</a>
			<?php endif; ?>
		<?php endif; ?>
		<?php if($this->canAccess('sysconfig', 'main')): ?>
			<a class="akeeba-action--teal" href="<?php echo $this->container->router->route('index.php?view=sysconfig'); ?>">
				<span class="akion-ios-settings-strong"></span>
				<?php echo \Awf\Text\Text::_('SOLO_MAIN_LBL_SYSCONFIG'); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
