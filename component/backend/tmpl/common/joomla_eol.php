<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') || die();

?>
<?php if (time() > 1760475600): ?>
	<details class="alert alert-danger">
		<summary class="alert-heading h3">Joomla! 4 has reached End of Service</summary>
		<p>
			Joomla! 4 became End of Service on October 15th, 2025.
		</p>
		<p>
			Our software for Joomla! 4 is also End of Life. We will no longer provide any updates or support.
		</p>
		<p>
			Kindly note that we started showing these notices since October 15th, 2023 — two years before the planned End of Life of our software for Joomla! 4.
		</p>
	</details>
<?php elseif (time() > 1728939600): ?>
	<details class="alert alert-warning">
		<summary class="alert-heading h3">Joomla! 4 is approaching End of Service</summary>
		<p>
			Joomla! 4 is currently in security–only maintenance. It will become End of Service on October 15th, 2025.
		</p>
		<p>
			Our software for Joomla! 4 is also in security-only maintenance. We only provide security updates and limited support for it until October 15th, 2025.
		</p>
		<p>
			<strong>You need to update your site to Joomla! 5 as soon as possible.</strong> We will not provide any updates or support after October 15th, 2025. Moreover, we do not guarantee an update path to Joomla! 5 and beyond will exist after October 15th, 2025.
		</p>
	</details>
<?php elseif(time() > 1697317200): ?>
	<details class="alert alert-info">
		<summary class="alert-heading h3">Joomla! 4 is approaching Security Maintenance</summary>
		<p>
			Joomla! 4 will enter security–only maintenance on October 15th, 2024. It will become End of Service on October 15th, 2025.
		</p>
		<p>
			Will provide full support and updates for our Joomla! 4 software until October 15th, 2024. From then until October 15th, 2025 we will only provide security updates and limited support. We will not provide any updates or support after October 15th, 2025.
		</p>
		<p>
			We urge you to upgrade your site to Joomla! 5 before October 15th, 2024. Please note that we do not guarantee an update path to Joomla! 5 and beyond after October 15th, 2025.
		</p>
	</details>
<?php endif; ?>
