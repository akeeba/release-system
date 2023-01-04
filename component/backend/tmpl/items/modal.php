<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ARS\Administrator\View\Items\HtmlView $this */

echo $this->loadAnyTemplate('items/default', false, [
	'modal' => true,
]);