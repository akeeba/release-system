<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\IntegrationTest\Tests;

defined('_JEXEC') or die;

use Akeeba\ARS\IntegrationTest\AbstractE2ETestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * The ARS control panel.
 *
 * `view=controlpanel` is not a view in the ordinary sense. Its default task, `main`, performs
 * housekeeping WRITES — `ControlpanelModel::saveMagicVariables()` and
 * `UpgradeModel::adoptMyExtensions()`, the latter reassigning rows in `#__extensions` — and then
 * redirects to the com_cpanel dashboard. It never renders anything itself.
 *
 * That makes it worth two assertions the rest of the suite does not cover: that a task doing
 * `#__extensions` surgery is gated on `core.manage`, and that it survives being run for the first
 * time on a site where it has never run before.
 *
 * @since 7.5.0
 */
#[Group('authorisation')]
class ControlPanelTest extends AbstractE2ETestCase
{
	/**
	 * The housekeeping task completes for a Super User and redirects to the ARS dashboard.
	 *
	 * Deliberately does not follow the redirect: this asserts that `main` completed, not that
	 * com_cpanel renders.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testHousekeepingCompletesForASuperUser(): void
	{
		$admin    = $this->superUser();
		$response = $admin->get($this->adminUrl(['view' => 'controlpanel']));

		$this->assertTrue(
			$response->isRedirect(),
			"The control panel task did not redirect, which means it did not complete.\n" . $response->summary()
		);

		$this->assertStringContainsString(
			'com_cpanel',
			(string) $response->getLocation(),
			"The control panel did not redirect to the com_cpanel dashboard.\n" . $response->summary()
		);
	}

	/**
	 * Running it twice is harmless.
	 *
	 * `adoptMyExtensions()` rewrites `#__extensions` rows. If it were not idempotent, the second run
	 * would be the one that broke, and it would break on a site that had merely been visited twice.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testHousekeepingIsIdempotent(): void
	{
		$admin = $this->superUser();

		$first  = $admin->get($this->adminUrl(['view' => 'controlpanel']));
		$second = $admin->get($this->adminUrl(['view' => 'controlpanel']));

		$this->assertTrue($first->isRedirect(), "First run did not complete.\n" . $first->summary());
		$this->assertTrue($second->isRedirect(), "Second run did not complete.\n" . $second->summary());

		// And the package's own extension rows are still intact afterwards.
		$rows = (int) $this->db()->value(
			'SELECT COUNT(*) FROM `#__extensions` WHERE `element` = ? AND `type` = ?',
			['com_ars', 'component']
		);

		$this->assertSame(1, $rows, 'The housekeeping task left com_ars with the wrong number of extension rows.');
	}

	/**
	 * An account without core.manage cannot run the housekeeping task.
	 *
	 * The gate is explicit in ControlpanelController::main(), with a comment saying why: the task
	 * performs housekeeping writes. Without it, any back-end user could trigger `#__extensions`
	 * surgery.
	 *
	 * @return  void
	 * @since   7.5.0
	 */
	public function testHousekeepingIsRefusedWithoutCoreManage(): void
	{
		// `catManager` holds core.manage, so it is not the right actor here; `subscriber` holds no
		// back-end privilege at all and cannot log into the back-end. The meaningful probe is
		// therefore a guest, which is also the case that matters: an unauthenticated request must
		// not be able to trigger writes.
		$guest    = $this->guest();
		$response = $guest->get($this->adminUrl(['view' => 'controlpanel']));

		$this->assertRefused(
			$guest,
			$response,
			'A guest was able to reach the control panel housekeeping task, which writes to #__extensions.'
		);
	}
}
