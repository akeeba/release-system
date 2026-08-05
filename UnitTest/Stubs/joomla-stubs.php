<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Minimal Joomla symbol stubs for the unit test suite.
 *
 * ARS classes are Joomla classes: they extend `Table`, `ListModel`, `CMSPlugin` and friends, and
 * `use` traits that in turn `use` Joomla traits. PHP resolves all of that at class-definition time,
 * so a class cannot even be *loaded* without those symbols existing. These stubs make loading
 * possible; they do not make the CMS available, and they are not meant to.
 *
 * Three rules keep this file honest.
 *
 * 1. **Every declaration is guarded with `class_exists(..., false)`** (or the interface/trait
 *    equivalent). If a real Joomla is on the autoloader, the real class always wins.
 * 2. **A stub carries no behaviour a test could mistake for the real thing.** Where a method has to
 *    return something, it returns the most inert value that lets the caller proceed, and the
 *    docblock says so. If a test needs real behaviour, that behaviour belongs behind a seam the
 *    test overrides — not in here.
 * 3. **Where fidelity genuinely matters, the real implementation is copied verbatim** and the
 *    provenance is stated. There are exactly two such cases: `StringHelper::increment()` and
 *    `ArrayHelper::toInteger()`, both of which ARS's own logic is built directly on top of, so a
 *    hand-waved stub would mean the test asserted the stub rather than ARS.
 *
 * Keep this file short. A growing stub set is a signal that the class under test wants a seam, or
 * that it belongs in the end-to-end suite instead. See tests/README.md.
 */

namespace {
	defined('_JEXEC') or die;

	/**
	 * Joomla's global configuration class. `CacheCleaner::getAppConfigParam()` falls back to
	 * `new \JConfig` as its last resort, and that branch has to be reachable in a test.
	 */
	if (!class_exists(JConfig::class, false))
	{
		#[\AllowDynamicProperties]
		class JConfig
		{
		}
	}
}

namespace Joomla\Registry {
	/**
	 * Stand-in for Joomla\Registry\Registry: a flat key/value store with the get()/set()/exists()/
	 * toArray() surface ARS uses for component parameters and list state (e.g. 'filter.search',
	 * 'list.ordering'). Keys are opaque strings — no dot-notation traversal is performed, which
	 * matches how ARS reads and writes state keys.
	 */
	if (!class_exists(Registry::class, false))
	{
		class Registry
		{
			/** @var array<string,mixed> */
			private $data = [];

			public function __construct($data = null)
			{
				if (\is_array($data))
				{
					$this->data = $data;
				}
				elseif (\is_object($data))
				{
					$this->data = get_object_vars($data);
				}
				elseif (\is_string($data) && $data !== '')
				{
					$decoded    = json_decode($data, true);
					$this->data = \is_array($decoded) ? $decoded : [];
				}
			}

			public function get($key, $default = null)
			{
				return \array_key_exists($key, $this->data) && $this->data[$key] !== null
					? $this->data[$key]
					: $default;
			}

			public function set($key, $value = null)
			{
				$this->data[$key] = $value;

				return $value;
			}

			public function exists($key)
			{
				return \array_key_exists($key, $this->data);
			}

			public function toArray()
			{
				return $this->data;
			}
		}
	}
}

