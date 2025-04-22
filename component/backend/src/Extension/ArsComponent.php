<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Extension;

defined('_JEXEC') || die;

use Akeeba\Component\ARS\Administrator\CustomCategory\ARSPseudoCategory;
use Akeeba\Component\ARS\Administrator\CustomCategory\ARSReleaseNodeProvider;
use Akeeba\Component\ARS\Administrator\Service\Html\AkeebaReleaseSystem;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\CMS\Categories\CategoryServiceInterface;
use Joomla\CMS\Categories\CategoryServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsServiceInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Tag\TagServiceInterface;
use Joomla\CMS\Tag\TagServiceTrait;
use Joomla\Component\Categories\Administrator\Field\CategoryeditField;
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Psr\Container\ContainerInterface;

class ArsComponent extends MVCComponent implements
	BootableExtensionInterface, CategoryServiceInterface, RouterServiceInterface,
	TagServiceInterface, FieldsServiceInterface
{
	use HTMLRegistryAwareTrait;
	use RouterServiceTrait;
	use CategoryServiceTrait
	{
		CategoryServiceTrait::getTableNameForSection insteadof TagServiceTrait;
		CategoryServiceTrait::getStateColumnForSection insteadof TagServiceTrait;
	}
	use TagServiceTrait;

	private static $hasRegisteredHandler = false;

	public function boot(ContainerInterface $container)
	{
		$this->getRegistry()->register('ars', new AkeebaReleaseSystem());
	}

	/**
	 * Magically use ARS categories (instead of com_categories) in the Fields user interface.
	 *
	 * DO NOT DO THIS IN YOUR OWN SOFTWARE. I am having Joomla do things it's not supposed to even support.
	 *
	 * “The Dark Side of the Force is a pathway to many abilities some consider to be unnatural.”
	 *    — Darth Sidious
	 *
	 * @return  void
	 * @since   7.4.0
	 */
	private function magicFieldsUsingARSCategories($section): void
	{
		// This trick allows us to only register our event handler once.
		if (self::$hasRegisteredHandler)
		{
			return;
		}

		self::$hasRegisteredHandler = true;

		// Register an event handler, as if there was a plugin.
		/** @var Dispatcher $dispatcher */
		$dispatcher = Factory::getContainer()->get(DispatcherInterface::class);
		$dispatcher->addListener(
			'onContentPrepareForm',
			function (Event $event) use ($section)
			{
				// Make sure this really is the form preparation event.
				if (!$event instanceof PrepareFormEvent)
				{
					return;
				}

				// Make sure we have a form for ARS fields
				$form = $event->getArgument('subject');

				if (!$form instanceof Form || !str_starts_with($form->getName(), 'com_fields.field.com_ars.'))
				{
					return;
				}

				// Get all known ARS categories
				$childrenCategories = (new ARSPseudoCategory([], $section))->get()->getChildren();

				// Add the ARS categories to the assigned_cat_ids field in Joomla's Fields component.
				/** @var CategoryeditField $catField */
				$catField = $form->getField('assigned_cat_ids');

				foreach($childrenCategories as $category)
				{
					$catField->addOption(
						htmlentities($category->title),
						[
							'value' => $category->id,
						]
					);
				}
			}
		);
	}

	/**
	 * Returns a CategoryInterface object. Used for custom fields management.
	 *
	 * The CategoryInterface object implements a single method, get(), which returns a CategoryNode. This is used to
	 * attach fields to a category.
	 *
	 * @param   array   $options
	 * @param   string  $section
	 *
	 * @return  CategoryInterface
	 * @since   7.4.0
	 */
	public function getCategory(array $options = [], $section = ''): CategoryInterface
	{
		// Force Joomla's Fields component to show ARS' categories, even though we're not using com_categories.
		$this->magicFieldsUsingARSCategories($section);

		return new ARSPseudoCategory($options, $section);
	}

	/**
	 * Returns valid contexts.
	 *
	 * Used for custom fields.
	 *
	 * @return  array
	 *
	 * @since   7.4.0
	 */
	public function getContexts(): array
	{
		Factory::getApplication()->getLanguage()->load('com_ars', JPATH_ADMINISTRATOR);

		return [
			'com_ars.release'  => Text::_('COM_ARS_TITLE_RELEASES'),
			'com_ars.category' => Text::_('COM_ARS_TITLE_CATEGORIES'),
		];
	}

	/**
	 * If the section is valid it returns it, otherwise returns null.
	 *
	 * Used for custom fields.
	 *
	 * @param   string  $section  The section to get the mapping for
	 * @param   object  $item     The item
	 *
	 * @return  string|null  The new section
	 *
	 * @since   7.4.0
	 */
	public function validateSection($section, $item = null)
	{
		$validSections = array_map(
			fn(string $x) => explode('.', $x)[1],
			array_keys($this->getContexts())
		);

		return in_array(strtolower($section), $validSections) ? $section : null;
	}

	/** @inheritdoc */
	protected function getStateColumnForSection(?string $section = null)
	{
		return 'published';
	}

	/** @inheritdoc */
	protected function getTableNameForSection(?string $section = null)
	{
		return match (strtolower($section))
		{
			'release' => 'ats_releases',
			'category' => 'ats_categories',
		};
	}
}