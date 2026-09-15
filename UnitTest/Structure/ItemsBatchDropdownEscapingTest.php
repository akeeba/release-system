<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ARS\UnitTest\Structure;

defined('_JEXEC') or die;

use PHPUnit\Framework\TestCase;

/**
 * Regression coverage for the L7 fix: the Items batch dropdown passed `option.key.toHtml => false` and
 * `option.text.toHtml => false` to `HTMLHelper::_('select.groupedlist', ...)`, opting OUT of Joomla
 * core's normal option-text escaping for the release `version` field (a free-text column, editable by
 * any category-scoped Editor) with no compensating control. See `security.md`.
 *
 * Confusingly, `toHtml` here does not mean "output as raw HTML" — it means "HTML-escape this value
 * before output" — so `false` is the dangerous setting, not the safe one.
 *
 * This is a plain Joomla admin template with no runtime seam (the escaping itself happens inside
 * `HTMLHelper`, trusted framework code this project does not re-test) — the only meaningful regression
 * test is asserting the template never again opts out of it.
 */
class ItemsBatchDropdownEscapingTest extends TestCase
{
	private function templatePath(): string
	{
		return \dirname(__DIR__, 2) . '/component/backend/tmpl/items/default_batch_body.php';
	}

	public function testTheTemplateStillExists(): void
	{
		self::assertFileExists($this->templatePath());
	}

	public function testTheBatchDropdownDoesNotDisableOptionEscaping(): void
	{
		$contents = file_get_contents($this->templatePath());

		self::assertStringNotContainsString(
			'option.key.toHtml',
			$contents,
			"default_batch_body.php must not opt out of HTMLHelper's option escaping (see security.md, L7)."
		);
		self::assertStringNotContainsString(
			'option.text.toHtml',
			$contents,
			"default_batch_body.php must not opt out of HTMLHelper's option escaping (see security.md, L7)."
		);
	}
}
