<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

defined('_JEXEC') or die;

if (!class_exists('Akeeba\\Engine\\Postproc\\Connector\\S3v4\\Connector'))
{
	\FOF30\Autoloader\Autoloader::getInstance()->addMap(
		'Akeeba\\Engine\\Postproc\\Connector\\S3v4\\', array(
			realpath(__DIR__ . '/../vendor/akeeba/s3/src')
		)
	);
}

if (!defined('AKEEBAENGINE'))
{
	define('AKEEBAENGINE', 1);
}

use Akeeba\Engine\Postproc\Connector\S3v4\Configuration;
use Akeeba\Engine\Postproc\Connector\S3v4\Connector;
use Akeeba\Engine\Postproc\Connector\S3v4\Input;

/**
 * This class is an abstraction layer to the actual Amazon S3 API implementation we are using. It allows us to shield
 * the ARS code from any necessary changes to the underlying API client implementation.
 */
class AmazonS3 extends \JObject
{
	// ACL flags
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';
	const ACL_BUCKET_OWNER_READ = 'bucket-owner-read';
	const ACL_BUCKET_OWNER_FULL_CONTROL = 'bucket-owner-full-control';

	/**
	 * Should I use SSL?
	 *
	 * @var  bool
	 */
	private static $useSSL = true;

	/**
	 * AWS access key
	 *
	 * @var  string
	 */
	private static $accessKey; // AWS Access key

	/**
	 * AWS secret key
	 *
	 * @var  string
	 */
	private static $secretKey; // AWS Secret key

	/**
	 * Which signature method should I use?
	 *
	 * @var  string
	 */
	private static $signatureMethod = 'v4';

	/**
	 * Should I use reduced redundancy storage (RRS)?
	 *
	 * @var  bool
	 */
	private static $rrs = false;

	/**
	 * Which region your bucket is in (required for v4 signature API)
	 *
	 * @var  string
	 */
	private static $region = '';

	/**
	 * Which bucket should I use if none is specified?
	 *
	 * @var  string
	 */
	private static $bucket = null;

	/**
	 * Which ACL should I use if none is specified?
	 *
	 * @var  string
	 */
	private static $acl = 'private'; // Default ACLs to use: private

	/**
	 * Timeout for signed requests, in seconds. Default: 15 minutes.
	 *
	 * @var int
	 */
	private static $timeForSignedRequests = 900;

	private $s3Client = null;

	/**
	 * Public constructor
	 */
	function __construct($properties = null)
	{
		parent::__construct($properties);

		// Prepare the credentials object
		$s3Configuration = new Configuration(
			self::$accessKey,
			self::$secretKey,
			self::$signatureMethod,
			self::$region
		);

		$s3Configuration->setSSL(self::$useSSL);

		// Prepare the client options array. See http://docs.aws.amazon.com/aws-sdk-php/guide/latest/configuration.html#client-configuration-options
		$clientOptions = array(
			'credentials' => $s3Configuration,
			'scheme'      => self::$useSSL ? 'https' : 'http',
			'signature'   => self::$signatureMethod,
			'region'      => self::$region,
		);

		// If SSL is not enabled you must not provide the CA root file.
		if (self::$useSSL && !defined('AKEEBA_CACERT_PEM'))
		{
			define('AKEEBA_CACERT_PEM', JPATH_LIBRARIES . '/fof30/Download/Adapter/cacert.pem');
		}

		// Create the S3 client instance
		$this->s3Client = new Connector($s3Configuration);
	}

	/**
	 * Get a static instance of this helper
	 */
	public static function &getInstance()
	{
		static $instance = null;

		if (!is_object($instance))
		{
			$component = \JComponentHelper::getComponent('com_ars');

			if (!($component->params instanceof \JRegistry))
			{
				$params = new \JRegistry($component->params);
			}
			else
			{
				$params = $component->params;
			}

			// Set up
			self::$accessKey             = $params->get('s3access', '');
			self::$secretKey             = $params->get('s3secret', '');
			self::$useSSL                = $params->get('s3ssl', 1);
			self::$bucket                = $params->get('s3bucket', '');
			self::$region                = $params->get('s3region', 'us-east-1');
			self::$signatureMethod       = $params->get('s3method', 's3');
			self::$rrs                   = $params->get('s3rrs', 0);
			self::$acl                   = $params->get('s3perms', 'private');
			self::$timeForSignedRequests = $params->get('s3time', 900);

			// Remove slashes from the bucket...
			self::$bucket = str_replace('/', '', self::$bucket);

			// Get the instance
			$instance = new AmazonS3();
		}

		return $instance;
	}

	/**
	 * Save a file to Amazon S3
	 *
	 * @param   string $fileOrContent The absolute filesystem path or, if $rawContent is true, the raw content to save
	 * @param   string $path          The path in Amazon S3 where the data will be stored
	 * @param   bool   $rawContent    Does the $fileOrContent parameter contain raw data to upload?
	 *
	 * @return  bool  True on success
	 */
	public function putObject($fileOrContent, $path, $rawContent = false)
	{
		if ($rawContent)
		{
			$input = Input::createFromData($fileOrContent);
		}
		else
		{
			$input = Input::createFromFile($fileOrContent);
		}

		$headers = array(
			'X-Amz-Storage-Class' => 'STANDARD'
		);

		if (self::$rrs)
		{
			$headers['X-Amz-Storage-Class'] = 'REDUCED_REDUNDANCY';
		}

		try
		{
			$this->s3Client->putObject($input, self::$bucket, $path, self::$acl);
		}
		catch (\Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Get the contents of a file stored on Amazon S3
	 *
	 * @param   string $path The path of the file stored on S3
	 *
	 * @return  string|bool  The data returned from S3, false if it failed
	 */
	public function getObject($path)
	{
		try
		{
			return $this->s3Client->getObject(self::$bucket, $path);
		}
		catch (\Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}
	}

	/**
	 * Delete a file from Amazon S3
	 *
	 * @param   string $path The path to the Amazon S3 file to delete
	 *
	 * @return  bool  True on success
	 */
	public function deleteObject($path)
	{
		try
		{
			$this->s3Client->deleteObject(self::$bucket, $path);

			return true;
		}
		catch (\Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}
	}

	/*
	* Get contents for a bucket
	*
	* If maxKeys is null this method will loop through truncated result sets
	*
	* @param string $bucket Bucket name
	* @param string $prefix Prefix
	* @param string $marker Marker (last file listed)
	* @param string $maxKeys Max keys (maximum number of keys to return)
	* @param string $delimiter Delimiter
	* @param boolean $returnCommonPrefixes Set to true to return CommonPrefixes
	* @return array | false
	*/
	public function getBucket($bucket = null, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false)
	{
		if (empty($bucket))
		{
			$bucket = self::$bucket;
		}

		if (empty($delimiter))
		{
			$delimiter = '/';
		}

		try
		{
			return $this->s3Client->getBucket($bucket, $prefix, $marker, $maxKeys, $delimiter, $returnCommonPrefixes);
		}
		catch (\Exception $e)
		{
			$this->setError($e->getCode() . ' :: ' . $e->getMessage());

			return false;
		}
	}

	/**
	 * Get a query string authenticated URL
	 *
	 * @param   string $path Object URI
	 *
	 * @return  string
	 */
	public function getAuthenticatedURL($path)
	{
		return $this->s3Client->getAuthenticatedURL(self::$bucket, $path, self::$timeForSignedRequests, self::$useSSL);
	}
}
