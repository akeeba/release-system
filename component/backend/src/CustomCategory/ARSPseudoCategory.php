<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\CustomCategory;

defined('_JEXEC') || die;

use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Categories\CategoryNode;

class ARSPseudoCategory implements CategoryInterface
{
	public function __construct(protected array $options, protected string $section)
	{
	}

	#[\ReturnTypeWillChange]
	public function get($id = 'root', $forceload = false): ?CategoryNode
	{
		if ($id == 'root')
		{
			return new RootNode([], $this);
		}

		return new LeafNode($id, $this);
	}

	public function getExtension(): string
	{
		return 'com_ars';
	}
}