namespace Joomla\String {
	/**
	 * Stand-in for Joomla\String\StringHelper.
	 *
	 * `increment()` is copied VERBATIM from Joomla's own implementation
	 * (libraries/vendor/joomla/string/src/StringHelper.php), including its `$incrementStyles` table.
	 * ARS's `ModelCopyTrait::generateNewTitle()` is built directly on it, so the alias-incrementing
	 * behaviour under test is Joomla's, not something invented here.
	 */
	if (!class_exists(StringHelper::class, false))
	{
		abstract class StringHelper
		{
			/** @var array Copied verbatim from Joomla\String\StringHelper */
			protected static $incrementStyles = [
				'dash'    => [
					'#-(\d+)$#',
					'-%d',
				],
				'default' => [
					['#\((\d+)\)$#', '#\(\d+\)$#'],
					[' (%d)', '(%d)'],
				],
			];

			public static function increment($string, $style = 'default', $n = 0)
			{
				$styleSpec = static::$incrementStyles[$style] ?? static::$incrementStyles['default'];

				// Regular expression search and replace patterns.
				if (\is_array($styleSpec[0]))
				{
					$rxSearch  = $styleSpec[0][0];
					$rxReplace = $styleSpec[0][1];
				}
				else
				{
					$rxSearch = $rxReplace = $styleSpec[0];
				}

				// New and old (existing) sprintf formats.
				if (\is_array($styleSpec[1]))
				{
					$newFormat = $styleSpec[1][0];
					$oldFormat = $styleSpec[1][1];
				}
				else
				{
					$newFormat = $oldFormat = $styleSpec[1];
				}

				// Check if we are incrementing an existing pattern, or appending a new one.
				if (preg_match($rxSearch, $string, $matches))
				{
					$n      = empty($n) ? (1 + (int) $matches[1]) : $n;
					$string = preg_replace($rxReplace, sprintf($oldFormat, $n), $string);
				}
				else
				{
					$n      = empty($n) ? 2 : $n;
					$string .= sprintf($newFormat, $n);
				}

				return $string;
			}

			public static function strtolower($string)
			{
				return function_exists('mb_strtolower') ? mb_strtolower($string, 'UTF-8') : strtolower($string);
			}

			public static function strtoupper($string)
			{
				return function_exists('mb_strtoupper') ? mb_strtoupper($string, 'UTF-8') : strtoupper($string);
			}

			public static function trim($string, $charlist = false)
			{
				return $charlist === false ? trim($string) : trim($string, $charlist);
			}
		}
	}
}

namespace Joomla\Utilities {
	/**
	 * Stand-in for Joomla\Utilities\ArrayHelper. `toInteger()` is copied VERBATIM from Joomla's own
	 * implementation, because the module helper under test delegates its whole normalisation
	 * behaviour to it.
	 */
	if (!class_exists(ArrayHelper::class, false))
	{
		final class ArrayHelper
		{
			public static function toInteger($array, $default = null)
			{
				if (\is_array($array))
				{
					return array_map('intval', $array);
				}

				if ($default === null)
				{
					return [];
				}

				if (\is_array($default))
				{
					return static::toInteger($default, null);
				}

				return [(int) $default];
			}
		}
	}
}

namespace Joomla\Database {
	if (!interface_exists(DatabaseInterface::class, false))
	{
		interface DatabaseInterface
		{
		}
	}

	if (!interface_exists(DatabaseAwareInterface::class, false))
	{
		interface DatabaseAwareInterface
		{
		}
	}

	if (!trait_exists(DatabaseAwareTrait::class, false))
	{
		trait DatabaseAwareTrait
		{
			/** @var mixed */
			private $__stubDatabase;

			public function setDatabase($db)
			{
				$this->__stubDatabase = $db;
			}

			protected function getDatabase()
			{
				return $this->__stubDatabase;
			}
		}
	}

	/**
	 * A concrete stand-in only so type declarations resolve. Tests hand ARS the
	 * {@see \Akeeba\ARS\UnitTest\Stubs\RecordingDatabase} instead, which is what actually records
	 * the SQL a model builds.
	 */
	if (!class_exists(DatabaseDriver::class, false))
	{
		abstract class DatabaseDriver implements DatabaseInterface
		{
		}
	}

	if (!interface_exists(QueryInterface::class, false))
	{
		interface QueryInterface
		{
		}
	}

	if (!class_exists(DatabaseQuery::class, false))
	{
		abstract class DatabaseQuery implements QueryInterface
		{
		}
	}

	if (!class_exists(ParameterType::class, false))
	{
		class ParameterType
		{
			public const BOOLEAN = 'boolean';

			public const INTEGER = 'integer';

			public const LARGE_OBJECT = 'large_object';

			public const NULL = 'null';

			public const STRING = 'string';
		}
	}
}

