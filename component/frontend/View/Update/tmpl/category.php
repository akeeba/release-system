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

$tag = "<"."?xml version=\"1.0\" encoding=\"utf-8\""."?".">";

$dlid = trim($this->input->getCmd('dlid',''));

if (!empty($dlid))
{
	$dlid = Filter::reformatDownloadID($dlid);
}

if (!empty($dlid))
{
	$dlid = '&dlid='.$dlid;
}
else
{
	$dlid = '';
}

$streamTypeMap = array(
	'components' => 'component',
	'libraries' => 'library',
	'modules' => 'module',
	'packages' => 'package',
	'plugins' => 'plugin',
	'files' => 'file',
	'templates' => 'template'
);

@ob_end_clean();
//@header('Content-type: application/xml');
?><?php echo $tag; ?>
<!-- Update stream generated automatically by Akeeba Release System on <?=gmdate('Y-m-d H:i:s')?> -->
<extensionset category="<?php echo ucfirst($this->category)?>" name="<?php echo ucfirst($this->category)?>" description="<?php echo JText::_('LBL_UPDATETYPES_'.strtoupper($this->category)); ?>">
<?php
if(!empty($this->items)) foreach($this->items as $item):
$url = $rootURL . Router::_('index.php?option=com_ars&view=update&format=xml&task=stream&id=' . $item->id . $dlid);
$url = str_replace('&', '&amp;', $url);

if (substr($url,-4) != '.xml')
{
	$url .= (strpos($url, '?') ? '&amp;' : '?').'dummy=extension.xml';
}
?>
	<extension name="<?php echo $item->name ?>" element="<?php echo $item->element ?>" type="<?php echo $streamTypeMap[$item->type] ?>" version="<?php echo $item->version?>" detailsurl="<?php echo $url ?>" />
<?php endforeach ?>
</extensionset>
