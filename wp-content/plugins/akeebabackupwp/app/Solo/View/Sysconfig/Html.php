<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Solo\View\Sysconfig;

use Awf\Mvc\Model;
use Awf\Mvc\View;
use Awf\Text\Text;
use Awf\Utils\Template;
use Solo\Model\Main;

class Html extends View
{
	public $profileList;

	public function onBeforeMain()
	{
		/** @var Main $mainModel */
		$mainModel         = Model::getTmpInstance($this->container->application_name, 'Main', $this->container);
		$this->profileList = $mainModel->getProfileList();

		$document = $this->container->application->getDocument();

		$buttons = [
			[
				'title'   => 'SOLO_BTN_SAVECLOSE',
				'class'   => 'akeeba-btn--green',
				'onClick' => 'akeeba.System.submitForm(\'save\')',
				'icon'    => 'akion-checkmark-circled',
			],
			[
				'title'   => 'SOLO_BTN_SAVE',
				'class'   => 'akeeba-btn--grey',
				'onClick' => 'akeeba.System.submitForm(\'apply\')',
				'icon'    => 'akion-checkmark',
			],
			[
				'title' => 'SOLO_BTN_PHPINFO',
				'class' => 'akeeba-btn--dark',
				'url'   => $this->container->router->route('index.php?view=phpinfo'),
				'icon'  => 'akion-information-circled',
			],
			[
				'title' => 'SOLO_BTN_CANCEL',
				'class' => 'akeeba-btn--orange',
				'url'   => $this->container->router->route('index.php'),
				'icon'  => 'akion-close-circled',
			],
		];

		$toolbar = $document->getToolbar();

		foreach ($buttons as $button)
		{
			$toolbar->addButtonFromDefinition($button);
		}

		// Load Javascript
		//Template::addJs('media://js/solo/setup.js', $this->container->application);

		$js = <<< JS
akeeba.System.documentReady(function() {
    akeeba.System.addEventListener('comAkeebaSysconfigTestEmail', 'click', function() {
      akeeba.System.submitForm('testemail');
      
      return false;
    });
});

JS;


		$this->getContainer()->application->getDocument()->addScriptDeclaration($js);

		// JavaScript language strings
		Text::script('SOLO_COMMON_LBL_ROOT');
		Text::script('COM_AKEEBA_CONFIG_DIRECTFTP_TEST_OK');
		Text::script('COM_AKEEBA_CONFIG_DIRECTFTP_TEST_FAIL');
		Text::script('COM_AKEEBA_CONFIG_DIRECTSFTP_TEST_OK');
		Text::script('COM_AKEEBA_CONFIG_DIRECTSFTP_TEST_FAIL');

		return true;
	}
}