namespace Joomla\Event {
	if (!interface_exists(DispatcherInterface::class, false))
	{
		interface DispatcherInterface
		{
		}
	}

	if (!interface_exists(SubscriberInterface::class, false))
	{
		interface SubscriberInterface
		{
			public static function getSubscribedEvents(): array;
		}
	}

	if (!class_exists(Event::class, false))
	{
		#[\AllowDynamicProperties]
		class Event
		{
			/** @var string */
			protected $name;

			/** @var array */
			protected $arguments;

			public function __construct($name, array $arguments = [])
			{
				$this->name      = $name;
				$this->arguments = $arguments;
			}

			public function getName()
			{
				return $this->name;
			}

			public function getArgument($name, $default = null)
			{
				return $this->arguments[$name] ?? $default;
			}

			public function setArgument($name, $value)
			{
				$this->arguments[$name] = $value;

				return $this;
			}
		}
	}
}

namespace Joomla\Application {
	if (!interface_exists(ConfigurationAwareApplicationInterface::class, false))
	{
		interface ConfigurationAwareApplicationInterface
		{
			public function get($key, $default = null);
		}
	}

	if (!class_exists(AbstractApplication::class, false))
	{
		abstract class AbstractApplication
		{
			public function get($key, $default = null)
			{
				return $default;
			}
		}
	}
}

namespace Joomla\CMS\Language {
	/**
	 * Stand-in for Joomla\CMS\Language\Text. `_()` echoes the key back unresolved, which is what a
	 * test wants: assertions then pin the language KEY, which is stable, rather than the English
	 * wording, which is not. Populate {@see Text::$strings} when a test needs a specific
	 * translation.
	 */
	if (!class_exists(Text::class, false))
	{
		class Text
		{
			/** @var array<string,string> Optional key => translation map. */
			public static $strings = [];

			public static function _($string, $jsSafe = false, $interpretBackSlashes = true, $script = false)
			{
				return self::$strings[$string] ?? $string;
			}

			public static function sprintf($string, ...$args)
			{
				$format = self::$strings[$string] ?? $string;

				// Only format when the resolved string actually carries placeholders, so a bare key
				// passed with extra arguments does not trigger an ArgumentCountError.
				if (strpos($format, '%') === false)
				{
					return $format;
				}

				return vsprintf($format, $args);
			}

			public static function plural($string, $n, ...$args)
			{
				return self::$strings[$string] ?? $string;
			}
		}
	}

	if (!class_exists(Multilanguage::class, false))
	{
		class Multilanguage
		{
			/** @var bool Flip this in a test to exercise the multilingual branches. */
			public static $enabled = false;

			public static function isEnabled()
			{
				return self::$enabled;
			}
		}
	}
}

namespace Joomla\CMS\Component {
	/**
	 * Stand-in for ComponentHelper. `getParams()` returns an empty Registry by default; assign
	 * {@see ComponentHelper::$params} in a test to hand ARS a specific parameter set.
	 */
	if (!class_exists(ComponentHelper::class, false))
	{
		class ComponentHelper
		{
			/** @var array<string,\Joomla\Registry\Registry> option => params */
			public static $params = [];

			public static function getParams($option = null, $strict = false)
			{
				return self::$params[$option] ?? new \Joomla\Registry\Registry();
			}

			public static function getComponent($option, $strict = false)
			{
				return null;
			}

			public static function isEnabled($option, $strict = false)
			{
				return false;
			}
		}
	}
}

