<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Main/oneclick.blade.php */ ?>
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
$token    = $this->container->session->getCsrfToken()->getValue();

?>
<section class="akeeba-panel--primary">
    <header class="akeeba-block-header">
        <h3>
            <span class="akion-ios-play"></span>
			<?php echo \Awf\Text\Text::_('COM_AKEEBA_CPANEL_HEADER_QUICKBACKUP'); ?>
        </h3>
    </header>
    <div class="akeeba-grid">
		<?php foreach($this->quickIconProfiles as $qiProfile): ?>
            <form action="<?php echo $router->route('index.php?view=backup'); ?>" method="post">
                <a class="oneclick akeeba-action--green" href="#">
                    <span class="akion-play"></span>
                    <span><?php echo $qiProfile->description; ?> </span>
                </a>

                <input type="hidden" name="autostart" value="1" />
                <input type="hidden" name="profile" value="<?php echo (int) $qiProfile->id; ?>" />
                <input type="hidden" name="<?php echo $token; ?>" value="1" />
            </form>
		<?php endforeach; ?>
    </div>
</section>
