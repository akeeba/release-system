<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Component\ARS\Administrator\Controller\DlidlabelController as AdminDlidlabelController;
use Akeeba\Component\ARS\Administrator\Table\DlidlabelTable;
use Joomla\CMS\Language\Text;

class DlidlabelController extends AdminDlidlabelController
{
	public function onBeforeMain()
	{
		$user = $this->app->getIdentity();
		$id   = $this->input->getInt('id', 0);

		if ($user->guest)
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if (!$id)
		{
			return;
		}

		/** @var DlidlabelTable $dlidTable */
		$dlidTable = $this->getModel()->getTable();

		if (!$dlidTable->load($id))
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		if ($dlidTable->user_id != $user->id)
		{
			throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	protected function allowAdd($data = [])
	{
		if (parent::allowAdd($data))
		{
			return true;
		}

		if (empty($data))
		{
			return true;
		}

		if (($data['id'] ?? 0) !== 0)
		{
			return false;
		}

		$user_id = $data['user_id'] ?? null;

		if (is_null($user_id))
		{
			return true;
		}

		$user = $this->app->getIdentity();

		return ($user->guest != 1) && ($user_id == $user->id);
	}


}