namespace Joomla\CMS\Uri {
	/**
	 * Stand-in for Joomla\CMS\Uri\Uri, carrying only what ARS's URL handling touches.
	 * {@see Uri::$rootUri} and {@see Uri::$baseUri} are settable so a test can pin the site root.
	 */
	if (!class_exists(Uri::class, false))
	{
		class Uri
		{
			/** @var string The value root() returns. */
			public static $rootUri = 'http://www.example.com/';

			/** @var string The value base() returns. */
			public static $baseUri = 'http://www.example.com/';

			/** @var string The URI an `getInstance()` with no argument reports. */
			public static $currentUri = 'http://www.example.com/index.php';

			protected $uri;

			public function __construct($uri = null)
			{
				$this->uri = $uri ?? self::$currentUri;
			}

			public static function root($pathonly = false, $path = null)
			{
				if (!$pathonly)
				{
					return self::$rootUri;
				}

				return rtrim((string) parse_url(self::$rootUri, PHP_URL_PATH), '/');
			}

			public static function base($pathonly = false)
			{
				if (!$pathonly)
				{
					return self::$baseUri;
				}

				return rtrim((string) parse_url(self::$baseUri, PHP_URL_PATH), '/');
			}

			public static function getInstance($uri = 'SERVER')
			{
				return new self($uri === 'SERVER' ? self::$currentUri : $uri);
			}

			public static function current()
			{
				return self::$currentUri;
			}

			public function toString(array $parts = [])
			{
				return $this->uri;
			}

			public function __toString()
			{
				return $this->uri;
			}

			public function getHost()
			{
				return (string) parse_url($this->uri, PHP_URL_HOST);
			}

			public function getScheme()
			{
				return (string) parse_url($this->uri, PHP_URL_SCHEME);
			}

			/**
			 * Mirrors Joomla's rule: a URL is internal when it starts with the site root, or with
			 * the site's base path. Tests of ARS's open-redirect guards depend on this being a real
			 * check rather than a constant, so it is implemented rather than stubbed away.
			 */
			public static function isInternal($url)
			{
				$url = trim((string) $url);

				if ($url === '')
				{
					return false;
				}

				$normalised = strtolower(str_replace('\\', '/', $url));
				$root       = strtolower(self::$rootUri);
				$base       = strtolower(self::$baseUri);

				if (strpos($normalised, $root) === 0 || strpos($normalised, $base) === 0)
				{
					return true;
				}

				// A scheme-bearing URL that did not match the root is external by definition.
				return !preg_match('#^[a-z][a-z0-9+.\-]*:#i', $normalised) && strpos($normalised, '//') !== 0;
			}
		}
	}
}

namespace Joomla\CMS\Router {
	/**
	 * Stand-in for Route. `_()` returns the URL unchanged, so tests assert the query ARS built
	 * rather than whatever the router would have rewritten it into. Route rewriting is a Joomla
	 * behaviour and belongs in the end-to-end suite.
	 */
	if (!class_exists(Route::class, false))
	{
		class Route
		{
			// Values copied from Joomla's own Route class. ARS evaluates Route::TLS_IGNORE at the top
			// of Update\Common::getDownloadUrl(), before it looks at anything else, so without these
			// the class cannot be exercised at all — the call fatals on an undefined constant.
			public const TLS_IGNORE = 0;

			public const TLS_FORCE = 1;

			public const TLS_DISABLE = 2;

			public static function _($url, $xhtml = true, $tls = self::TLS_IGNORE, $absolute = false)
			{
				return $url;
			}

			public static function link($client, $url, $xhtml = true, $tls = self::TLS_IGNORE, $absolute = false)
			{
				return $url;
			}
		}
	}
}

namespace Joomla\CMS\Layout {
	/**
	 * Stand-in for LayoutHelper. Rather than rendering, it RECORDS the calls, so a test can assert
	 * which layout ARS chose and with what data — which is the interesting part when the layout
	 * itself is just presentation.
	 */
	if (!class_exists(LayoutHelper::class, false))
	{
		class LayoutHelper
		{
			/** @var array<int,array{layout:string,data:mixed,basePath:mixed,options:mixed}> */
			public static $renderCalls = [];

			/** @var string What render() returns. */
			public static $renderResult = '';

			public static function render($layoutFile, $displayData = null, $basePath = '', $options = null)
			{
				self::$renderCalls[] = [
					'layout'   => $layoutFile,
					'data'     => $displayData,
					'basePath' => $basePath,
					'options'  => $options,
				];

				return self::$renderResult;
			}

			public static function reset()
			{
				self::$renderCalls  = [];
				self::$renderResult = '';
			}
		}
	}
}

