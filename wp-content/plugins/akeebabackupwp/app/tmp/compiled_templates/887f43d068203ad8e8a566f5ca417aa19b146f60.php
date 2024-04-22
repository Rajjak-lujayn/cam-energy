<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/icons_includeexclude.blade.php */ ?>
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

?>
<section class="akeeba-panel--info">
	<header class="akeeba-block-header">
		<h3>
			<span class="akion-funnel"></span>
			<?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_HEADER_INCLUDEEXCLUDE'); ?>
		</h3>
	</header>

	<div class="akeeba-grid">
		<?php if(defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO): ?>
			<?php if($this->canAccess('multidb', 'main')): ?>
				<a class="akeeba-action--green" href="<?php echo $this->container->router->route('index.php?view=multidb'); ?>">
					<span class="akion-arrow-swap"></span>
					<?php echo \Awf\Text\Text::_('COM_AKEEBA_MULTIDB'); ?>
				</a>
			<?php endif; ?>
			<?php if($this->canAccess('extradirs', 'main')): ?>
				<a class="akeeba-action--green" href="<?php echo $this->container->router->route('index.php?view=extradirs'); ?>">
					<span class="akion-folder"></span>
					<?php echo \Awf\Text\Text::_('COM_AKEEBA_INCLUDEFOLDER'); ?>
				</a>
			<?php endif; ?>
		<?php endif; ?>

		<?php if($this->canAccess('fsfilters', 'main')): ?>
			<a class="akeeba-action--red" href="<?php echo $this->container->router->route('index.php?view=fsfilters'); ?>">
				<span class="akion-filing"></span>
				<?php echo \Awf\Text\Text::_('COM_AKEEBA_FILEFILTERS'); ?>
			</a>
		<?php endif; ?>
		<?php if($this->canAccess('dbfilters', 'main')): ?>
			<a class="akeeba-action--red" href="<?php echo $this->container->router->route('index.php?view=dbfilters'); ?>">
				<span class="akion-ios-grid-view"></span>
				<?php echo \Awf\Text\Text::_('COM_AKEEBA_DBFILTER'); ?>
			</a>
		<?php endif; ?>
		<?php if(defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO): ?>
			<?php if($this->canAccess('regexfsfilters', 'main')): ?>
				<a class="akeeba-action--red" href="<?php echo $this->container->router->route('index.php?view=regexfsfilters'); ?>">
					<span class="akion-ios-folder"></span>
					<?php echo \Awf\Text\Text::_('COM_AKEEBA_REGEXFSFILTERS'); ?>
				</a>
			<?php endif; ?>
			<?php if($this->canAccess('regexdbfilters', 'main')): ?>
				<a class="akeeba-action--red" href="<?php echo $this->container->router->route('index.php?view=regexdbfilters'); ?>">
					<span class="akion-ios-box"></span>
					<?php echo \Awf\Text\Text::_('COM_AKEEBA_REGEXDBFILTERS'); ?>
				</a>
			<?php endif; ?>
		<?php endif; ?>

	</div>
</section>
