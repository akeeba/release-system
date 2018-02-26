<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use FOF30\Container\Container;

defined('_JEXEC') or die();

class plgArsBleedingedgediff extends JPlugin
{
	private $_enabled = false;

	/**
	 * The component container
	 *
	 * @var   Container
	 */
	protected $container;

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		// Make sure the Horde_Text_Diff engine can be loaded
		$file = dirname(__FILE__) . '/bleedingedgediff.diff.php';

		if (!file_exists($file))
		{
			$this->_enabled = false;

			return;
		}

		require_once $file;

		$this->_enabled = true;
		$this->container = Container::getInstance('com_ars');
	}

	public function onNewARSBleedingEdgeItem($info, $data)
	{
		// Sanity check :)
		if (!$this->_enabled)
		{
			return false;
		}

		// Make sure we can get the category's directory
		/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $release */
		$release     = $info['release'];
		$category_id = $release->category_id;
		$folder      = $this->_getCategoryDirectory($category_id);

		if (empty($folder))
		{
			return false;
		}

		// Check the file extension against the list
		$extensionsString = $this->params->get('validextensions', 'txt,js,htm,html,css');

		if (empty($extensionsString))
		{
			return false;
		}

		$temp  = explode(',', $extensionsString);
		$found = false;
		$fname = strtolower($data['filename']);

		foreach ($temp as $ext)
		{
			$extension = '.' . trim(strtolower($ext));

			if (substr($fname, - strlen($extension)) == $extension)
			{
				$found = true;
				break;
			}
		}

		if (!$found)
		{
			return false;
		}

		// Get the previous file (and make sure it exists)
		$previousFile = $this->_getPreviousFile($info, $data);

		if (is_null($previousFile))
		{
			return false;
		}

		if (!file_exists($previousFile))
		{
			return false;
		}

		// Create the diff
		$thisFile   = $folder . DIRECTORY_SEPARATOR . $data['filename'];
		$lines1     = file($thisFile);
		$lines2     = file($previousFile);
		$diffObject = new Horde_Text_Diff('native', array($lines2, $lines1));
		unset($lines1, $lines2);
		$renderer = new Horde_Text_Diff_Renderer_Html();
		$diff     = $renderer->render($diffObject);
		unset($renderer);
		unset($diffObject);

		// Open the tabs
		$data['description'] = $this->params->get('pretext', '');

		// Get the auto description, if requested, and create tabs or separator
		if ($this->params->get('use_description', 0))
		{
			$data['description'] .= $this->_getAutoDescription($info, $data) .
			                        $this->params->get('midtext', '');
		}

		// Update the item's description with the diff
		$data['description'] .= "<pre class=\"ars-diff-container\">$diff</pre>";

		// Close the tabs
		$data['description'] .= $this->params->get('posttext', '');

		return $data;
	}

	/**
	 * Returns the absolute path to the category's files
	 *
	 * @param int $category_id
	 */
	private function _getCategoryDirectory($category_id)
	{
		$db    = $this->container->db;
		$query = $db->getQuery(true)
		            ->select('*')
		            ->from($db->qn('#__ars_categories'))
		            ->where($db->qn('id') . ' = ' . $db->q($category_id));
		$db->setQuery($query);
		$category = $db->loadObject();

		$folder = $category->directory;
		JLoader::import('joomla.filesystem.folder');
		if (!JFolder::exists($folder))
		{
			$folder = JPATH_ROOT . '/' . $folder;
			if (!JFolder::exists($folder))
			{
				return;
			}
		}

		return $folder;
	}

	/**
	 * Returns the absolute path to the file with the same name on the previous
	 * published released.
	 *
	 * @param array $info
	 * @param array $data
	 *
	 * @return string|null
	 */
	private function _getPreviousFile($info, $data)
	{
		// Get the category directory
		$release     = $info['release'];
		$category_id = $release->category_id;
		$folder      = $this->_getCategoryDirectory($category_id);

		// Find the previous release
		$db    = $this->container->db;
		$query = $db->getQuery(true)
		            ->select('*')
		            ->from($db->qn('#__ars_releases'))
		            ->where($db->qn('category_id') . ' = ' . $db->q($category_id))
		            ->where($db->qn('id') . ' < ' . $db->q($release->id))
		            ->where($db->qn('published') . ' = ' . $db->q('1'))
		            ->order($db->qn('id') . ' DESC');
		$db->setQuery($query, 0, 1);
		$record = $db->loadObject();
		if (empty($record))
		{
			return null;
		}

		return $folder . DIRECTORY_SEPARATOR . $record->alias . DIRECTORY_SEPARATOR
		       . $info['file'];
	}

	/**
	 * Gets the automatic item description data for this new item
	 *
	 * @param array $info
	 * @param array $data
	 *
	 * @return object
	 */
	private function _getAutoDescription($info, $data)
	{
		// Let's get automatic item title/description records
		$subquery = $db->getQuery(true)
		               ->select($db->qn('category_id'))
		               ->from('#__ars_releases')
		               ->where($db->qn('id') . ' = ' . $db->q($info['release_id']));
		$query    = $db->getQuery(true)
		               ->select('*')
		               ->from($db->qn('#__ars_autoitemdesc'))
		               ->where($db->qn('category') . ' IN (' . $subquery . ')')
		               ->where('NOT ' . $db->qn('published') . ' = ' . $db->q('0'));
		$db->setQuery($query);
		$autoitems = $db->loadObjectList();
		$auto      = (object) array('title' => '', 'description' => '');
		if (!empty($autoitems))
		{
			$fname = basename($data['filename']);
			foreach ($autoitems as $autoitem)
			{
				$pattern = $autoitem->packname;
				if (empty($pattern))
				{
					continue;
				}

				if (fnmatch($pattern, $fname))
				{
					$auto = $autoitem;
					break;
				}
			}
		}

		return $auto;
	}
}
