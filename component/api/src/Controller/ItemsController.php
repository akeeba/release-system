<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Api\Controller;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Api\Controller\Mixin\PopulateModelState;
use Joomla\CMS\MVC\Controller\ApiController;

class ItemsController extends ApiController
{
	use PopulateModelState;

	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $contentType = 'items';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  7.0.0
	 */
	protected $default_view = 'items';

	public function displayList()
	{
		$stateMapper = [
			['search', 'filter.search', 'string'],
			['category_id', 'filter.category_id', 'int'],
			['release_id', 'filter.release_id', 'int'],
			['published', 'filter.published', 'int'],
			['show_unauth_links', 'filter.show_unauth_links', 'int'],
			['access', 'filter.access', 'int'],
			['language', 'filter.language', 'string'],
		];

		$this->populateModelState($stateMapper);

		return parent::displayList();
	}

	public function delete($id = null)
	{
		$fileToDelete = null;
		if ($this->input->getInt('delete_file') === 1) {
			$fileToDelete = $this->getFileNameToDelete();
		}

		parent::delete($id);

		if (!$fileToDelete) {
			return;
		}

		unlink($fileToDelete);

		if ($this->input->getInt('delete_empty_directory') !== 1) {
			return;
		}

		// The iterator is valid, when the directory is not empty
		if ((new \FilesystemIterator(dirname($fileToDelete)))->valid()) {
			return;
		}

		rmdir(dirname($fileToDelete));
	}

	private function getFileNameToDelete(): string
	{
		$id = $this->input->get('id', 0, 'int');
		if (!$id) {
			return '';
		}

		$item = $this->getModel('Item')->getItem($id);
		if (empty($item->filename) || $item->type !== 'file') {
			return '';
		}

		$release = $this->getModel('Release')->getItem($item->release_id);
		if (empty($release->category_id)) {
			return '';
		}

		$category = $this->getModel('Category')->getItem($release->category_id);
		if (empty($category->directory)) {
			return '';
		}

		$folder = JPATH_ROOT . '/' . $category->directory;
		if (!is_dir($folder)) {
			return '';
		}

		if (!is_file($folder . '/' . $item->filename)) {
			return '';
		}

		return $folder . '/' . $item->filename;
	}
}
