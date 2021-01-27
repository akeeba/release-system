<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

$tooLongAgo = (int) gmdate('Y') - 2015;
?>

<div style="margin: 1em">
	<h1>Akeeba Framework-on-Framework (FOF) version 3 could not be found on this site</h1>
	<hr />
	<div class="alert alert-warning">
		<h2>
			This component requires the Akeeba FOF framework package to be installed on your site. Please go to <a
					href="https://www.akeeba.com/download/fof4.html">our download page</a> to download it, then install
			it on your site.
		</h2>
	</div>
	<hr />
	<h4>Further information</h4>
	<p>
		FOF is a Joomla component framework. It's the low level code which sits between our Joomla! extensions and
		Joomla! itself. It is automatically installed when you install our extensions on your site.
	</p>
	<p>
		FOF can be missing from your site either because Joomla failed to install it or because you, another Super User,
		or another extension mistakenly uninstalled it.
	</p>
	<p>
		If it's missing, our components cannot talk to Joomla &mdash; or vice versa. Because of that they can not run.
		That's why you see this message.
	</p>
	<p>
		You do not have to worry about adding bloat to your site. FOF is very small. It will also be automatically
		uninstalled when you uninstall all components which depend on it.
	</p>
	<p>
		FOF is installed in the <code><?php echo rtrim(JPATH_LIBRARIES, '/\\') . DIRECTORY_SEPARATOR ?>fof40</code>
		folder on your server. It appears in Joomla's Extensions, Manage page as <code>FOF40</code>. Please do not
		remove it from your site.
	</p>
	<?php if (version_compare(JVERSION, '3.9999.9999', 'le')): ?>
		<h4>Why do I have multiple FOF entries in Joomla?</h4>
		<p>
			Joomla <?php echo JVERSION ?> includes an <em>old, obsolete</em> version of FOF - version 2.x. It is
			installed in the <code><?php echo JPATH_LIBRARIES . DIRECTORY_SEPARATOR ?>fof</code> folder on your server.
			It appears in Joomla's Extensions, Manage page as <code>FOF</code>. Please do not remove it from your site;
			Joomla needs it to function properly.
		</p>
	<p>
		We discontinued FOF 2.x in 2015 &mdash; that's <?php echo $tooLongAgo ?> years ago. Ever since, we replaced it
		with FOF 3.x. Starting February 2021 we replaced it with FOF 4.x. The three versions are incompatible with each
		other but they are all required; FOF 2.x for Joomla! 3.x itself, FOF 3.x for our old extensions and some third
		party extensions, FOF 4.x for our newer extensions. That's why you see all of them. You must not remove any of
		them or something will break! Please note that FOF 3.x is automatically uninstalled if it's no longer necessary
		as part of our extensions' update or uninstallation. We can't vouch that third party developers are as diligent,
		though.
	</p>
<?php endif; ?>
</div>
