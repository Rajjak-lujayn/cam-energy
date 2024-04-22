<?php /* /home/camenerg/public_html/wp-content/plugins/akeebabackupwp/app/Solo/ViewTemplates/Manage/default.blade.php */ ?>
<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Text\Text;

defined('_AKEEBA') or die();

// Used for type hinting
/** @var  Solo\View\Manage\Html $this */

$router = $this->container->router;
$token = $this->container->session->getCsrfToken()->getValue();
$proKey = (defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO) ? 'PRO' : 'CORE';
?>

<?php if($this->promptForBackupRestoration): ?>
    <?php echo $this->loadAnyTemplate('Manage/howtorestore_modal'); ?>
<?php endif; ?>

<div class="akeeba-block--info">
    <h4><?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_HOWDOIRESTORE_LEGEND'); ?></h4>

    <p>
        <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LABEL_HOWDOIRESTORE_TEXT_' . $proKey,
            'https://www.akeeba.com/videos/1214-akeeba-solo/1637-abts05-restoring-site-new-server.html',
            $router->route('index.php?view=Transfer'),
            'https://www.akeeba.com/latest-kickstart-core.zip'
        ); ?>
    </p>
    <?php if(!AKEEBABACKUP_PRO): ?>
        <p>
            <?php if($this->getContainer()->segment->get('insideCMS', false)): ?>
                <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LABEL_HOWDOIRESTORE_TEXT_CORE_INFO_ABOUT_PRO',
                'https://www.akeeba.com/products/akeeba-backup-wordpress.html'); ?>
            <?php else: ?>
                <?php echo \Awf\Text\Text::sprintf('COM_AKEEBA_BUADMIN_LABEL_HOWDOIRESTORE_TEXT_CORE_INFO_ABOUT_PRO',
                'https://www.akeeba.com/products/akeeba-solo.html'); ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<form action="<?php echo $this->container->router->route('index.php?view=manage'); ?>" method="post" name="adminForm" id="adminForm"
      role="form" class="akeeba-form">

    <table class="akeeba-table--striped" id="itemsList">
        <thead>
        <tr>
            <th width="20">
                <input type="checkbox" name="toggle" value="" onclick="akeeba.System.checkAll(this);" />
            </th>
            <th width="20" class="akeeba-hidden-phone">
                <?php echo \Awf\Html\Html::_('grid.sort', 'COM_AKEEBA_BUADMIN_LABEL_ID', 'id', $this->lists->order_Dir, $this->lists->order,
                'default'); ?>
            </th>
            <th width="80" class="akeeba-hidden-phone">
                <?php echo \Awf\Html\Html::_('grid.sort', 'COM_AKEEBA_BUADMIN_LABEL_FROZEN', 'frozen', $this->lists->order_Dir, $this->lists->order,
                'default'); ?>
            </th>
            <th width="25%">
                <?php echo \Awf\Html\Html::_('grid.sort', 'COM_AKEEBA_BUADMIN_LABEL_DESCRIPTION', 'description', $this->lists->order_Dir,
                $this->lists->order, 'default'); ?>
            </th>
            <th width="25%" class="akeeba-hidden-phone">
                <?php echo \Awf\Html\Html::_('grid.sort', 'COM_AKEEBA_BUADMIN_LABEL_PROFILEID', 'profile_id', $this->lists->order_Dir,
                $this->lists->order, 'default'); ?>
            </th>
            <th width="80">
                <?php echo \Awf\Html\Html::_('grid.sort', 'COM_AKEEBA_BUADMIN_LABEL_DURATION', 'backupstart', $this->lists->order_Dir,
                $this->lists->order, 'default'); ?>
            </th>
            <th width="80">
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_STATUS'); ?>
            </th>
            <th width="110" class="akeeba-hidden-phone">
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_SIZE'); ?>
            </th>
            <th class="akeeba-hidden-phone">
                <?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_MANAGEANDDL'); ?>
            </th>
        </tr>
        <tr>
            <td></td>
            <td class="akeeba-hidden-phone"></td>
            <td>
                <?php echo \Awf\Html\Html::_('select.genericlist', $this->frozenList, 'filter_frozen', ['list.attr' => ['class' => 'akeebaGridViewAutoSubmitOnChange'], 'list.select' => $this->lists->fltFrozen]); ?>
            </td>
            <td>
                <input type="text" name="filter_description" id="description"
                       class="akeebaGridViewAutoSubmitOnChange" style="width: 100%;"
                       value="<?php echo $this->lists->fltDescription; ?>"
                       placeholder="<?php echo \Awf\Text\Text::_('SOLO_MANAGE_FIELD_DESCRIPTION'); ?>">
            </td>
            <td class="akeeba-hidden-phone">
                <?php echo \Awf\Html\Html::_('select.genericlist', $this->profilesList, 'filter_profile', ['list.attr' => ['class' => 'akeebaGridViewAutoSubmitOnChange', 'style' => 'max-width: 12vw'], 'list.select' => $this->lists->fltProfile]); ?>
            </td>
            <td></td>
            <td></td>
            <td colspan="2" class="akeeba-hidden-phone"></td>
        </tr>
        </thead>
        <tfoot>
        <tr>
            <td colspan="11" class="center">
                <?php echo $this->pagination->getListFooter(); ?>

            </td>
        </tr>
        </tfoot>
        <tbody>
        <?php if(empty($this->items)): ?>
            <tr>
                <td colspan="11">
                    <?php echo \Awf\Text\Text::_('SOLO_LBL_NO_RECORDS'); ?>
                </td>
            </tr>
        <?php endif; ?>

        <?php if(!empty($this->items)): ?>
			<?php $i = 0; ?>
            <?php foreach($this->items as $record): ?>
				<?php
				list($originDescription, $originIcon) = $this->getOriginInformation($record);
				list($startTime, $duration, $timeZoneText) = $this->getTimeInformation($record);
				list($statusClass, $statusIcon) = $this->getStatusInformation($record);
				$profileName = $this->getProfileName($record);

				$frozenIcon  = 'akion-waterdrop';
				$frozenTask  = 'freeze';
				$frozenTitle = Text::_('COM_AKEEBA_BUADMIN_LABEL_ACTION_FREEZE');

				if ($record['frozen'])
				{
					$frozenIcon  = 'akion-ios-snowy';
					$frozenTask  = 'unfreeze';
					$frozenTitle = Text::_('COM_AKEEBA_BUADMIN_LABEL_ACTION_UNFREEZE');
				}

				?>
                <tr>
                    <td><?php echo \Awf\Html\Html::_('grid.id', ++$i, $record['id']); ?></td>
                    <td class="akeeba-hidden-phone">
                        <?php echo $this->escape($record['id']); ?>

                    </td>

                    <td style="text-align: center">
                        <a href="<?php echo $this->container->router->route('index.php?view=Manage&id=' . $record['id'] . '&task=' . $frozenTask . '&token=' . $token); ?>" title="<?php echo $frozenTitle; ?>">
                            <span class="<?php echo $frozenIcon; ?>"></span>
                        </a>
                    </td>

                    <td>
						<span class="<?php echo $originIcon; ?> akeebaCommentPopover" rel="popover"
                              title="<?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_ORIGIN'); ?>"
                              data-content="<?php echo $this->escape($originDescription); ?>"></span>
                        <?php if( ! (empty($record['comment']))): ?>
                            <span class="akion-help-circled akeebaCommentPopover" rel="popover"
                                  data-content="<?php echo $this->escape($record['comment']); ?>"></span>
                        <?php endif; ?>
                        <a href="<?php echo $this->container->router->route('index.php?view=manage&task=showComment&id=' . $record['id'] . '&token=' . $token); ?>">
                            <?php echo $this->escape(empty($record['description']) ? Text::_('COM_AKEEBA_BUADMIN_LABEL_NODESCRIPTION') : $record['description']); ?>


                        </a>
                        <br />
                        <div class="akeeba-buadmin-startdate" title="<?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_START'); ?>">
                            <small>
                                <span class="akion-calendar"></span>
                                <?php echo $this->escape($startTime); ?> <?php echo $this->escape($timeZoneText); ?>

                            </small>
                        </div>
                    </td>
                    <td class="akeeba-hidden-phone">
                        #<?php echo $this->escape((int)$record['profile_id']); ?>. <?php echo $this->escape($profileName); ?>

                        <br />
                        <small>
                            <em><?php echo $this->escape($this->translateBackupType($record['type'])); ?></em>
                        </small>
                    </td>
                    <td>
                        <?php echo $this->escape($duration); ?>

                    </td>
                    <td>
                        <span class="<?php echo $statusClass; ?> akeebaCommentPopover" rel="popover"
                              data-original-title="<?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_STATUS'); ?>"
                              data-content="<?php echo \Awf\Text\Text::_('COM_AKEEBA_BUADMIN_LABEL_STATUS_' . $record['meta']); ?>"
                              style="padding: 0.4em 0.6em;"
                        >
                            <span class="<?php echo $statusIcon; ?>"></span>
                        </span>
                    </td>
                    <td class="akeeba-hidden-phone">
                        <?php if($record['meta'] == 'ok'): ?>
                            <?php echo $this->escape(\Solo\Helper\Format::fileSize($record['size'])); ?>


                        <?php elseif($record['total_size'] > 0): ?>
                            <i><?php echo \Solo\Helper\Format::fileSize($record['total_size']); ?></i>
                            <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                    <td class="akeeba-hidden-phone">
                        <?php echo $this->loadAnyTemplate('Manage/manage_column', ['record' => &$record]); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="akeeba-hidden-fields-container">
        <input type="hidden" name="boxchecked" id="boxchecked" value="0">
        <input type="hidden" name="task" id="task" value="default">
        <input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order; ?>">
        <input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir; ?>">
        <input type="hidden" name="token" value="<?php echo $this->container->session->getCsrfToken()->getValue(); ?>">
    </div>
</form>
