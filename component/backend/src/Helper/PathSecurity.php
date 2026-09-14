<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Helper;

defined('_JEXEC') || die;

/**
 * Confines a DB-sourced, free-text relative path (e.g. `#__ars_items.filename`) to a known base
 * directory before it is used in any filesystem call.
 *
 * `filename` legitimately contains one level of subdirectory (Bleeding Edge stores items as
 * `<version>/<basename>`), so it cannot be reduced to a bare `basename()` the way a release
 * `version` can. It must instead be checked for traversal segments and, once concatenated onto the
 * base directory, be verified to still resolve inside it.
 *
 * @since  7.5.1
 */
abstract class PathSecurity
{
	/**
	 * Resolves `$relativePath` against `$basePath`, refusing to resolve outside of it.
	 *
	 * @param   string|null  $basePath      The known-safe base directory.
	 * @param   string|null  $relativePath  The untrusted, DB-sourced relative path.
	 *
	 * @return  string|null  The resolved, contained absolute path; NULL if unsafe or unresolvable.
	 * @since   7.5.1
	 */
	public static function resolveContained(?string $basePath, ?string $relativePath): ?string
	{
		if (empty($basePath) || !is_string($relativePath) || $relativePath === '')
		{
			return null;
		}

		// Reject NUL bytes, absolute paths (POSIX or Windows), and any '..' traversal segment.
		if (str_contains($relativePath, "\0"))
		{
			return null;
		}

		$normalised = str_replace('\\', '/', $relativePath);

		if (str_starts_with($normalised, '/') || preg_match('#^[A-Za-z]:#', $normalised) === 1)
		{
			return null;
		}

		$segments = explode('/', $normalised);

		if (in_array('..', $segments, true) || in_array('.', $segments, true) || in_array('', $segments, true))
		{
			return null;
		}

		$realBasePath = realpath($basePath);
		$realPath     = realpath(rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . $relativePath);

		if ($realBasePath === false || $realPath === false)
		{
			return null;
		}

		if ($realPath !== $realBasePath && !str_starts_with($realPath, $realBasePath . DIRECTORY_SEPARATOR))
		{
			return null;
		}

		return $realPath;
	}
}
