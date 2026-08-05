<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/** @var \Akeeba\Component\ARS\Site\View\Update\JsonView $this */

echo json_encode($this->payload, (defined('JDEBUG') && JDEBUG) ? JSON_PRETTY_PRINT : 0);
