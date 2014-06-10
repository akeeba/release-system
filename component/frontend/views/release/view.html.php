<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsViewRelease extends F0FViewHtml
{
	public function onAdd($tpl = null)
	{
		return $this->onRead();
	}

	public function onEdit($tpl = null)
	{
		return $this->onRead();
	}

	function onRead($tpl = null)
	{
		// Load helpers
		$this->loadHelper('breadcrumbs');
		$this->loadHelper('html');
		$this->loadHelper('router');
		$this->loadHelper('title');

		// Load the model
		$model = $this->getModel();

		// Get component parameters
		$app = JFactory::getApplication();
		$pparams = $app->getPageParameters('com_ars');

		// Set page title and meta
		$cat = F0FModel::getTmpInstance('Categories', 'ArsModel')
			->setId($model->item->category_id)
			->getItem();

		$title = ArsHelperTitle::setTitleAndMeta($pparams, $cat->title.' '.$model->item->version);

		// Add a breadcrumb if necessary
		$catModel = F0FModel::getTmpInstance('Categories','ArsModel');
		$category = $catModel->getItem($model->item->category_id);

		$repoType = $category->type;

		ArsHelperBreadcrumbs::addRepositoryRoot($repoType);
		ArsHelperBreadcrumbs::addCategory($category->id, $category->title);
		ArsHelperBreadcrumbs::addRelease($model->item->id, $model->item->version);


		// Pass on a user and a Download ID
		$user = JFactory::getUser();
		$dlid = $user->guest ? '' : md5($user->id . $user->username . $user->password);
		$directlink = $pparams->get('show_directlink', 1) && !$user->guest;

		// Pass on Direct Link-related stuff
		if($directlink)
		{
			$directlink_extensions = explode(',',$pparams->get('directlink_extensions', 'zip,tar,tar.gz'));

			if(empty($directlink_extensions))
			{
				$directlink_extensions = array();
			}
			else
			{
				$temp = array();

				foreach($directlink_extensions as $ext)
				{
					$temp[] = '.' . trim($ext);
				}

				$directlink_extensions = $temp;
			}

			$this->directlink_extensions = $directlink_extensions;

			$this->directlink_description = $pparams->get('directlink_description', JText::_('COM_ARS_CONFIG_DIRECTLINKDESCRIPTION_DEFAULT'));
		}

		// Add RSS links
		$show_feed = $pparams->get('show_feed_link');

		if ($show_feed)
		{
			$feed = 'index.php?option=com_ars&view=category&id='.$category->id.'&format=feed';
			$rss = array(
				'type' => 'application/rss+xml',
				'title' => $title.' (RSS)'
			);
			$atom = array(
				'type' => 'application/atom+xml',
				'title' => $title.' (Atom)'
			);
			// add the links
			$document = JFactory::getDocument();
			$document->addHeadLink(JRoute::_($feed.'&type=rss'), 'alternate',
				'rel', $rss);
			$document->addHeadLink(JRoute::_($feed.'&type=atom'), 'alternate',
				'rel', $atom);
		}

		// Cleanup for display
		$items	= $model->itemList;

		foreach ( $items as $item )
		{
			$item->environments = ArsHelperHtml::getEnvironments( $item->environments );
		}

		$model->itemList	= $items;

		$this->category		= $category;
		$this->user			= $user;
		$this->dlid			= $dlid;
		$this->directlink	= $directlink;
		$this->pparams		= $pparams;
		$this->item			= $model->item;

		if (is_object($model->item) && method_exists($model->item, 'hit'))
		{
			$model->item->hit();
		}

		$this->items		= $model->itemList;
		$this->pagination	= $model->items_pagination;
		$this->release_id	= $model->item->id;
		$this->Itemid		= $this->input->getInt('Itemid', null);

		if($this->getLayout() == 'item')
		{
			$this->setLayout('default');
		}

		return true;
	}
}