namespace Joomla\CMS\HTML {
	if (!class_exists(HTMLHelper::class, false))
	{
		abstract class HTMLHelper
		{
			public static function _($key, ...$methodArgs)
			{
				return '';
			}
		}
	}
}

namespace Joomla\CMS\Filter {
	if (!class_exists(InputFilter::class, false))
	{
		class InputFilter
		{
			public static function getInstance($tagsArray = [], $attrArray = [], $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1)
			{
				return new self();
			}

			public function clean($source, $type = 'string')
			{
				return $source;
			}
		}
	}
}

namespace Joomla\CMS\Application {
	if (!class_exists(ApplicationHelper::class, false))
	{
		class ApplicationHelper
		{
			/**
			 * Joomla's slug generator, reduced to the transliteration-free path. ARS's alias
			 * generation is asserted against ASCII inputs only, precisely so this reduction cannot
			 * change an outcome; a test needing transliteration belongs in the end-to-end suite.
			 */
			public static function stringURLSafe($string, $language = '')
			{
				$str = str_replace('-', ' ', (string) $string);
				$str = strtolower(trim($str));
				$str = preg_replace('/(\s|[^A-Za-z0-9\-])+/', '-', $str);

				return trim($str, '-');
			}
		}
	}

	if (!class_exists(CMSApplication::class, false))
	{
		abstract class CMSApplication extends \Joomla\Application\AbstractApplication
		{
		}
	}

	if (!class_exists(SiteApplication::class, false))
	{
		class SiteApplication extends CMSApplication
		{
		}
	}

	if (!class_exists(AdministratorApplication::class, false))
	{
		class AdministratorApplication extends CMSApplication
		{
		}
	}
}

namespace Joomla\CMS\User {
	if (!class_exists(User::class, false))
	{
		#[\AllowDynamicProperties]
		class User
		{
			public $id = 0;

			public $username = '';

			public $name = '';

			public $email = '';

			public $guest = 1;

			/** @var array<string,bool> asset|action => allowed. Populate in a test. */
			public $permissions = [];

			/** @var int[] */
			public $viewLevels = [1];

			public function __construct($identifier = 0)
			{
				$this->id    = (int) $identifier;
				$this->guest = $this->id === 0 ? 1 : 0;
			}

			public function authorise($action, $assetname = null)
			{
				return (bool) ($this->permissions[$assetname . '|' . $action] ?? false);
			}

			public function getAuthorisedViewLevels()
			{
				return $this->viewLevels;
			}

			public function getParam($key, $default = null)
			{
				return $default;
			}
		}
	}
}

namespace Joomla\CMS\Date {
	if (!class_exists(Date::class, false))
	{
		class Date extends \DateTime
		{
			public function toSql($local = false, $db = null)
			{
				return $this->format('Y-m-d H:i:s');
			}

			/**
			 * Real Joomla's `Date::format()` overrides `\DateTime::format()` with two extra parameters, $local
			 * (translate day/month names via the active language) and $translate. `AkeebaReleaseSystem::formatDate()`
			 * calls `$date->format($dateFormat, $local)`, so without this override every call throws
			 * `ArgumentCountError: DateTime::format() expects exactly 1 argument, 2 given` — confirmed empirically
			 * before adding this. Locale translation itself is NOT emulated (that is real formatting behaviour, not
			 * a value a stub should invent); $local and $translate are accepted and ignored, so the call succeeds
			 * and returns the plain (untranslated) formatted string.
			 */
			public function format($format, $local = false, $translate = true): string
			{
				return parent::format($format);
			}
		}
	}
}

