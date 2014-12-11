<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewUpdate extends F0FViewHtml
{
	function onDisplay($tpl = null)
	{
		$this->loadHelper('router');

		$task = $this->getModel()->getState('task', 'all');
		$document = JFactory::getDocument();
		$document->setMimeEncoding('text/xml');

		switch ($task)
		{
			default:
			case 'all':
				$component = JComponentHelper::getComponent('com_ars');
				$params = ($component->params instanceof JRegistry) ? $component->params : new JRegistry($component->params);
				$this->updates_name = $params->get('updates_name', '');
				$this->updates_desc = $params->get('updates_desc', '');
				$this->setLayout('all');
				break;

			case 'category':
				$category = $this->input->getCmd('id', '');
				$model = $this->getModel();
				$items = $model->items;
				$this->category = $category;
				$this->items = $items;
				$this->setLayout('category');
				break;

			case 'stream':
				$model = $this->getModel();
				$items = $model->items;

				$envmodel = F0FModel::getTmpInstance('Environments', 'ArsModel');
				$rawenvs = $envmodel->getItemList(true);
				$envs = array();

				if (!empty($rawenvs))
				{
					foreach ($rawenvs as $env)
					{
						$envs[$env->id] = $env;
					}
				}

				$this->items = $items;
				$this->envs = $envs;
				$this->setLayout('stream');
				break;

			case 'jed':
				$model = $this->getModel();
				$items = $model->items;

				$envmodel = F0FModel::getTmpInstance('Environments', 'ArsModel');
				$rawenvs = $envmodel->getItemList(true);
				$envs = array();

				if (!empty($rawenvs))
				{
					foreach ($rawenvs as $env)
					{
						$envs[$env->id] = $env;
					}
				}

				$this->items = $items;
				$this->envs = $envs;
				$this->setLayout('jed');
				break;
		}

		return true;
	}
}