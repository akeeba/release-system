<?php
/**
 * Old PHP version notification
 *
 * @copyright Copyright (c) 2018-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

(defined('_JEXEC') || defined('WPINC') || defined('APATH_BASE') || defined('AKEEBA_COMMON_WRONGPHP') || defined('KICKSTART')) or die;

if (!function_exists('akeeba_common_phpversion_warning'))
{
	/**
	 * The function checks if you are using an obsolete PHP version and outputs a warning if you do.
	 *
	 * Configuration array:
	 *
	 * * softwareName: human-readable software name, e.g. "Akeeba Example"
	 * * class_priority_low: CSS class for low priority notices
	 * * class_priority_medium: CSS class for medium priority warnings
	 * * class_priority_high: CSS class for high priority errors
	 * * warn_about_maintenance: should I warn about PHP versions which have entered maintenance mode? Def: TRUE.
	 * * maintenance_period: how long after we enter maintenance period should I warn the user? Def: P6M.
	 * * eol_period_too_old: how long after EOL is the PHP version considered dangerous, as DateInterval text, e.g. P3M
	 * * longVersion: current PHP version, long format, e.g. "7.3.1-12ubuntu3.2". Skip to automatically determine.
	 * * shortVersion: current PHP version, short format, e.g. "7.3". Skip to automatically determine.
	 * * currentTimestamp: current UNIX timestamp. Skip to automatically determine.
	 *
	 * You need to provide at the very least the softwareName. The default CSS classes are compatible with Akeeba FEF.
	 * PHP versions in maintenance will be warned about and the period after which an EOL version of PHP is dangerous is
	 * considered to be 3 months.
	 *
	 * @param   array  $config  See above
	 *
	 * @throws  Exception
	 */
	function akeeba_common_phpversion_warning($config = array())
	{
		/**
		 * Format: version => [maintenance_date, eol_date]
		 *
		 * For versions older than 5.6 we use a fake maintenance_date because this information no longer exists on PHP's
		 * site and it's irrelevant anyway; these PHP versions are already EOL therefore we only use their EOL date.
		 */
		$phpDates = array(
			'3.0' => array('1990-01-01 00:00:00', '2000-10-20 00:00:00'),
			'4.0' => array('1990-01-01 00:00:00', '2001-06-23 00:00:00'),
			'4.1' => array('1990-01-01 00:00:00', '2002-03-12 00:00:00'),
			'4.2' => array('1990-01-01 00:00:00', '2002-09-06 00:00:00'),
			'4.3' => array('1990-01-01 00:00:00', '2005-03-31 00:00:00'),
			'4.4' => array('1990-01-01 00:00:00', '2008-08-07 00:00:00'),
			'5.0' => array('1990-01-01 00:00:00', '2005-09-05 00:00:00'),
			'5.1' => array('1990-01-01 00:00:00', '2006-08-24 00:00:00'),
			'5.2' => array('1990-01-01 00:00:00', '2011-01-11 00:00:00'),
			'5.3' => array('1990-01-01 00:00:00', '2014-08-14 00:00:00'),
			'5.4' => array('1990-01-01 00:00:00', '2015-09-03 00:00:00'),
			'5.5' => array('1990-01-01 00:00:00', '2016-07-10 00:00:00'),
			'5.6' => array('2017-01-10 00:00:00', '2018-12-31 00:00:00'),
			'7.0' => array('2018-01-01 00:00:00', '2019-01-10 00:00:00'),
			'7.1' => array('2018-12-01 00:00:00', '2019-12-01 00:00:00'),
			'7.2' => array('2019-11-30 00:00:00', '2020-11-30 00:00:00'),
			'7.3' => array('2020-12-06 00:00:00', '2021-12-06 00:00:00'),
			'7.4' => array('2021-11-28 00:00:00', '2022-11-28 00:00:00'),
			'8.0' => array('2022-11-26 00:00:00', '2023-11-26 00:00:00'),
			'8.1' => array('2023-11-25 00:00:00', '2024-11-25 00:00:00'),
		);

		// Make sure I have all necessary configuration variables
		$useFef = !defined('JVERSION') || version_compare(JVERSION, '4.0.0', 'lt');
		$config = array_merge(array(
			'softwareName'           => 'This software',
			'class_priority_low'     => $useFef ? 'akeeba-block--info' : 'alert alert-info',
			'class_priority_medium'  => $useFef ? 'akeeba-block--warning' : 'alert alert-warning',
			'class_priority_high'    => $useFef ? 'akeeba-block--failure' : 'alert alert-danger',
			'warn_about_maintenance' => true,
			'maintenance_period'     => 'P6M',
			'eol_period_too_old'     => 'P3M',
			'longVersion'            => PHP_VERSION,
			'shortVersion'           => sprintf('%d.%d', PHP_MAJOR_VERSION, PHP_MINOR_VERSION),
			'currentTimestamp'       => time(),
		), $config);

		// Selectively extract configuration variables. Do not use extract(), it's potentially dangerous.
		$softwareName           = $config['softwareName'];
		$class_priority_low     = $config['class_priority_low'];
		$class_priority_medium  = $config['class_priority_medium'];
		$class_priority_high    = $config['class_priority_high'];
		$warn_about_maintenance = $config['warn_about_maintenance'];
		$maintenance_period     = $config['maintenance_period'];
		$eol_period_too_old     = $config['eol_period_too_old'];
		$longVersion            = $config['longVersion'];
		$shortVersion           = $config['shortVersion'];
		$currentTimestamp       = $config['currentTimestamp'];
		$phpVersions            = array_keys($phpDates);
		$lastVersion            = array_pop($phpVersions);

		/**
		 * Safe defaults for PHP versions older than 5.3.0.
		 *
		 * Older PHP versions don't even have support for DateTime so we need these defaults to prevent this warning script from
		 * bringing the site down with an error.
		 */
		$isEol      = true;
		$isAncient  = true;
		$isSecurity = false;
		$isCurrent  = false;
		$isTooNew   = !isset($phpDates[$shortVersion]) && version_compare($shortVersion, $lastVersion, 'gt');

		$eolDateFormatted      = isset($phpDates[$shortVersion]) ? $phpDates[$shortVersion][1] : '';
		$securityDateFormatted = isset($phpDates[$shortVersion]) ? $phpDates[$shortVersion][0] : '';

		/**
		 * This can only work on PHP 5.3.0 or later since we are using DatePeriod (PHP >= 5.3.0)
		 */
		if (version_compare($longVersion, '5.2.0', 'ge') && !$isTooNew)
		{
			$tzGmt         = new DateTimeZone('GMT');
			$securityDate  = new DateTime($phpDates[$shortVersion][0], $tzGmt);
			$eolDate       = new DateTime($phpDates[$shortVersion][1], $tzGmt);
			$ancientPeriod = new DateInterval($eol_period_too_old);
			$ancientDate   = clone $eolDate;
			$ancientDate->add($ancientPeriod);

			if (!empty($maintenance_period))
			{
				$maintenancePeriod = new DateInterval($maintenance_period);
				$securityDate      = $securityDate->add($maintenancePeriod);
			}

			/**
			 * Ancient:  This PHP version has reached end-of-life more than $eol_period_too_old ago
			 * EOL:      This PHP version has reached end-of-life
			 * Security: This PHP version has reached the Security Support date but not the EOL date yet
			 * Current:  This PHP version is still in Active Support
			 */
			$isEol      = $eolDate->getTimestamp() <= $currentTimestamp;
			$isAncient  = $ancientDate->getTimestamp() <= $currentTimestamp;
			$isSecurity = !$isEol && ($securityDate->getTimestamp() <= $currentTimestamp);
			$isCurrent  = !$isEol && !$isSecurity;

			$eolDateFormatted      = $eolDate->format('l, d F Y');
			$securityDateFormatted = $securityDate->format('l, d F Y');
		}

		if ($isCurrent)
		{
			return;
		}

		if ($isTooNew): ?>
			<!-- Your PHP version is too new -->
			<div class="<?php echo $class_priority_medium ?>">
				<h3>PHP version <?php echo $shortVersion ?> is newer than this software supports</h3>

				<p>
					Your site is currently using PHP <?php echo $longVersion ?>. This version of PHP was released after your currently installed version of <?php echo $softwareName ?> was released. As a result, we cannot guarantee that <?php echo $softwareName ?> will work correctly on your site.
				</p>
				<p>
					Please check for an updated version of <?php echo $softwareName ?>. Kindly note that it usually takes us a few days to weeks after the initial (X.Y<strong>.0</strong>) PHP version release before we publish a version of our software which supports it.
				</p>
			</div>
		<?php elseif ($isAncient): ?>
			<!-- Your PHP version has been End-of-Life for a very long time -->
			<div class="<?php echo $class_priority_high ?>">
				<h3>Severely outdated PHP version <?php echo $shortVersion ?></h3>

				<p>
					Your site is currently using PHP <?php echo $longVersion ?>. This version of PHP has become <a href="https://php.net/eol.php" target="_blank">End-of-Life since <?php echo $eolDateFormatted ?></a>. It has not received security updates for a <em>very long time</em>. You MUST NOT use it for a live site!
				</p>
				<p>
					<?php echo $softwareName ?> will stop supporting your version of PHP very soon. You must <strong>very urgently</strong> upgrade to a newer version of PHP. You can check <a href="https://www.akeeba.com/compatibility.html">our Compatibility page</a> to see which versions of PHP are supported by each version of our software, select the newest one that fits your site's needs and upgrade your site to it. You can ask your host or your system administrator for instructions on upgrading PHP. It's easy and it will make your site faster and more secure.
				</p>
			</div>
		<?php
		elseif ($isEol):
			?>
			<!-- Your PHP version has recently been marked End-of-Life -->
			<div class="<?php echo $class_priority_medium ?>">
				<h3>Outdated PHP version <?php echo $shortVersion ?></h3>

				<p>
					Your site is currently using PHP <?php echo $longVersion ?>. This version of PHP has recently become <a href="https://php.net/eol.php" target="_blank">End-of-Life since <?php echo $eolDateFormatted ?></a>. It has stopped receiving security updates. You should not use it for a live site.
				</p>
				<p>
					<?php echo $softwareName ?> will stop supporting your version of PHP in the near future. You should upgrade to a newer version of PHP at your earliest convenience. You can check <a href="https://www.akeeba.com/compatiblity.html">our Compatibility page</a> to see which versions of PHP are supported by each version of our software, select the newest one that fits your site's needs and upgrade your site to it. You can ask your host or your system administrator for instructions on upgrading PHP. It's easy and it will make your site faster and more secure.
				</p>
			</div>
		<?php
		elseif ($warn_about_maintenance):
			?>
			<!-- Your PHP version has entered “Security Support” and will become EOL rather soon -->
			<div class="<?php echo $class_priority_low ?>">
				<h3>PHP <?php echo $shortVersion ?> is approaching End–of–Life</h3>

				<p>
					Your site is currently using PHP <?php echo $longVersion ?>. This version of PHP has entered its “Security maintenance” phase since <?php echo $securityDateFormatted ?> and has stopped receiving bug fixes. It will stop receiving security updates on <?php echo $eolDateFormatted ?> at which point it will be unsuitable for use on a live site. We recommend updating to a newer PHP version as soon as possible.
				</p>
			</div>
		<?php
		endif;
	}
}

