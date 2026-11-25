<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use Akeeba\ARS\IntegrationTest\Engine\Response;
use Akeeba\ARS\IntegrationTest\Engine\Surfer;
use Akeeba\ARS\IntegrationTest\SiteProvisioner;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * The "Access Web Services" (core.api) permission of Joomla 5.4.11+ / 6.2.1+ (gh-256).
 *
 * From those versions Joomla's ApiDispatcher refuses every non-public API route to a user who does not hold core.api
 * on the routed component — com_ars for every ARS route — before any ARS code runs. On older Joomla versions there
 * is no such permission and ARS's own privileges alone decide.
 *
 * The `noApiManager` account holds every ARS privilege (core.manage, core.create, core.edit, core.edit.state,
 * core.delete) but not core.api: an ARS manager on a fresh install of a Joomla version which has core.api. The other
 * API accounts hold core.api on com_ars, so their tests keep exercising ARS's own authorisation.
 *
 * @since 7.5.3
 */
#[Group('api')]
#[Group('authorisation')]
class WebServicesAccessTest extends AbstractE2ETestCase
{
	/**
	 * Guard: the account without core.api really does authenticate against the API.
	 *
	 * Without this, a 403 below could just as well be an authentication failure (wrong token, user group missing
	 * from plg_user_token's allowedUserGroups, no core.login.api) and the tests would prove nothing. A token which
	 * fails to authenticate gets a 401 from Joomla's ApiApplication, never a 403.
	 *
	 * @since 7.5.3
	 */
	public function testAccountWithoutWebServicesPermissionAuthenticates(): void
	{
		$forged = $this->api('GET', 'v1/ars/categories', base64_encode('sha256:999999:' . str_repeat('0', 64)));

		$this->assertStatus(401, $forged, 'A forged token was not refused with a 401, so 401 vs 403 tells us nothing.');

		$response = $this->api('GET', 'v1/ars/categories', static::$fixtures->apiToken('noApiManager'));

		$this->assertNotSame(
			401,
			$response->code,
			'The noApiManager token did not authenticate. Check its group in plg_user_token\'s allowedUserGroups and '
			. 'its core.login.api grant: every assertion in this class depends on it.'
		);
	}

	/**
	 * Every ARS route refuses a manager without core.api where Joomla has core.api, and serves them where it does
	 * not.
	 *
	 * @since 7.5.3
	 */
	#[DataProvider('endpointProvider')]
	public function testEndpointsHonourWebServicesPermission(string $path): void
	{
		$response = $this->api('GET', $path, static::$fixtures->apiToken('noApiManager'));

		if (static::$fixtures->hasWebServicesPermission())
		{
			$this->assertStatus(
				403,
				$response,
				sprintf(
					'Joomla %s has the "Access Web Services" permission, yet a manager who lacks it on ARS was served.',
					static::$fixtures->joomlaVersion()
				)
			);

			return;
		}

		$this->assertStatus(
			200,
			$response,
			sprintf(
				'Joomla %s has no "Access Web Services" permission, so ARS\'s own privileges should have let this '
				. 'manager through.',
				static::$fixtures->joomlaVersion()
			)
		);
	}

	/**
	 * The same manager privileges plus core.api are served on every Joomla version: this is the upgraded site, or
	 * a fresh one where the administrator granted "Access Web Services" on ARS.
	 *
	 * @since 7.5.3
	 */
	#[DataProvider('endpointProvider')]
	public function testEndpointsServeManagerWithWebServicesPermission(string $path): void
	{
		$response = $this->api('GET', $path, static::$fixtures->apiToken('manager'));

		$this->assertStatus(200, $response, 'A manager holding "Access Web Services" on ARS was refused.');
	}