namespace Joomla\CMS {
	/**
	 * Stand-in for Joomla\CMS\Version. The constants are what ARS's update-stream code reads to
	 * decide which target platforms to advertise; a test pins them by declaring its expectations
	 * against these values rather than against whatever Joomla the developer happens to have.
	 */
	if (!class_exists(Version::class, false))
	{
		final class Version
		{
			public const PRODUCT = 'Joomla!';

			public const MAJOR_VERSION = 6;

			public const MINOR_VERSION = 1;

			public const PATCH_VERSION = 2;

			public const EXTRA_VERSION = '';

			public const RELEASE = '6.1';

			public const DEV_LEVEL = '2';

			public function getShortVersion()
			{
				return self::MAJOR_VERSION . '.' . self::MINOR_VERSION . '.' . self::PATCH_VERSION;
			}
		}
	}

	/**
	 * Stand-in for Joomla\CMS\Factory. Everything is a public static slot a test assigns before
	 * exercising the class under test. Nothing here constructs a real object: a Factory call that
	 * has not been primed returns null, which fails loudly rather than quietly returning something
	 * plausible.
	 */
	if (!class_exists(Factory::class, false))
	{
		abstract class Factory
		{
			/** @var mixed */
			public static $application;

			/** @var mixed */
			public static $container;

			/** @var mixed */
			public static $user;

			/** @var mixed */
			public static $config;

			/** @var mixed */
			public static $document;

			/** @var mixed */
			public static $language;

			public static function getApplication()
			{
				return self::$application;
			}

			public static function getContainer()
			{
				return self::$container;
			}

			public static function getUser($id = null)
			{
				return self::$user;
			}

			public static function getConfig()
			{
				return self::$config;
			}

			public static function getDocument()
			{
				return self::$document;
			}

			public static function getLanguage()
			{
				return self::$language;
			}

			public static function getDate($time = 'now', $tzOffset = null)
			{
				return new \Joomla\CMS\Date\Date($time === 'now' ? 'now' : $time, new \DateTimeZone('UTC'));
			}

			/**
			 * Clear every primed slot. Call this in tearDown(): the suite runs with
			 * beStrictAboutChangesToGlobalState, and these are global state.
			 */
			public static function reset()
			{
				self::$application = null;
				self::$container   = null;
				self::$user        = null;
				self::$config      = null;
				self::$document    = null;
				self::$language    = null;
			}
		}
	}
}

namespace Joomla\CMS\Table {
	if (!interface_exists(TableInterface::class, false))
	{
		interface TableInterface
		{
		}
	}

	/**
	 * Stand-in for Joomla\CMS\Table\Table, carrying the small part of the API ARS's own table
	 * classes call on their parent. It stores no data and talks to no database; a test drives
	 * `onBeforeCheck()`-style logic directly with properties it assigns.
	 */
	if (!class_exists(Table::class, false))
	{
		#[\AllowDynamicProperties]
		abstract class Table implements TableInterface
		{
			/** @var string */
			protected $_tbl = '';

			/** @var string */
			protected $_tbl_key = 'id';

			/** @var mixed */
			protected $_db;

			/** @var array<string,string> */
			protected $_columnAlias = [];

			/** @var array<string,mixed> Column => default. Populate in a test to declare a schema. */
			public $__stubFields = [];

			public function __construct($table = '', $key = 'id', $db = null, $dispatcher = null)
			{
				$this->_tbl     = $table;
				$this->_tbl_key = $key;
				$this->_db      = $db;
			}

			public function getDatabase()
			{
				return $this->_db;
			}

			public function setDatabase($db)
			{
				$this->_db = $db;
			}

			/**
			 * Real Joomla's `Table::getDbo()` is a deprecated alias for `getDatabase()`. Several ARS Table classes
			 * still call it directly (that is production code, out of scope for this stub file to "fix"), so the
			 * alias has to exist here too or those classes could not be loaded into a running state at all.
			 *
			 * @deprecated Mirrors a deprecated real-Joomla method; kept only so calling code still works.
			 */
			public function getDbo()
			{
				return $this->getDatabase();
			}

			public function getTableName()
			{
				return $this->_tbl;
			}

			public function getKeyName($multiple = false)
			{
				return $multiple ? [$this->_tbl_key] : $this->_tbl_key;
			}

			public function getFields($reload = false)
			{
				return $this->__stubFields;
			}

			public function hasField($key)
			{
				return \array_key_exists($this->getColumnAlias($key), $this->__stubFields)
					|| \array_key_exists($key, $this->__stubFields);
			}

			public function getColumnAlias($column)
			{
				return $this->_columnAlias[$column] ?? $column;
			}

			public function setColumnAlias($column, $columnAlias)
			{
				$this->_columnAlias[$column] = $columnAlias;
			}

			public function getProperties($public = true)
			{
				return get_object_vars($this);
			}

			public function bind($src, $ignore = [])
			{
				foreach ((array) $src as $k => $v)
				{
					if (!\in_array($k, (array) $ignore, true))
					{
						$this->$k = $v;
					}
				}

				return true;
			}

			public function check()
			{
				return true;
			}

			public function reset()
			{
			}

			public function setError($error)
			{
			}
		}
	}
}