/**
 * Immediately executes the akeeba_common_phpversion_warning() function on all of our software except Kickstart.
 */
if (!defined('KICKSTART'))
{
	try
	{
		$useFef = !defined('JVERSION') || version_compare(JVERSION, '4.0.0', 'lt');
		akeeba_common_phpversion_warning(array(
			// Configuration -- Override before calling this script
			'softwareName'           => isset($softwareName) ? $softwareName : 'This software',
			'class_priority_low'     => $useFef ? 'akeeba-block--info' : 'alert alert-info',
			'class_priority_medium'  => $useFef ? 'akeeba-block--warning' : 'alert alert-warning',
			'class_priority_high'    => $useFef ? 'akeeba-block--failure' : 'alert alert-danger',
			'warn_about_maintenance' => isset($warn_about_maintenance) ? ((bool) $warn_about_maintenance) : true,
			'eol_period_too_old'     => isset($eol_period_too_old) ? $eol_period_too_old : 'P3M',
			// Override these to test the script
			'longVersion'            => isset($longVersion) ? $longVersion : PHP_VERSION,
			'shortVersion'           => isset($shortVersion) ? $shortVersion : sprintf('%d.%d', PHP_MAJOR_VERSION, PHP_MINOR_VERSION),
			'currentTimestamp'       => isset($currentTimestamp) ? $currentTimestamp : time(),
		));
	}
	catch (Exception $e)
	{
		// This should never happen
		return;
	}
}
