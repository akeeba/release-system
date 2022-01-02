<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */


namespace Akeeba\Component\ARS\Administrator\Mixin;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Language\Text;
use Throwable;

/**
 * Adds support for loading any template or layout, of any view of the current component, in an HTML view.
 *
 * @since  9.0.0
 */
trait LoadAnyTemplate
{
	/**
	 * Load any view template of the current component.
	 *
	 * You can this method using different $viewTemplate formats:
	 * * `layout` Loads `layout.php` of the current view.
	 * * `layout_subtemplate` Loads `layout_subtemplate.php` of the current view.
	 * * `_subtemplate` Equivalent to `$this->loadTemplate('subtemplate')`. DISCOURAGED!
	 * * `viewName/layout` Loads the `layout.php` file of the view `viewName`.
	 * * `viewName/layout_subtemplate` Loads the `layout_subtemplate.php` file of the view `viewName`.
	 *
	 * There are other silly ways to call this method which make no sense. Please don't.
	 *
	 * @param   string  $viewTemplate       What to load in the format 'view/layout_subtemplate'
	 * @param   bool    $fallbackToDefault  Should I fall back to the default layout?
	 * @param   array   $extraVariables     Extra variables to introduce in the view template's scope
	 *
	 * @return  string
	 * @throws  Throwable
	 */
	public function loadAnyTemplate(string $viewTemplate, bool $fallbackToDefault = true, array $extraVariables = []): string
	{
		// We were only given a layout. Prefix it with the view name.
		if (strpos($viewTemplate, '/') === false)
		{
			$viewTemplate = $this->getName() . '/' . $viewTemplate;
		}

		// Convert the 'view/template' to separate view and template
		[$view, $layout] = explode('/', $viewTemplate, 2);

		// Make sure I have a valid view
		$view = $view ?: $this->getName();

		// Start with no subtemplate
		$tpl = null;

		// Does the layout also have a subtemplate (e.g. 'layout_subtemplate')?
		$layoutParts = explode('_', $layout, 2);

		if (count($layoutParts) === 2)
		{
			// This makes sure that a bare '_subtemplate' results in something meaningful.
			$layout = $layoutParts[0] ?: $this->getLayout();
			// An empty tpl is squashed to null (Joomla can't have empty subtemplates!)
			$tpl = $layoutParts[1] ?: null;
		}

		// Store the current view template paths and layout name
		$previousTemplatePaths = $this->_path['template'];
		$previousLayout        = $this->getLayout();

		// Create new view template paths
		$newTemplatePaths = array_map(function ($path) use ($view) {
			$path      = rtrim($path, DIRECTORY_SEPARATOR);
			$lastSlash = strrpos($path, DIRECTORY_SEPARATOR);

			return substr($path, 0, $lastSlash) . DIRECTORY_SEPARATOR . strtolower($view) . DIRECTORY_SEPARATOR;
		}, $previousTemplatePaths);

		// Set up the default return HTML and thrown exception
		$ret       = '';
		$exception = null;

		try
		{
			// Apply the new view template paths
			$this->_path['template'] = $newTemplatePaths;
			// Apply the new base layout
			$this->setLayout($layout);
			// Get the subtemplate (null here means load the base layout file)
			$ret = $this->loadTemplate($tpl, false, $extraVariables);
		}
		catch (Throwable $e)
		{
			if (defined('AKEEBADEBUG'))
			{
				$id  = ApplicationHelper::getHash(microtime());
				$ret = <<< HTML
<div class="border border-3 border-danger bg-light">
	<h3 class="test-danger">
		{$e->getMessage()}
	</h3>
	<p>
		<a href="#$id"
			class="btn btn-link" 
			data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="$id">
			Debug backtrace		
		</a>	
	</p>
	<pre class="collapse" id="$id">{$e->getFile()}:{$e->getLine()}
{$e->getTraceAsString()}</pre>
</div>
HTML;

			}
			else
			{
				// An error occurred. Cache it so that the finally block runs first.
				$exception = $e;
			}

		}
		finally
		{
			// Undo the custom template paths and layout
			$this->_path['template'] = $previousTemplatePaths;
			$this->setLayout($previousLayout);
		}

		// If an error had occurred, rethrow the exception and terminate early.
		if (!is_null($exception))
		{
			throw $exception;
		}

		// Return the HTML of the parsed template.
		return $ret;
	}

	/**
	 * Load a template file -- first look in the templates folder for an override
	 *
	 * Copied from Joomla 4.0. Added the $fallbackToDefault and $extraVariables options.
	 *
	 * @param   null   $tpl                The name of the template source file; automatically searches the template
	 *                                     paths and compiles as needed.
	 * @param   bool   $fallbackToDefault  Should I fall back to the default layout?
	 * @param   array  $extraVariables     Extra variables to introduce in the view template's scope
	 *
	 * @return  string  The output of the the template script.
	 *
	 * @throws Exception
	 * @since   9.0.0
	 */
	public function loadTemplate($tpl = null, bool $fallbackToDefault = true, array $extraVariables = []): string
	{
		// Clear prior output
		$this->_output = null;

		$template       = Factory::getApplication()->getTemplate(true);
		$layout         = $this->getLayout();
		$layoutTemplate = $this->getLayoutTemplate();

		// Create the template file name based on the layout
		$file = isset($tpl) ? $layout . '_' . $tpl : $layout;

		// Clean the file name
		$file = preg_replace('/[^A-Z0-9_\.-]/i', '', $file);
		$tpl  = isset($tpl) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $tpl) : $tpl;

		// Load the language file for the template
		$lang = Factory::getLanguage();
		$lang->load('tpl_' . $template->template, JPATH_BASE)
		|| $lang->load('tpl_' . $template->parent, JPATH_THEMES . '/' . $template->parent)
		|| $lang->load('tpl_' . $template->template, JPATH_THEMES . '/' . $template->template);

		// Change the template folder if alternative layout is in different template
		if (isset($layoutTemplate) && $layoutTemplate !== '_' && $layoutTemplate != $template->template)
		{
			$this->_path['template'] = str_replace(
				JPATH_THEMES . DIRECTORY_SEPARATOR . $template->template,
				JPATH_THEMES . DIRECTORY_SEPARATOR . $layoutTemplate,
				$this->_path['template']
			);
		}

		// Load the template script
		$filetofind      = $this->_createFileName('template', ['name' => $file]);
		$this->_template = Path::find($this->_path['template'], $filetofind);

		// If alternate layout can't be found, fall back to default layout
		if (($this->_template === false) && $fallbackToDefault)
		{
			$filetofind      = $this->_createFileName('', ['name' => 'default' . (isset($tpl) ? '_' . $tpl : $tpl)]);
			$this->_template = Path::find($this->_path['template'], $filetofind);
		}

		if ($this->_template != false)
		{
			// Unset so as not to introduce into template scope
			unset($tpl, $file);

			// Never allow a 'this' property
			if (isset($this->this))
			{
				unset($this->this);
			}

			// Start capturing output into a buffer
			ob_start();

			empty($extraVariables) || extract($extraVariables);

			// Include the requested template filename in the local scope
			// (this will execute the view logic).
			include $this->_template;

			// Done with the requested template; get the buffer and
			// clear it.
			$this->_output = ob_get_contents();
			ob_end_clean();

			return $this->_output;
		}

		throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_LAYOUTFILE_NOT_FOUND', $file), 500);
	}

}