namespace Joomla\CMS\MVC\Factory {
	if (!interface_exists(MVCFactoryInterface::class, false))
	{
		interface MVCFactoryInterface
		{
		}
	}

	if (!trait_exists(MVCFactoryAwareTrait::class, false))
	{
		trait MVCFactoryAwareTrait
		{
			/** @var mixed */
			private $__stubMvcFactory;

			public function setMVCFactory($mvcFactory)
			{
				$this->__stubMvcFactory = $mvcFactory;
			}

			protected function getMVCFactory()
			{
				return $this->__stubMvcFactory;
			}
		}
	}
}

namespace Joomla\CMS\MVC\Model {
	if (!interface_exists(StateBehaviorInterface::class, false))
	{
		interface StateBehaviorInterface
		{
		}
	}

	if (!interface_exists(FormModelInterface::class, false))
	{
		interface FormModelInterface
		{
		}
	}

	if (!trait_exists(FormBehaviorTrait::class, false))
	{
		trait FormBehaviorTrait
		{
		}
	}

	/**
	 * Stand-in for Joomla's model base classes. State is a Registry a test seeds directly, and the
	 * database is settable, which is all a `getListQuery()` test needs: the real class's ordering
	 * whitelist lives in `populateState()`, which the tests bypass on purpose so they can drive
	 * hostile values straight into `list.ordering` / `list.direction` and see what the model does
	 * with them.
	 */
	if (!class_exists(BaseModel::class, false))
	{
		abstract class BaseModel
		{
			/** @var \Joomla\Registry\Registry */
			protected $state;

			/** @var string */
			protected $context = 'com_ars.test';

			/** @var mixed */
			protected $__stubDatabase;

			public function __construct($config = [], $factory = null)
			{
				$this->state = new \Joomla\Registry\Registry();
			}

			public function getState($property = null, $default = null)
			{
				$this->state = $this->state ?: new \Joomla\Registry\Registry();

				return $property === null ? $this->state : $this->state->get($property, $default);
			}

			public function setState($property, $value = null)
			{
				$this->state = $this->state ?: new \Joomla\Registry\Registry();

				return $this->state->set($property, $value);
			}

			public function getDatabase()
			{
				return $this->__stubDatabase;
			}

			public function setDatabase($db)
			{
				$this->__stubDatabase = $db;

				return $this;
			}

			protected function getStoreId($id = '')
			{
				return $id;
			}

			protected function populateState($ordering = null, $direction = null)
			{
			}
		}
	}

	if (!class_exists(BaseDatabaseModel::class, false))
	{
		abstract class BaseDatabaseModel extends BaseModel
		{
		}
	}

	if (!class_exists(ListModel::class, false))
	{
		class ListModel extends BaseDatabaseModel
		{
			public function getItems()
			{
				return [];
			}
		}
	}

	if (!class_exists(AdminModel::class, false))
	{
		class AdminModel extends BaseDatabaseModel
		{
			public function getTable($name = '', $prefix = '', $options = [])
			{
				return null;
			}
		}
	}
}

