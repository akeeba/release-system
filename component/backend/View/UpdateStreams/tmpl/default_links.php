<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var  int  $fieldValue  The ID of the UpdateStreams item */

?>
<a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&format=ini&id=<?php echo (int)$fieldValue ?>" target="_blank">INI</a>
&bull;
<a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=stream&format=xml&id=<?php echo (int)$fieldValue ?>" target="_blank">XML</a>
&bull;
<a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=jed&format=xml&id=<?php echo (int)$fieldValue ?>" target="_blank">JED</a>
&bull;
<a href="<?php echo JURI::root() ?>index.php?option=com_ars&view=update&task=download&format=raw&id=<?php echo (int)$fieldValue ?>" target="_blank">D/L</a>
