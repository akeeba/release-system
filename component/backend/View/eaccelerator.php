<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

?>

<div style="margin: 1em">
	<h1>eAccelerator is not compatible with PHP 5.4 and later</h1>
	<hr/>
	<p style="font-size: 180%; line-height: 1.5; margin: 2em 1em;">
		Your host is using a broken, abandoned PHP extension which doesn't allow modern software to run. Ask your
		host to disable eAccelerator before trying to run this component.
	</p>
	<p>
		Your host is using eAccelerator, a code cache which is broken (<a href="https://github.com/eaccelerator/eaccelerator/issues/12">example</a>)
		and abandoned in 2012. In fact, <a href="https://github.com/eaccelerator/eaccelerator/pull/44">there is an open
		issue on its project site about it being dead</a>. Simple facts:
	</p>
	<ul>
		<li>eAccelerator breaks perfectly working PHP 5.4 code.</li>
		<li>eAccelerator was abandoned in 2012.</li>
		<li>No sane person should ever use eAccelerator on a production server because of the above.</li>
	</ul>
	<p>
		Please let your host know that they are using outdated software on their site and demand that they deactivate
		it at once. Once eAccelerator is deactivated this message will go away and Akeeba Release System will work just fine.
	</p>
	<p>
		Please note that PHP 5.5 and later come with Zend Opcache built in. It is a much better solution which is
		actively supported by the people who make PHP. Ask your server to use it.
	</p>
</div>
