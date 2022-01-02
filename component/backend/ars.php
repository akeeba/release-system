<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

define('AKEEBA_COMMON_WRONGPHP', 1);
$minPHPVersion         = '7.3.0';
$recommendedPHPVersion = '7.4';
$softwareName          = 'Akeeba Release System';

if (!require_once(__DIR__ . '/tmpl/ErrorPages/wrongphp.php'))
{
	return;
}

// HHVM made sense in 2013, now PHP 7 is a way better solution than an hybrid PHP interpreter
if (defined('HHVM_VERSION'))
{
	(include_once __DIR__ . '/tmpl/ErrorPages/hhvm.php') or die('We have detected that you are running HHVM instead of PHP. This software WILL NOT WORK properly on HHVM. Please switch to PHP 7 instead.');

	return;
}

// So, FEF is not installed?
if (!@file_exists(JPATH_SITE . '/media/fef/fef.php'))
{
	(include_once __DIR__ . '/tmpl/ErrorPages/fef.php') or die('You need to have the Akeeba Frontend Framework (FEF) package installed on your site to display this component. Please visit https://www.akeeba.com/download/official/fef.html to download it and install it on your site.');

	return;
}

/**
 * The following code is a neat trick to help us collect the maximum amount of relevant information when a user
 * encounters an unexpected exception (PHP 5.4+) or a PHP fatal error (PHP 7+). In both cases we capture the generated
 * exception and render an error page, making sure that the HTTP response code is set to an appropriate value (4xx or
 * 5xx).
 *
 * Why the two functions? In PHP 5 the base exception class is Exception. In PHP 7 there is a base interface called
 * Throwable which the two base classes Exception (user-defined exception) and Error (PHP fatal error) implement.
 * However, Throwable does not exist in PHP 5 so we can't have a try-catch expecting Throwable. At the same time, in
 * PHP 7 neither catching Exception will handle PHP fatal errors nor can you manually implement Throwable to create a
 * base class for use in try-catch. Therefore the only solution is to have two functions for the try and catch part,
 * a conditional for the PHP version and a slightly different catch block in each case.
 *
 * Now you know what we did and why we did it. Feel free to include this idea in your GPL projects :)
 */

function mainLoopAkeebaReleaseSystem()
{
	if (!defined('FOF40_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof40/include.php'))
	{
		(include_once __DIR__ . '/tmpl/ErrorPages/fof.php') or die('You need to have the Akeeba Framework-on-Framework (FOF) 3 package installed on your site to use this component. Please visit https://www.akeeba.com/download/fof4.html to download it and install it on your site.');

		return;
	}

	FOF40\Container\Container::getInstance('com_ars')->dispatcher->dispatch();
};

function errorHandlerAkeebaReleaseSystem($e)
{
	$title = 'Akeeba Release System';
	$isPro = false;

	if (!(include_once __DIR__ . '/tmpl/ErrorPages/errorhandler.php'))
	{
		throw $e;
	}
}

// PHP 7.0 or later; we can catch PHP Fatal Errors as well
try
{
	mainLoopAkeebaReleaseSystem();
}
catch (Throwable $e)
{
	errorHandlerAkeebaReleaseSystem($e);
}