	/**
	 * A write is refused, and has no effect, for a manager without core.api where Joomla has core.api.
	 *
	 * @since 7.5.3
	 */
	public function testWritesHonourWebServicesPermission(): void
	{
		$version  = 'E2E-WRITE-TEST noapi ' . uniqid();
		$response = $this->api(
			'POST',
			'v1/ars/releases',
			static::$fixtures->apiToken('noApiManager'),
			[
				'category_id' => static::$fixtures->categoryId('public'),
				'version'     => $version,
				'alias'       => 'e2e-write-test-noapi-' . uniqid(),
				'maturity'    => 'stable',
				'published'   => 1,
			]
		);

		$created = (int) $this->db()->value('SELECT COUNT(*) FROM `#__ars_releases` WHERE version = ?', [$version]);

		$this->db()->query('DELETE FROM `#__ars_releases` WHERE version = ?', [$version]);

		if (static::$fixtures->hasWebServicesPermission())
		{
			$this->assertStatus(403, $response, 'A manager without "Access Web Services" on ARS created a release.');
			$this->assertSame(0, $created, 'A release row was created despite the 403.');

			return;
		}

		$this->assertStatus(200, $response, 'A manager could not create a release on a Joomla without core.api.');
		$this->assertSame(1, $created, 'The release was not created.');
	}

	/**
	 * An administrator can see, and so grant, "Access Web Services" in ARS's Options, Permissions tab, on every
	 * Joomla version.
	 *
	 * The unit suite proves access.xml declares core.api and that its language keys exist. Only this proves that
	 * Joomla loads them when it renders the Permissions tab: com_config loads com_ars.ini, not com_ars.sys.ini, for a
	 * single component's Options.
	 *
	 * @since 7.5.3
	 */
	public function testPermissionsTabShowsAccessWebServices(): void
	{
		$response = $this->superUser()->get(
			'administrator/index.php?' . http_build_query(
				['option' => 'com_config', 'view' => 'component', 'component' => 'com_ars']
			)
		);

		$this->assertStatus(200, $response, 'Could not open ARS\'s Options page.');
		$this->assertBodyNotContains('COM_ARS_ACCESS_API_LBL', $response, 'The Permissions tab shows the raw language key.');
		$this->assertBodyNotContains('COM_ARS_ACCESS_API_DESC', $response, 'The Permissions tab shows the raw language key.');
		$this->assertBodyContains('Access Web Services', $response, 'The Permissions tab does not offer "Access Web Services".');
	}

	/**
	 * Every ARS JSON:API resource, as a list and as a single record.
	 *
	 * @return  array<string, array{string}>
	 * @since   7.5.3
	 */
	public static function endpointProvider(): array
	{
		$fixtures = SiteProvisioner::getInstance();

		return [
			'categories list'        => ['v1/ars/categories'],
			'releases list'          => ['v1/ars/releases'],
			'items list'             => ['v1/ars/items'],
			'autodescriptions list'  => ['v1/ars/autodescriptions'],
			'dlidlabels list'        => ['v1/ars/dlidlabels'],
			'environments list'      => ['v1/ars/environments'],
			'updatestreams list'     => ['v1/ars/updatestreams'],
			'single category'        => ['v1/ars/categories/' . $fixtures->categoryId('public')],
			'single release'         => ['v1/ars/releases/' . $fixtures->releaseId('restrictedStable')],
			'single item'            => ['v1/ars/items/' . $fixtures->itemId('restrictedFile')],
			'single autodescription' => ['v1/ars/autodescriptions/' . $fixtures->autoDescriptionId('public')],
			'single dlidlabel'       => ['v1/ars/dlidlabels/' . $fixtures->dlidLabelId('otherPrimary')],
			'single environment'     => ['v1/ars/environments/' . $fixtures->environmentId('php84')],
			'single updatestream'    => ['v1/ars/updatestreams/' . $fixtures->updateStreamId('restrictedStream')],
		];
	}

	/**
	 * A raw JSON:API request: a fresh Surfer, no cookies, the vnd.api+json Accept header, and the token on
	 * X-Joomla-Token.
	 *
	 * @param   string      $verb   HTTP verb.
	 * @param   string      $path   Path under api/index.php, e.g. 'v1/ars/releases'.
	 * @param   string      $token  A Joomla API token.
	 * @param   array|null  $body   Request body, JSON-encoded. Null for no body.
	 *
	 * @return  Response
	 * @since   7.5.3
	 */
	private function api(string $verb, string $path, string $token, ?array $body = null): Response
	{
		$surfer  = new Surfer(static::$config->getSiteUrl());
		$headers = ['Accept' => 'application/vnd.api+json', 'X-Joomla-Token' => $token];

		if ($body !== null)
		{
			$headers['Content-Type'] = 'application/json';

			return $surfer->request($verb, $this->apiUrl($path), json_encode($body), $headers);
		}

		return $surfer->request($verb, $this->apiUrl($path), null, $headers);
	}
}
