<?php
/**
 * Missing FOF 4.x error page
 *
 * @copyright Copyright (c) 2018-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

$tooLongAgo = (int) gmdate('Y') - 2015;
?>

<div style="margin: 1em">
	<h1>Akeeba Framework-on-Framework (FOF) version 4 could not be found on this site</h1>
	<hr />
	<div class="alert alert-warning">
		<h2>
			This component requires the Akeeba FOF framework package, verion 4, to be installed on your site. Please go
			to <a href="https://www.akeeba.com/download/fof4.html">our download page</a> to download it, then install
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
		or another extension mistakenly uninstalled it or deleted its files.
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
			with FOF 3.x. Starting February 2021 we replaced it with FOF 4.x.
		</p>
		<p>
			The different FOF versions are incompatible with each other but all may be required on your site for
			different reasons:
		</p>
		<ul>
			<li>
				<strong>FOF 2.x</strong> is required by Joomla! 3.x itself. Some of its core features, such as
				post-installation messages and Two Factor Authentiaction, use it.
			</li>
			<li>
				<strong>FOF 3.x</strong> is used by Akeeba Ltd extensions released before late February 2021 and some third
				party extensions.
			</li>
			<li>
				<strong>FOF 4.x</strong> is used by Akeeba Ltd extensions release <em>after</em> February 2021 and some third
				party extensions.
			</li>
		</ul>
		<p>
			Depending on which extensions you are using on your site you may see some or all of the above FOF versions.
			You <strong>must not</strong> try to uninstall them or delete their files yourself. If you do that, you will
			break some extensions and may lose access to your site.
		</p>
		<p>
			Please note that Akeeba extensions will automatically uninstall FOF versions 3 and 4 when they are no longer
			marked as needed by any extensions installed on your sites. Please note that whether they are needed or not
			is something that each extension needs to communicate to Joomla when it is installed, updated or
			uninstalled. While we can vouch for our extensions' ability to correctly communicate this information we
			can not make any promises for third party extensions. If you are unable to uninstall FOF 3 or 4 after
			uninstalling all Akeeba Ltd extensions from your site the culprit is a third party extension. Such an
			extension either still uses FOF or didn't communicate its upgrade or uninstallation, letting Joomla think
			there is still an extension depending on FOF. We cannot provide support for the latter issue; it's something
			caused by a different developer's code. Thank you for your understanding!
		</p>
	<?php endif; ?>
</div>
