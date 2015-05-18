<?php

/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

class ArsHelperIp
{

	/**
	 * Gets the visitor's IP address. Automatically handles reverse proxies
	 * reporting the IPs of intermediate devices, like load balancers. Examples:
	 * https://www.akeebabackup.com/support/admin-tools/13743-double-ip-adresses-in-security-exception-log-warnings.html
	 * http://stackoverflow.com/questions/2422395/why-is-request-envremote-addr-returning-two-ips
	 * The solution used is assuming that the last IP address is the external one.
	 *
	 * @return  string
	 */
	public static function getUserIP()
	{
		$ip = self::_real_getUserIP();

		if ((strstr($ip, ',') !== false) || (strstr($ip, ' ') !== false))
		{
			$ip = str_replace(' ', ',', $ip);
			$ip = str_replace(',,', ',', $ip);
			$ips = explode(',', $ip);
			$ip = '';
			while (empty($ip) && !empty($ips))
			{
				$ip = array_pop($ips);
				$ip = trim($ip);
			}
		}
		else
		{
			$ip = trim($ip);
		}

		return $ip;
	}

	/**
	 * Gets the visitor's IP address
	 *
	 * @return  string
	 */
	private static function _real_getUserIP()
	{
		// Normally the $_SERVER superglobal is set
		if (isset($_SERVER))
		{
			// Do we have an x-forwarded-for HTTP header (e.g. NginX)?
			if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
			{
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}

			// Do we have a client-ip header (e.g. non-transparent proxy)?
			if (array_key_exists('HTTP_CLIENT_IP', $_SERVER))
			{
				return $_SERVER['HTTP_CLIENT_IP'];
			}

			// Normal, non-proxied server or server behind a transparent proxy
			return $_SERVER['REMOTE_ADDR'];
		}

		// This part is executed on PHP running as CGI, or on SAPIs which do
		// not set the $_SERVER superglobal
		// If getenv() is disabled, you're screwed
		if (!function_exists('getenv'))
		{
			return '';
		}

		// Do we have an x-forwarded-for HTTP header?
		if (getenv('HTTP_X_FORWARDED_FOR'))
		{
			return getenv('HTTP_X_FORWARDED_FOR');
		}

		// Do we have a client-ip header?
		if (getenv('HTTP_CLIENT_IP'))
		{
			return getenv('HTTP_CLIENT_IP');
		}

		// Normal, non-proxied server or server behind a transparent proxy
		if (getenv('REMOTE_ADDR'))
		{
			return getenv('REMOTE_ADDR');
		}

		// Catch-all case for broken servers, apparently
		return '';
	}

