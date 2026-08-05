<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Component\ARS\Site\View\Update\XmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var XmlView $this */

/** @var \Joomla\CMS\Document\XmlDocument $document */
$document = $this->getDocument();

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

foreach (['components', 'libraries', 'modules', 'packages', 'plugins', 'files', 'templates'] as $category)
{
	$node = $xml->addChild('category');
	$node->addAttribute('name', ucfirst($category));
	$node->addAttribute('description', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_' . $category));
	$node->addAttribute('category', $category);
	// $xhtml MUST be false: SimpleXMLElement::addAttribute() escapes the ampersands itself. Asking
	// Route::_() to do it as well is what produced `&amp;amp;` in this attribute.
	$node->addAttribute('ref', Route::_(
		sprintf("index.php?option=com_ars&view=update&format=xml&task=category&id=%s%s", $category, $this->dlidRequest),
		false, \Joomla\CMS\Router\Route::TLS_IGNORE, true
	));
}

@ob_end_clean();

echo $xml->asXML();
