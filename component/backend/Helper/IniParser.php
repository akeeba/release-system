<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * A class to load INI files, without bumping into incompatibilities between
 * different PHP versions
 */
abstract class IniParser
{
	/**
	 * Parse an INI file and return an associative array using a custom pure PHP method when parse_ini_file is blocked
	 * by some so-called "hosts"...
	 *
	 * Yeah, I know, I hate myself too for having to include this helper class.
	 *
	 * @param    string  $file              The file to process
	 * @param    bool    $process_sections  True to also process INI sections
	 * @param    bool    $isRawData         If true, the $file contains raw INI data, not a filename
	 *
	 * @return   array  An associative array of sections, keys and values
	 */
	public static function parse_ini_file($file, $process_sections, $isRawData = false)
	{
		if ($isRawData)
		{
			return self::parse_ini_file_php($file, $process_sections, $isRawData);
		}
		else
		{
			if (function_exists('parse_ini_file'))
			{
				return parse_ini_file($file, $process_sections);
			}
			else
			{
				return self::parse_ini_file_php($file, $process_sections);
			}
		}
	}

	/**
	 * A PHP based INI file parser.
	 *
	 * Thanks to asohn ~at~ aircanopy ~dot~ net for posting this handy function on
	 * the parse_ini_file page on http://gr.php.net/parse_ini_file
	 *
	 * @param    string  $file              Filename to process
	 * @param    bool    $process_sections  True to also process INI sections
	 * @param    bool    $isRawData         If true, the $file contains raw INI data, not a filename
	 *
	 * @return    array    An associative array of sections, keys and values
	 */
	static function parse_ini_file_php($file, $process_sections = false, $isRawData = false)
	{
		$process_sections = ($process_sections !== true) ? false : true;

		if (!$isRawData)
		{
			$ini = file($file);
		}
		else
		{
			$file = str_replace("\r", "", $file);
			$ini  = explode("\n", $file);
		}

		if (count($ini) == 0)
		{
			return array();
		}

		$sections = array();
		$values   = array();
		$result   = array();
		$globals  = array();
		$i        = 0;

		foreach ($ini as $line)
		{
			$line = trim($line);
			$line = str_replace("\t", " ", $line);

			// Comments
			if (!preg_match('/^[a-zA-Z0-9[]/', $line))
			{
				continue;
			}

			// Sections
			if ($line{0} == '[')
			{
				$tmp        = explode(']', $line);
				$sections[] = trim(substr($tmp[0], 1));
				$i++;

				continue;
			}

			// Key-value pair
			list($key, $value) = explode('=', $line, 2);

			$key   = trim($key);
			$value = trim($value);

			if (strstr($value, ";"))
			{
				$tmp = explode(';', $value);

				if (count($tmp) == 2)
				{
					if ((($value{0} != '"') && ($value{0} != "'")) ||
						preg_match('/^".*"\s*;/', $value) || preg_match('/^".*;[^"]*$/', $value) ||
						preg_match("/^'.*'\s*;/", $value) || preg_match("/^'.*;[^']*$/", $value)
					)
					{
						$value = $tmp[0];
					}
				}
				else
				{
					if ($value{0} == '"')
					{
						$value = preg_replace('/^"(.*)".*/', '$1', $value);
					}
					elseif ($value{0} == "'")
					{
						$value = preg_replace("/^'(.*)'.*/", '$1', $value);
					}
					else
					{
						$value = $tmp[0];
					}
				}
			}
			$value = trim($value);
			$value = trim($value, "'\"");

			if ($i == 0)
			{
				if (substr($line, -1, 2) == '[]')
				{
					$globals[ $key ][] = $value;
				}
				else
				{
					$globals[ $key ] = $value;
				}
			}
			else
			{
				if (substr($line, -1, 2) == '[]')
				{
					$values[ $i - 1 ][ $key ][] = $value;
				}
				else
				{
					$values[ $i - 1 ][ $key ] = $value;
				}
			}
		}

		for ($j = 0; $j < $i; $j++)
		{
			if ($process_sections === true)
			{
				if (isset($sections[ $j ]) && isset($values[ $j ]))
				{
					$result[ $sections[ $j ] ] = $values[ $j ];
				}
			}
			else
			{
				if (isset($values[ $j ]))
				{
					$result[] = $values[ $j ];
				}
			}
		}

		return $result + $globals;
	}
}