	/**
	 * Works around the REMOTE_ADDR not containing the user's IP
	 */
	public static function workaroundIPIssues()
	{
		$ip = self::getUserIP();

		if ($_SERVER['REMOTE_ADDR'] == $ip)
		{
			return;
		}

		if (array_key_exists('REMOTE_ADDR', $_SERVER))
		{
			$_SERVER['ADMINTOOLS_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
		}
		elseif (function_exists('getenv'))
		{
			if (getenv('REMOTE_ADDR'))
			{
				$_SERVER['ADMINTOOLS_REMOTE_ADDR'] = getenv('REMOTE_ADDR');
			}
		}

		$_SERVER['REMOTE_ADDR'] = $ip;
	}

	/**
	 * Is this an IPv6 IP address?
	 *
	 * @param   string $ip An IPv4 or IPv6 address
	 *
	 * @return  boolean  True if it's IPv6
	 */
	public static function isIPv6($ip)
	{
		if (strstr($ip, ':'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Converts inet_pton output to bits string
	 *
	 * @param   string $inet The in_addr representation of an IPv4 or IPv6 address
	 *
	 * @return  string
	 */
	protected static function inet_to_bits($inet)
	{
		if (strlen($inet) == 4)
		{
			$unpacked = unpack('A4', $inet);
		}
		else
		{
			$unpacked = unpack('A16', $inet);
		}
		$unpacked = str_split($unpacked[1]);
		$binaryip = '';

		foreach ($unpacked as $char)
		{
			$binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
		}

		return $binaryip;
	}

	/**
	 * Checks if an IP is contained in a list of IPs or IP expressions
	 *
	 * @param   string $ip      The IPv4/IPv6 address to check
	 * @param   array  $ipTable The list of IP expressions
	 *
	 * @return  null|boolean  True if it's in the list, null if the filtering can't proceed
	 */
	public static function IPinList($ip, $ipTable = array())
	{
		// No point proceeding with an empty IP list
		if (empty($ipTable))
		{
			return null;
		}

		// If no IP address is found, return null (unsupported)
		if ($ip == '0.0.0.0')
		{
			return null;
		}

		// If no IP is given, return null (unsupported)
		if (empty($ip))
		{
			return null;
		}

		// Sanity check
		if (!function_exists('inet_pton'))
		{
			return null;
		}

		// Get the IP's in_adds representation
		$myIP = @inet_pton($ip);

		// If the IP is in an unrecognisable format, quite
		if ($myIP === false)
		{
			return null;
		}

		$ipv6 = self::isIPv6($ip);

		foreach ($ipTable as $ipExpression)
		{
			$ipExpression = trim($ipExpression);

			// Inclusive IP range, i.e. 123.123.123.123-124.125.126.127
			if (strstr($ipExpression, '-'))
			{
				list($from, $to) = explode('-', $ipExpression, 2);

				if ($ipv6 && (!self::isIPv6($from) || !self::isIPv6($to)))
				{
					// Do not apply IPv4 filtering on an IPv6 address
					continue;
				}
				elseif (!$ipv6 && (self::isIPv6($from) || self::isIPv6($to)))
				{
					// Do not apply IPv6 filtering on an IPv4 address
					continue;
				}

				$from = @inet_pton(trim($from));
				$to = @inet_pton(trim($to));

				// Sanity check
				if (($from === false) || ($to === false))
				{
					continue;
				}

				// Swap from/to if they're in the wrong order
				if ($from > $to)
				{
					list($from, $to) = array($to, $from);
				}

				if (($myIP >= $from) && ($myIP <= $to))
				{
					return true;
				}
			}
			// Netmask or CIDR provided
			elseif (strstr($ipExpression, '/'))
			{
				$binaryip = self::inet_to_bits($myIP);

				list($net, $maskbits) = explode('/', $ipExpression, 2);
				if ($ipv6 && !self::isIPv6($net))
				{
					// Do not apply IPv4 filtering on an IPv6 address
					continue;
				}
				elseif (!$ipv6 && self::isIPv6($net))
				{
					// Do not apply IPv6 filtering on an IPv4 address
					continue;
				}
				elseif (!$ipv6 && strstr($maskbits, '.'))
				{
					// Convert IPv4 netmask to CIDR
					$long = ip2long($maskbits);
					$base = ip2long('255.255.255.255');
					$maskbits = 32 - log(($long ^ $base) + 1, 2);
				}

				// Convert network IP to in_addr representation
				$net = @inet_pton($net);

				// Sanity check
				if ($net === false)
				{
					continue;
				}

				// Get the network's binary representation
				$binarynet = self::inet_to_bits($net);

				// Check the corresponding bits of the IP and the network
				$ip_net_bits = substr($binaryip, 0, $maskbits);
				$net_bits = substr($binarynet, 0, $maskbits);

				if ($ip_net_bits == $net_bits)
				{
					return true;
				}
			}
			else
			{
				// IPv6: Only single IPs are supported
				if ($ipv6)
				{
					$ipExpression = trim($ipExpression);

					if (!self::isIPv6($ipExpression))
					{
						continue;
					}

					$ipCheck = @inet_pton($ipExpression);
					if ($ipCheck === false)
					{
						continue;
					}

					if ($ipCheck == $myIP)
					{
						return true;
					}
				}
				else
				{
					// Standard IPv4 address, i.e. 123.123.123.123 or partial IP address, i.e. 123.[123.][123.][123]
					$dots = 0;
					if (substr($ipExpression, -1) == '.')
					{
						// Partial IP address. Convert to CIDR and re-match
						foreach (count_chars($ipExpression, 1) as $i => $val)
						{
							if ($i == 46)
							{
								$dots = $val;
							}
						}
						switch ($dots)
						{
							case 1:
								$netmask = '255.0.0.0';
								$ipExpression .= '0.0.0';
								break;

							case 2:
								$netmask = '255.255.0.0';
								$ipExpression .= '0.0';
								break;

							case 3:
								$netmask = '255.255.255.0';
								$ipExpression .= '0';
								break;

							default:
								$dots = 0;
						}

						if ($dots)
						{
							$binaryip = self::inet_to_bits($myIP);

							// Convert netmask to CIDR
							$long = ip2long($netmask);
							$base = ip2long('255.255.255.255');
							$maskbits = 32 - log(($long ^ $base) + 1, 2);

							$net = @inet_pton($ipExpression);

							// Sanity check
							if ($net === false)
							{
								continue;
							}

							// Get the network's binary representation
							$binarynet = self::inet_to_bits($net);

							// Check the corresponding bits of the IP and the network
							$ip_net_bits = substr($binaryip, 0, $maskbits);
							$net_bits = substr($binarynet, 0, $maskbits);

							if ($ip_net_bits == $net_bits)
							{
								return true;
							}
						}
					}
					if (!$dots)
					{
						$ip = @inet_pton(trim($ipExpression));
						if ($ip == $myIP)
						{
							return true;
						}
					}
				}
			}
		}

		return false;
	}
}