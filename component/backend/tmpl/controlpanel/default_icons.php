<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die;

/** @var  \Akeeba\ReleaseSystem\Admin\View\ControlPanel\Html $this */

?>
<div class="card mb-2">
	<h3 class="card-header">
		<?= Text::_('COM_ARS_CPANEL_HEAD_BASIC') ?>
	</h3>

	<div class="card-body">
		<div class="d-flex flex-row flex-wrap align-items-stretch">

			<a class="text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=categories') ?>">
				<div class="bg-primary text-white d-block text-center p-3 h2">
					<span class="fa fa-folder"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_CATEGORIES') ?>
				</span> </a>

			<a class="text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=releases') ?>">
				<div class="bg-primary text-white d-block text-center p-3 h2">
					<span class="fa fa-folder-open"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_RELEASES') ?>
				</span> </a>

			<a class="text-center align-self-stretch btn btn-outline-dark border-0" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=items') ?>">
				<div class="bg-dark text-white d-block text-center p-3 h2">
					<span class="fa fa-list"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_ITEMS') ?>
				</span> </a>

			<a class="text-center align-self-stretch btn btn-outline-warning border-0 text-dark" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=environments') ?>">
				<div class="bg-warning text-dark d-block text-center p-3 h2">
					<span class="fa fa-th-large"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_ENVIRONMENTS') ?>
				</span> </a>

			<a class="text-center align-self-stretch btn btn-outline-danger border-0" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=dlidlabels') ?>">
				<div class="bg-danger text-white d-block text-center p-3 h2">
					<span class="fa fa-key"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_DLIDLABELS') ?>
				</span> </a>

		</div>
	</div>
</div>

<div class="card mb-2">
	<h3 class="card-header">
		<?= Text::_('COM_ARS_CPANEL_HEAD_TOOLS') ?>
	</h3>

	<div class="card-body">
		<div class="d-flex flex-row flex-wrap align-items-stretch">

			<a class="text-center align-self-stretch btn btn-outline-dark border-0" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=autodescriptions') ?>">
				<div class="bg-dark text-white d-block text-center p-3 h2">
					<span class="fa fa-magic"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_AUTODESCRIPTIONS') ?>
				</span> </a>

			<a class="text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=updatestreams') ?>">
				<div class="bg-primary text-white d-block text-center p-3 h2">
					<span class="fa fa-info-circle"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_UPDATESTREAMS') ?>
				</span> </a>

			<a class="text-center align-self-stretch btn btn-outline-primary border-0" style="width: 10em"
			   href="<?= Route::_('index.php?option=com_ars&view=logs') ?>">
				<div class="bg-primary text-white d-block text-center p-3 h2">
					<span class="fa fa-clipboard-list"></span>
				</div>
				<span>
					<?= Text::_('COM_ARS_TITLE_LOGS') ?>
				</span> </a>

		</div>
	</div>
</div>
