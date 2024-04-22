<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Awf\Text\Text;
use Solo\Helper\Escape;
use Solo\Helper\FEFSelect;

defined('_AKEEBA') or die();

/** @var \Solo\View\Sysconfig\Html $this */

$config = $this->getContainer()->appConfig;
$inCMS  = $this->getContainer()->segment->get('insideCMS', false);

?>
<div id="sysconfigUpdate" class="tab-pane">
	@if (defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO)
		<div class="akeeba-form-group">
			<label for="update_dlid">
				@lang('COM_AKEEBA_CONFIG_DOWNLOADID_LABEL')
			</label>
			<input type="text" name="options[update_dlid]" id="update_dlid" placeholder="@lang('COM_AKEEBA_CONFIG_DOWNLOADID_LABEL')" value="<?php echo $config->get('options.update_dlid')?>">
			<p class="akeeba-help-text">
				@lang('COM_AKEEBA_CONFIG_DOWNLOADID_DESC')
			</p>
		</div>
	@endif

	<div class="akeeba-form-group">
		<label for="minstability">
			@lang('SOLO_CONFIG_MINSTABILITY_LABEL')
		</label>
		{{ \Solo\Helper\Setup::minstabilitySelect($config->get('options.minstability', 'stable')) }}
		<p class="akeeba-help-text">
			@lang('SOLO_CONFIG_MINSTABILITY_DESC')
		</p>
	</div>

	@if ($inCMS)
        <div class="akeeba-form-group">
            <label for="options_integratedupdate">
				@lang('SOLO_CONFIG_UPDATE_INTEGRATED_WP')
            </label>
            <div class="akeeba-toggle">
				{{ FEFSelect::booleanList('options[integratedupdate]', ['id' => 'options_integratedupdate', 'forToggle' => 1, 'colorBoolean' => 1], $config->get('options.integratedupdate', 1)) }}            </div>
            <p class="akeeba-help-text">
				@lang('SOLO_CONFIG_UPDATE_INTEGRATED_WP_DESC')
            </p>
        </div>
	@endif
</div>
