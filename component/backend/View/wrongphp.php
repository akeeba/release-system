<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// PHP 5.6 is modern enough. Anything else gets a warning.
$minPHPVersion = '5.4.0';
$recommendedPHPVersion = '5.6.0';

if (!version_compare(PHP_VERSION, $minPHPVersion, 'lt'))
{
	return;
}

$tx = new DateTimeZone('GMT');

// PHP 5.3
if (version_compare(PHP_VERSION, '5.4.0', 'lt'))
{
	$akeebaCommonDatePHP = new DateTime('2014-08-14 00:00:00', $tx);
}
// PHP 5.4
elseif (version_compare(PHP_VERSION, '5.5.0', 'lt'))
{
	$akeebaCommonDatePHP = new DateTime('2015-09-03 00:00:00', $tx);
}
// PHP 5.5
elseif (version_compare(PHP_VERSION, '5.6.0', 'lt'))
{
	$akeebaCommonDatePHP = new DateTime('2016-07-10 00:00:00', $tx);
}
// PHP 5.6
elseif (version_compare(PHP_VERSION, '5.7.0', 'lt'))
{
	$akeebaCommonDatePHP = new DateTime('2018-12-31 00:00:00', $tx);
}
// PHP 7.0
elseif (version_compare(PHP_VERSION, '7.1.0', 'lt'))
{
	$akeebaCommonDatePHP = new DateTime('2018-12-03 00:00:00', $tx);
}
// PHP 7.1
elseif (version_compare(PHP_VERSION, '7.2.0', 'lt'))
{
	$akeebaCommonDatePHP = new DateTime('2019-12-1 00:00:00', $tx);
}
// PHP 7.2
elseif (version_compare(PHP_VERSION, '7.3.0', 'lt'))
{
	$akeebaCommonDatePHP = new DateTime('2020-11-30 00:00:00', $tx);
}

?>

<div style="margin: 1em">
	<h1>Outdated PHP version <?php echo PHP_VERSION ?> detected</h1>
	<hr/>
	<p style="font-size: 180%; margin: 2em 1em;">
		Akeeba Release System requires PHP <?php echo $minPHPVersion ?> or any later version to work.
	</p>
	<p>
		We <b>strongly</b> urge you to update to PHP <?php echo $recommendedPHPVersion ?> or later. If you are
		unsure how to do this, please ask your host.
	</p>
	<p>
		<a href="https://www.akeebabackup.com/how-do-version-numbers-work.html">Version numbers don't make sense?</a>
	</p>

	<hr/>

	<h3>Security advice</h3>
	<p>
		Your version of PHP, <?php echo PHP_VERSION ?>, <a href="http://php.net/eol.php">has reached the end
		of its life</a> on <?php echo $akeebaCommonDatePHP->format(JText::_('DATE_FORMAT_LC1')) ?>. You are
		strongly urged to upgrade to a current version, as using older versions may expose you to security
		vulnerabilities and bugs that have been fixed in more recent versions of PHP.
	</p>
</div>