namespace Joomla\CMS\MVC\View {
	/**
	 * Stand-in for HtmlView. ARS's update-stream views are traits mixed into a view, so the view
	 * only has to exist and carry `$state`; the algorithms under test are in the trait.
	 */
	if (!class_exists(HtmlView::class, false))
	{
		class HtmlView
		{
			/** @var \Joomla\Registry\Registry */
			protected $state;

			public function __construct($config = [])
			{
				$this->state = new \Joomla\Registry\Registry();
			}

			public function getName()
			{
				return '';
			}

			public function get($property, $default = null)
			{
				return $default;
			}
		}
	}

	if (!class_exists(JsonView::class, false))
	{
		class JsonView extends HtmlView
		{
		}
	}
}

namespace Joomla\CMS\MVC\Controller {
	/**
	 * Stand-in for Joomla's controller hierarchy. It carries only the properties ARS's controller
	 * mixins touch, so a controller can be created with `newInstanceWithoutConstructor()` and the
	 * mixin exercised directly.
	 */
	if (!class_exists(BaseController::class, false))
	{
		class BaseController
		{
			/** @var mixed */
			protected $app;

			/** @var mixed */
			protected $input;

			/** @var string */
			protected $task;

			public function __construct($config = [], $factory = null, $app = null, $input = null)
			{
				$this->app   = $app;
				$this->input = $input;
			}

			public function getName()
			{
				return '';
			}

			public function checkToken($method = 'post', $redirect = true)
			{
				return true;
			}
		}
	}

	if (!class_exists(FormController::class, false))
	{
		class FormController extends BaseController
		{
		}
	}

	if (!class_exists(AdminController::class, false))
	{
		class AdminController extends BaseController
		{
		}
	}
}

namespace Joomla\CMS\Plugin {
	if (!class_exists(CMSPlugin::class, false))
	{
		#[\AllowDynamicProperties]
		abstract class CMSPlugin
		{
			/** @var \Joomla\Registry\Registry */
			public $params;

			/** @var mixed */
			protected $app;

			/** @var bool */
			protected $autoloadLanguage = false;

			public function __construct($subject = null, array $config = [])
			{
				$this->params = new \Joomla\Registry\Registry($config['params'] ?? null);
			}

			public function setApplication($app)
			{
				$this->app = $app;
			}

			protected function getApplication()
			{
				return $this->app;
			}
		}
	}
}

namespace Joomla\CMS\Event {
	if (!trait_exists(CoreEventAware::class, false))
	{
		trait CoreEventAware
		{
			protected static array $eventNameToConcreteClass = [];
		}
	}
}

namespace Joomla\CMS\Cache {
	if (!interface_exists(CacheControllerFactoryInterface::class, false))
	{
		interface CacheControllerFactoryInterface
		{
		}
	}
}

namespace Joomla\CMS\Cache\Controller {
	if (!class_exists(CallbackController::class, false))
	{
		class CallbackController
		{
		}
	}
}

namespace Joomla\CMS\Installer {
	if (!class_exists(InstallerHelper::class, false))
	{
		abstract class InstallerHelper
		{
			public static function downloadPackage($url, $target = false, $timeout = 0)
			{
				return false;
			}
		}
	}
}

namespace Joomla\Filesystem {
	if (!class_exists(File::class, false))
	{
		class File
		{
			public static function exists($file)
			{
				return is_file($file);
			}

			public static function delete($file)
			{
				return @unlink($file);
			}

			public static function write($file, &$buffer, $useStreams = false)
			{
				return file_put_contents($file, $buffer) !== false;
			}

			public static function getExt($file)
			{
				$dot = strrpos((string) $file, '.');

				return $dot === false ? '' : substr((string) $file, $dot + 1);
			}
		}
	}

	if (!class_exists(Folder::class, false))
	{
		class Folder
		{
			public static function exists($path)
			{
				return is_dir($path);
			}

			public static function create($path, $mode = 0755)
			{
				return is_dir($path) || @mkdir($path, $mode, true);
			}

			public static function files($path, $filter = '.', $recurse = false, $full = false)
			{
				return [];
			}

			public static function folders($path, $filter = '.', $recurse = false, $full = false)
			{
				return [];
			}
		}
	}
}
