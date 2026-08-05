<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Identity probe for the ARS end-to-end test suite.
 *
 * THIS IS A TEST FIXTURE, not production code. SiteProvisioner copies this file into the throwaway
 * site's document root, substituting the shared secret. It boots the real front-end application far
 * enough to read the session's identity, then answers with JSON. It never routes or dispatches, so it
 * cannot have side effects on the site.
 *
 * Why this exists: the suite has to be able to say "this surfer is logged in as X" and "X holds these
 * privileges" without parsing a rendered template, which would differ between Joomla 5.4 and 6.1 and
 * break for reasons that have nothing to do with ARS.
 *
 * It also lets the ACL fixtures be *verified* rather than assumed. A permissions test that passes
 * because the fixture accidentally granted the wrong thing is worse than no test at all.
 *
 * THIS IS A TEST FIXTURE. It is written into a disposable container that is destroyed at the end of
 * the run, and it is never part of any ARS package. The shared secret is there so a stray request
 * cannot reach it; it is not a security boundary.
 */

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\Session\Session;
use Joomla\Session\SessionInterface;

const _JEXEC = 1;

const ARS_E2E_PROBE_SECRET = '##SECRET##';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!hash_equals(ARS_E2E_PROBE_SECRET, (string) ($_GET['secret'] ?? '')))
{
	http_response_code(403);
	echo json_encode(['error' => 'Bad or missing probe secret']);

	exit;
}

if (file_exists(__DIR__ . '/defines.php'))
{
	include_once __DIR__ . '/defines.php';
}

require_once __DIR__ . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$container = Factory::getContainer();

// Same aliasing as the site's own includes/app.php: without it the container hands back the wrong
// session backend and the probe would report every surfer as a guest.
$container->alias('session.web', 'session.web.site')
	->alias('session', 'session.web.site')
	->alias('JSession', 'session.web.site')
	->alias(\Joomla\CMS\Session\Session::class, 'session.web.site')
	->alias(Session::class, 'session.web.site')
	->alias(SessionInterface::class, 'session.web.site');

/** @var SiteApplication $app */
$app = $container->get(SiteApplication::class);

Factory::$application = $app;

// The extension namespaces are registered by createExtensionNamespaceMap(), which CMSApplication
// only calls from initialiseApp() — i.e. from inside execute(), which this probe never reaches.
// See the same note in e2e-provision.php.
$app->createExtensionNamespaceMap();

$session = $app->getSession();

if (!$session->isStarted())
{
	$session->start();
}

$sessionUser = $session->get('user');
$identity    = $sessionUser instanceof User ? $sessionUser : null;

$app->loadIdentity($identity);

$user = $app->getIdentity();

$result = [
	'joomla'     => JVERSION,
	'id'         => (int) $user->id,
	'username'   => (string) $user->username,
	'name'       => (string) $user->name,
	'email'      => (string) $user->email,
	'guest'      => (bool) $user->guest,
	'groups'     => array_values(array_map('intval', $user->getAuthorisedGroups())),
	'viewLevels' => array_values(array_map('intval', $user->getAuthorisedViewLevels())),
	'authorise'  => [],
];

// Optional authorisation matrix: ?assets=com_ars,com_ars.category.7&actions=core.manage,core.create
$assets  = array_filter(array_map('trim', explode(',', (string) ($_GET['assets'] ?? ''))));
$actions = array_filter(array_map('trim', explode(',', (string) ($_GET['actions'] ?? ''))));

foreach ($assets as $asset)
{
	foreach ($actions as $action)
	{
		$result['authorise'][$asset][$action] = (bool) $user->authorise($action, $asset);
	}
}

echo json_encode($result);
