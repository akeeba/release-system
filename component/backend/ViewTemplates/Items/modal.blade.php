<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Akeeba\ReleaseSystem\Admin\Model\Categories;
use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF30\Utils\FEFHelper\Html as FEFHtml;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

/** @var Akeeba\ReleaseSystem\Admin\View\Items\Html $this */
?>
@include('admin:com_ars/Items/default', ['modal' => true])