<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Joomla\CMS\Router\Route;

/** @var \Akeeba\Component\ARS\Site\View\Update\XmlView $this */

$streamTypeMap = [
		'components' => 'component',
		'libraries'  => 'library',
		'modules'    => 'module',
		'packages'   => 'package',
		'plugins'    => 'plugin',
		'files'      => 'file',
		'templates'  => 'template'
];

$xml = new SimpleXMLElement(<<< XML
<?xml version="1.0" encoding="UTF-8"?>
<extensionset />
XML
);

if (!empty($this->updates_name))
{
	$xml->addAttribute('name', $this->updates_name);
}

if (!empty($this->updates_desc))
{
	$xml->addAttribute('description', $this->updates_desc);
}

$xml->addAttribute('category', ucfirst($this->category));

foreach($this->items as $item)
{
	$node = $xml->addChild('extension');
	$node->addAttribute('name', $item->name);
	$node->addAttribute('element', $item->element);
	$node->addAttribute('type', $streamTypeMap[$item->type]);
	$node->addAttribute('version', $item->version);
	$node->addAttribute('detailsurl', Route::_(
		sprintf("index.php?option=com_ars&view=update&format=xml&task=stream&id=%s%s", $item->id, $this->dlidRequest),
		true, Route::TLS_IGNORE, true
	));
}

@ob_end_clean();

echo $xml->asXML();