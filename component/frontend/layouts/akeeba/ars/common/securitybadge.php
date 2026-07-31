<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

use Joomla\CMS\Language\Text;

/**
 * Security severity badge for a release.
 *
 * Rendered through HTMLHelper::_('ars.securityBadge', $level, $extraClass). Override it in your site or
 * administrator template as html/layouts/com_ars/akeeba/ars/common/securitybadge.php if you need different
 * markup or CSS classes.
 *
 * @var array  $displayData  Incoming display data. These set the following variables.
 * @var int    $level        Security severity: 0 none, 1 low, 2 medium, 3 high, 4 critical.
 * @var string $extraClass   Extra CSS classes to add to the badge element.
 */

extract(
	array_merge(
		[
			'level'      => 0,
			'extraClass' => '',
		], $displayData
	)
);

$level = max(0, min((int) $level, 4));

/**
 * Severity 0 means “this is not a security release”; it must render nothing at all, not a “Security: None”
 * badge. The calling helper already short–circuits on it. This is a second line of defence for anyone rendering
 * this layout directly.
 */
if ($level === 0)
{
	return;
}

// Joomla's com_installer uses danger for High and Critical, and warning for the rest.
$colourClass = $level > 2 ? 'bg-danger' : 'bg-warning text-dark';

/**
 * The badge always follows other content — a maturity badge, or the version. The margin has to come from a
 * class: every call site renders it inside a d-flex heading, and flex containers drop the whitespace text
 * nodes between their children, so relying on the newline in the template gives no gap at all.
 */
?>
<span class="badge ms-2 <?= $colourClass ?> <?= $this->escape($extraClass) ?>">
	<?= $this->escape(
		Text::sprintf('COM_ARS_RELEASES_SECURITY_BADGE', Text::_('COM_ARS_RELEASES_SECURITY_' . $level))
	) ?>
</span>
