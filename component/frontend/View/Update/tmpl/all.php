<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \Akeeba\ReleaseSystem\Site\View\Update\Xml $this */

use Akeeba\ReleaseSystem\Site\Helper\Router;
use Akeeba\ReleaseSystem\Site\Helper\Filter;

$rootURL    = rtrim(JURI::base(), '/');
$subpathURL = JURI::base(true);

if (!empty($subpathURL) && ($subpathURL != '/'))
{
	$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
}

$categories = array('components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates');
$tag        = "<" . "?xml version=\"1.0\" encoding=\"utf-8\"" . "?" . ">";

$dlid = trim($this->input->getCmd('dlid', ''));

if (!empty($dlid))
{
	$dlid = Filter::reformatDownloadID($dlid);
}

if (!empty($dlid))
{
	$dlid = '&dlid=' . $dlid;
}
else
{
	$dlid = '';
}

@ob_end_clean();
// @header('Content-type: application/xml');
?><?php echo $tag; ?>
<!-- Update stream generated automatically by Akeeba Release System on <?= gmdate('Y-m-d H:i:s') ?> -->
<extensionset name="<?php echo $this->updates_name ?>" description="<?php echo $this->updates_desc ?>">
<?php foreach ($categories as $category):
$url = $rootURL .
	   Router::_('index.php?option=com_ars&view=update&format=xml&task=category&id=' . $category . $dlid);
$url = str_replace('&', '&amp;', $url);

if (substr($url, -4) != '.xml')
{
	$url .= (strpos($url, '?') ? '&amp;' : '?') . 'dummy=extension.xml';
}
?>
	<category name="<?php echo ucfirst($category)?>"
			  description="<?php echo JText::_('LBL_UPDATETYPES_' . strtoupper($category)); ?>"
			  category="<?php echo $category ?>" ref="<?php echo $url ?>" />
<?php endforeach ?>
</extensionset>
