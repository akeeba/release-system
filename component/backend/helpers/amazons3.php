<?php
/**
 * Akeeba Engine
 * The modular PHP5 site backup engine
 * @copyright Copyright (c)2009-2012 Nicholas K. Dionysopoulos
 * @license GNU GPL version 3 or, at your option, any later version
 * @package akeebaengine
 * @version $Id$
 *
 * This file contains the ArsHelperAmazons3 class which allows storing and
 * retrieving files from Amazon's Simple Storage Service (Amazon S3).
 * It is a subset of S3.php, written by Donovan Schonknecht and available
 * at http://undesigned.org.za/2007/10/22/amazon-s3-php-class under a
 * BSD-like license. I have merely removed the parts which weren't useful
 * to ARS and changed the naming.
 * 
 * Note for this version: I have added multipart uploads, a feature which
 * wasn't included in the original version of the S3.php. As a result, this
 * file no longer reflects the original author's work and should not be
 * confused with it.
 *
 * Amazon S3 is a trademark of Amazon.com, Inc. or its affiliates.
 */

// Protection against direct access
defined('_JEXEC') or die();

if(!defined('AKEEBA_CACERT_PEM')) {
	define('AKEEBA_CACERT_PEM', JPATH_ADMINISTRATOR.'/components/com_ars/assets/cacert.pem');
}

class ArsHelperAmazons3 extends JObject
{
	// ACL flags
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';
	const ACL_BUCKET_OWNER_READ = 'bucket-owner-read';
	const ACL_BUCKET_OWNER_FULL_CONTROL = 'bucket-owner-full-control';

	public static $useSSL = true;

	private static $__accessKey; // AWS Access key
	private static $__secretKey; // AWS Secret key
	private static $__default_bucket = null;
	private static $__default_acl = 'private'; // Default ACLs to use: private
	private static $__default_time = 900; // Default timeout for signed URLs: 15 minutes

	/**
	 * Singleton implemetation
	 */
	public static function &getInstance($accessKey = null, $secretKey = null, $useSSL = true)
	{
		static $instance = null;
		
		if(!is_object($instance)) {
			$component = JComponentHelper::getComponent('com_ars');
			if(!($component->params instanceof JRegistry)) {
				$params = new JRegistry($component->params);
			} else {
				$params = $component->params;
			}
			
			if(empty($accessKey) && empty($secretKey)) {
				if(version_compare(JVERSION, '3.0', 'ge')) {
					$accessKey	= $params->get('s3access','');
					$secretKey	= $params->get('s3secret','');
					$useSSL		= $params->get('s3ssl',true);
				} else {
					$accessKey	= $params->getValue('s3access','');
					$secretKey	= $params->getValue('s3secret','');
					$useSSL		= $params->getValue('s3ssl',true);
				}
			}
			
			$instance = new ArsHelperAmazons3($accessKey, $secretKey, $useSSL);
			if(version_compare(JVERSION, '3.0', 'ge')) {
				self::$__default_bucket = $params->get('s3bucket', '');
				self::$__default_acl = $params->get('s3perms','private');
				self::$__default_time = $params->get('s3time', 900);
			} else {
				self::$__default_bucket = $params->getValue('s3bucket', '');
				self::$__default_acl = $params->getValue('s3perms','private');
				self::$__default_time = $params->getValue('s3time', 900);
			}
		}
		
		return $instance;
	}
	
	/**
	* Constructor - if you're not using the class statically
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @param boolean $useSSL Enable SSL
	* @return void
	*/
	public function __construct($accessKey = null, $secretKey = null, $useSSL = true) {
		if ($accessKey !== null && $secretKey !== null)
			self::setAuth($accessKey, $secretKey);
		self::$useSSL = $useSSL;
	}

	/**
	* Set AWS access key and secret key
	*
	* @param string $accessKey Access key
	* @param string $secretKey Secret key
	* @return void
	*/
	public static function setAuth($accessKey, $secretKey) {
		self::$__accessKey = $accessKey;
		self::$__secretKey = $secretKey;
	}

	/**
	* Create input info array for putObject()
	*
	* @param string $file Input file
	* @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
	* @return array | false
	*/
	public static function inputFile($file, $md5sum = false) {
		if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
			$o = self::getInstance();
			$o->setError(__CLASS__.'::inputFile(): Unable to open input file: '.$file);
			return false;
		}
		
		return array('file' => $file, 'size' => filesize($file),
		'md5sum' => $md5sum !== false ? (is_string($md5sum) ? $md5sum :
		base64_encode(md5_file($file, true))) : '');
	}


	/**
	* Create input array info for putObject() with a resource
	*
	* @param string $resource Input resource to read from
	* @param integer $bufferSize Input byte size
	* @param string $md5sum MD5 hash to send (optional)
	* @return array | false
	*/
	public static function inputResource($resource, $bufferSize, $md5sum = '') {
		if (!is_resource($resource) || $bufferSize < 0) {
			$o = self::getInstance();
			$o->setError(__CLASS__.'::inputResource(): Invalid resource or buffer size');
			return false;
		}
		$input = array('size' => $bufferSize, 'md5sum' => $md5sum);
		$input['fp'] = $resource;
		return $input;
	}


	/**
	* Put an object
	*
	* @param mixed $input Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param array $requestHeaders Array of request headers or content type as a string
	* @return boolean
	*/
	public static function putObject($input, $bucket, $uri, $acl = null, $metaHeaders = array(), $requestHeaders = array()) {
		if ($input === false) return false;
		if(empty($bucket)) $bucket = self::$__default_bucket;
		$rest = new ArsHelperS3Request('PUT', $bucket, $uri);

		if(is_null($acl)) $acl = self::$__default_acl;
		
		if (is_string($input)) $input = array(
			'data' => $input, 'size' => strlen($input),
			'md5sum' => base64_encode(md5($input, true))
		);

		// Data
		if (isset($input['fp']))
			$rest->fp = $input['fp'];
		elseif (isset($input['file']))
			$rest->fp = @fopen($input['file'], 'rb');
		elseif (isset($input['data']))
			$rest->data = $input['data'];

		// Content-Length (required)
		if (isset($input['size']) && $input['size'] >= 0)
			$rest->size = $input['size'];
		else {
			if (isset($input['file']))
				$rest->size = filesize($input['file']);
			elseif (isset($input['data']))
				$rest->size = strlen($input['data']);
		}

		// Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
		if (is_array($requestHeaders))
			foreach ($requestHeaders as $h => $v) $rest->setHeader($h, $v);
		elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
			$input['type'] = $requestHeaders;

		// Content-Type
		if (!isset($input['type'])) {
			if (isset($requestHeaders['Content-Type']))
				$input['type'] = $requestHeaders['Content-Type'];
			elseif (isset($input['file']))
				$input['type'] = self::__getMimeType($input['file']);
			else
				$input['type'] = 'application/octet-stream';
		}

		// We need to post with Content-Length and Content-Type, MD5 is optional
		if ($rest->size >= 0 && ($rest->fp !== false || $rest->data !== false)) {
			$rest->setHeader('Content-Type', $input['type']);
			if (isset($input['md5sum'])) $rest->setHeader('Content-MD5', $input['md5sum']);

			$rest->setAmzHeader('x-amz-acl', $acl);
			foreach ($metaHeaders as $h => $v) $rest->setAmzHeader('x-amz-meta-'.$h, $v);
			$rest->getResponse();
		} else
			$rest->response->error = array('code' => 0, 'message' => 'Missing input parameters');

		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false) {
			$o = self::getInstance();
			$o->setError(sprintf(__CLASS__."::putObject(): [%s] %s", $rest->response->error['code'], $rest->response->error['message']));
			return false;
		}
		return true;
	}
	
	/**
	* Start a multipart upload of an object
	*
	* @param mixed $input Input data
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers
	* @param array $requestHeaders Array of request headers or content type as a string
	* @return boolean
	*/
	public static function startMultipart($input, $bucket, $uri, $acl = null, $metaHeaders = array(), $requestHeaders = array()) {
		if ($input === false) return false;
		if(empty($bucket)) $bucket = self::$__default_bucket;
		$rest = new ArsHelperS3Request('POST', $bucket, $uri);
		$rest->setParameter('uploads','');

		if(is_null($acl)) $acl = self::$__default_acl;
		
		if (is_string($input)) $input = array(
			'data' => $input, 'size' => strlen($input),
			'md5sum' => base64_encode(md5($input, true))
		);

		// Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
		if (is_array($requestHeaders))
			foreach ($requestHeaders as $h => $v) $rest->setHeader($h, $v);
		elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
			$input['type'] = $requestHeaders;

		// Content-Type
		if (!isset($input['type'])) {
			if (isset($requestHeaders['Content-Type']))
				$input['type'] = $requestHeaders['Content-Type'];
			elseif (isset($input['file']))
				$input['type'] = self::__getMimeType($input['file']);
			else
				$input['type'] = 'application/octet-stream';
		}

		// Do the post
		$rest->setHeader('Content-Type', $input['type']);
		if (isset($input['md5sum'])) $rest->setHeader('Content-MD5', $input['md5sum']);

		$rest->setAmzHeader('x-amz-acl', $acl);
		foreach ($metaHeaders as $h => $v) $rest->setAmzHeader('x-amz-meta-'.$h, $v);
		$rest->getResponse();
		
		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false) {
			$o = self::getInstance();
			$o->setError(sprintf(__CLASS__."::startMultipart(): [%s] %s", $rest->response->error['code'], $rest->response->error['message']));
			return false;
		}
		
		$body = $rest->response->body;
		if(!is_object($body)) $body = simplexml_load_string($body);
		return (string)$body->UploadId;
	}
	
	/**
	* Uploads a part of a multipart object upload
	*
	* @param mixed $input Input data. You MUST specify the UploadID and PartNumber
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param constant $acl ACL constant
	* @param array $metaHeaders Array of x-amz-meta-* headers (NOT USED)
	* @param array $requestHeaders Array of request headers or content type as a string
	* @return boolean
	*/
	public static function uploadMultipart($input, $bucket, $uri, $acl = null, $metaHeaders = array(), $requestHeaders = array()) {
		if ($input === false) {
			$o = self::getInstance();
			$o->setError(__CLASS__."::uploadMultipart(): No input specified");
			return false;	
		}
		if(empty($bucket)) $bucket = self::$__default_bucket;
		
		if(is_null($acl)) $acl = self::$__default_acl;
		
		if (is_string($input)) $input = array(
			'data' => $input, 'size' => strlen($input),
			'md5sum' => base64_encode(md5($input, true))
		);
		
		// We need a valid UploadID and PartNumber
		if(!array_key_exists('UploadID', $input)) {
			$o = self::getInstance();
			$o->setError(__CLASS__."::uploadMultipart(): No UploadID specified");
			return false;	
		}
		if(!array_key_exists('PartNumber', $input)) {
			$o = self::getInstance();
			$o->setError(__CLASS__."::uploadMultipart(): No PartNumber specified");
			return false;	
		}
		
		$UploadID = $input['UploadID'];
		$UploadID = urlencode($UploadID);
		$PartNumber = (int)$input['PartNumber'];
		
		$rest = new ArsHelperS3Request('PUT', $bucket, $uri, 's3.amazonaws.com');
		$rest->setParameter('partNumber',$PartNumber);
		$rest->setParameter('uploadId',$UploadID);

		// Data
		if (isset($input['fp'])) {
			$rest->fp = $input['fp'];
		} elseif (isset($input['file'])) {
			$rest->fp = @fopen($input['file'], 'rb');
		} elseif (isset($input['data'])) {
			$rest->data = $input['data'];
		}

		// Full data length
		if (isset($input['size']) && $input['size'] >= 0) {
			$totalSize = $input['size'];
		} else {
			if (isset($input['file'])) {
				$totalSize = filesize($input['file']);
			} elseif (isset($input['data'])) {
				$totalSize = strlen($input['data']);
			}
		}
		
		// No Content-Type for multipart uploads
		if(array_key_exists('type', $input)) unset($input['type']);

		// Calculate part offset
		$partOffset = 5242880 * ($PartNumber - 1);
		if($partOffset > $totalSize) {
			return 0; // This is to signify that we ran out of parts ;)
		}
		
		// How many parts are there?
		$totalParts = floor($totalSize / 5242880);
		if($totalParts * 5242880 < $totalSize) $totalParts++;
		
		// Calculate Content-Length
		if($PartNumber == $totalParts) {
			$rest->size = $totalSize - ($PartNumber - 1) * 5242880;
		} else {
			$rest->size = 5242880;
		}
		
		if (isset($input['file'])) {
			// Create a temp file with the bytes we want to upload
			$fp = @fopen($input['file'], 'rb');			
			$result = fseek($fp, ($PartNumber - 1) * 5242880);
			
			$rest->fp = $fp; // I have to set the ArsHelperS3Request's file pointer, NOT the file structure's, as the request object is already initialized
			$rest->data = false;
		} elseif (isset($input['data'])) {
			$tempfilename = null;
			$rest->fp = false;
			$rest->data = substr($input['data'], ($PartNumber - 1) * 5242880, $rest->size);
		}
		
		// Custom request headers (Content-Type, Content-Disposition, Content-Encoding)
		if (is_array($requestHeaders))
			foreach ($requestHeaders as $h => $v) $rest->setHeader($h, $v);
		elseif (is_string($requestHeaders)) // Support for legacy contentType parameter
			$input['type'] = $requestHeaders;

		// We need to post with Content-Length
		if ($rest->size >= 0 && ($rest->fp !== false || $rest->data !== false)) {
			$rest->getResponse();
		} else {
			if($rest->size < 0) {
				$rest->response->error = array('code' => 0, 'message' => 'Missing file size parameter');	
			} else {
				$rest->response->error = array('code' => 0, 'message' => 'No data file pointer specified');
			}
		}

		@fclose($fp);
		
		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false) {
			// Sometimes we get a broken pipe error which is bullshit; it's just a response with 0 size
			if( ($rest->response->error['code'] == 55) && ($rest->response->code == 200) && is_array($rest->response->headers) ) {
				// This is not an error. AAAAARGH!
				return $rest->response->headers['hash'];
			} else {
				$o = self::getInstance();
				$o->setError(sprintf(__CLASS__."::uploadMultipart(): [%s] %s", $rest->response->error['code'], $rest->response->error['message']));
				return false;
			}
		}
				
		// Return the ETag header
		return $rest->response->headers['hash'];
	}
	
	/**
	 * Finalizes the multi-part upload. The $input array should contain two keys, etags an array of ETags of the uploaded
	 * parts and UploadID the multipart upload ID.
	 * @param $input The array of input elements
	 * @param $bucket The bucket where the object is being stored
	 * @param $uri The key (path) to the object
	 * @return bool True on success
	 */
	public static function finalizeMultipart($input, $bucket, $uri) {
		if(!array_key_exists('etags',$input)) {
			$o = self::getInstance();
			$o->setError(__CLASS__."::finalizeMultipart(): No ETags array specified");
			return false;	
		}
		if(empty($bucket)) $bucket = self::$__default_bucket;
		if(!array_key_exists('UploadID', $input)) {
			$o = self::getInstance();
			$o->setError(__CLASS__."::finalizeMultipart(): No UploadID specified");
			return false;	
		}
		
		$etags = $input['etags'];
		$UploadID = $input['UploadID'];
		
		// Create the message
		$message ="<CompleteMultipartUpload>\n";
		$part = 0;
		foreach($etags as $etag) {
			$part++;
			$message .= "\t<Part>\n\t\t<PartNumber>$part</PartNumber>\n\t\t<ETag>\"$etag\"</ETag>\n\t</Part>\n";
		}
		$message .= "</CompleteMultipartUpload>";
		
		// Get a request query
		$rest = new ArsHelperS3Request('POST', $bucket, $uri);
		$rest->setParameter('uploadId', $UploadID);
		
		// Set content length
		$rest->size = strlen($message);
		
		// Set content
		$rest->data = $message;
		$rest->fp = false;
		
		// Do post
		$rest->setHeader('Content-Type', 'application/xml'); // Even though the Amazon API doc doesn't mention it, it's required... :(
		$rest->getResponse();
		
		if ($rest->response->error === false && $rest->response->code !== 200)
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false) {
			// A RequestTimeout is not an error. S3 will finish constructing the big file on its own pace
			if($rest->response->error['code'] == 'RequestTimeout') {
				return true;
			} else {
				$o = self::getInstance();
				$o->setError(sprintf(__CLASS__."::finalizeMultipart(): [%s] %s", $rest->response->error['code'], $rest->response->error['message']));
				return false;
			}
		}
		
		return true;
	}

	/**
	* Get an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param mixed $saveTo Filename or resource to write to
	* @return mixed
	*/
	public static function getObject($bucket, $uri, $saveTo = false, $from = null, $to = null) {
		if(empty($bucket)) $bucket = self::$__default_bucket;
		$rest = new ArsHelperS3Request('GET', $bucket, $uri);
		if ($saveTo !== false) {
			if (is_resource($saveTo))
				$rest->fp = $saveTo;
			else
				if (($rest->fp = @fopen($saveTo, 'wb')) !== false)
					$rest->file = realpath($saveTo);
				else
					$rest->response->error = array('code' => 0, 'message' => 'Unable to open save file for writing: '.$saveTo);
		}
		if ($rest->response->error === false) {
			// Set the range header
			if(!empty($from) && !empty($to))
			{
				$rest->setHeader('Range',"bytes=$from-$to");
			}
			$rest->getResponse();
		}

		if ($rest->response->error === false && (($rest->response->code !== 200) && ($rest->response->code !== 206)))
			$rest->response->error = array('code' => $rest->response->code, 'message' => 'Unexpected HTTP status');
		if ($rest->response->error !== false) {
			$o = self::getInstance();
			$o->setError(sprintf(__CLASS__."::getObject({$bucket}, {$uri}): [%s] %s",
			$rest->response->error['code'], $rest->response->error['message']));
			return false;
		}
		return ($saveTo === false) ? $rest->response : true;
	}

	/**
	* Delete an object
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return boolean
	*/
	public static function deleteObject($bucket, $uri) {
		if(empty($bucket)) $bucket = self::$__default_bucket;
		$rest = new ArsHelperS3Request('DELETE', $bucket, $uri);
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 204)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$o = self::getInstance();
			$o->setError(sprintf(__CLASS__."::deleteObject({$bucket}, {$uri}): [%s] %s",
			$rest->error['code'], $rest->error['message']));
			return false;
		}
		return true;
	}
	
	/**
	* Get a list of buckets
	*
	* @param boolean $detailed Returns detailed bucket list when true
	* @return array | false
	*/
	public static function listBuckets($detailed = false) {
		$rest = new ArsHelperS3Request('GET', '', '');
		$rest = $rest->getResponse();
		if ($rest->error === false && $rest->code !== 200)
			$rest->error = array('code' => $rest->code, 'message' => 'Unexpected HTTP status');
		if ($rest->error !== false) {
			$o = self::getInstance();
			$o->setError(sprintf(__CLASS__.'::'.__METHOD__."(): [%s] %s", $rest->error['code'], $rest->error['message']));
			return false;
		}
		$results = array();
		if (!isset($rest->body->Buckets)) return $results;

		if ($detailed) {
			if (isset($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
			$results['owner'] = array(
				'id' => (string)$rest->body->Owner->ID, 'name' => (string)$rest->body->Owner->ID
			);
			$results['buckets'] = array();
			foreach ($rest->body->Buckets->Bucket as $b)
				$results['buckets'][] = array(
					'name' => (string)$b->Name, 'time' => strtotime((string)$b->CreationDate)
				);
		} else
			foreach ($rest->body->Buckets->Bucket as $b) $results[] = (string)$b->Name;

		return $results;
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
	public static function getBucket($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false) {
		if(empty($bucket)) $bucket = self::$__default_bucket;
		$rest = new ArsHelperS3Request('GET', $bucket, '');
		if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix);
		if ($marker !== null && $marker !== '') $rest->setParameter('marker', $marker);
		if ($maxKeys !== null && $maxKeys !== '') $rest->setParameter('max-keys', $maxKeys);
		if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimiter', $delimiter);
		$response = $rest->getResponse();
		if ($response->error === false && $response->code !== 200)
			$response->error = array('code' => $response->code, 'message' => 'Unexpected HTTP status');
		if ($response->error !== false) {
			self::getInstance()->setError(sprintf(__CLASS__."::getBucket(): [%s] %s", $response->error['code'], $response->error['message']));
			return false;
		}

		$results = array();

		$nextMarker = null;
		if (isset($response->body, $response->body->Contents))
		foreach ($response->body->Contents as $c) {
			$results[(string)$c->Key] = array(
				'name' => (string)$c->Key,
				'time' => strtotime((string)$c->LastModified),
				'size' => (int)$c->Size,
				'hash' => substr((string)$c->ETag, 1, -1)
			);
			$nextMarker = (string)$c->Key;
		}

		if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
			foreach ($response->body->CommonPrefixes as $c)
				$results[(string)$c->Prefix] = array('prefix' => (string)$c->Prefix);

		if (isset($response->body, $response->body->IsTruncated) &&
		(string)$response->body->IsTruncated == 'false') return $results;

		if (isset($response->body, $response->body->NextMarker))
			$nextMarker = (string)$response->body->NextMarker;

		// Loop through truncated results if maxKeys isn't specified
		if ($maxKeys == null && $nextMarker !== null && (string)$response->body->IsTruncated == 'true')
		do {
			$rest = new ArsHelperS3Request('GET', $bucket, '');
			if ($prefix !== null && $prefix !== '') $rest->setParameter('prefix', $prefix);
			$rest->setParameter('marker', $nextMarker);
			if ($delimiter !== null && $delimiter !== '') $rest->setParameter('delimiter', $delimiter);

			if (($response = $rest->getResponse(true)) == false || $response->code !== 200) break;

			if (isset($response->body, $response->body->Contents))
			foreach ($response->body->Contents as $c) {
				$results[(string)$c->Key] = array(
					'name' => (string)$c->Key,
					'time' => strtotime((string)$c->LastModified),
					'size' => (int)$c->Size,
					'hash' => substr((string)$c->ETag, 1, -1)
				);
				$nextMarker = (string)$c->Key;
			}

			if ($returnCommonPrefixes && isset($response->body, $response->body->CommonPrefixes))
				foreach ($response->body->CommonPrefixes as $c)
					$results[(string)$c->Prefix] = array('prefix' => (string)$c->Prefix);

			if (isset($response->body, $response->body->NextMarker))
				$nextMarker = (string)$response->body->NextMarker;

		} while ($response !== false && (string)$response->body->IsTruncated == 'true');

		return $results;
	}
	
	/**
	* Get a query string authenticated URL
	*
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @param integer $lifetime Lifetime in seconds
	* @param boolean $hostBucket Use the bucket name as the hostname
	* @param boolean $https Use HTTPS ($hostBucket should be false for SSL verification)
	* @return string
	*/
	public static function getAuthenticatedURL($bucket, $uri, $lifetime = null, $hostBucket = false, $https = false) {
		if(empty($bucket)) $bucket = self::$__default_bucket;
		if(is_null($lifetime)) $lifetime = self::$__default_time;
		$expires = time() + $lifetime;
		$uri = str_replace('%2F', '/', rawurlencode($uri)); // URI should be encoded (thanks Sean O'Dea)
		return sprintf(($https ? 'https' : 'http').'://%s/%s?AWSAccessKeyId=%s&Expires=%u&Signature=%s',
		$hostBucket ? $bucket : $bucket.'.s3.amazonaws.com', $uri, self::$__accessKey, $expires,
		urlencode(self::__getHash("GET\n\n\n{$expires}\n/{$bucket}/{$uri}")));
	}
	
	/**
	* Get MIME type for file
	*
	* @internal Used to get mime types
	* @param string &$file File path
	* @return string
	*/
	public static function __getMimeType(&$file) {
		$type = false;
		// Fileinfo documentation says fileinfo_open() will use the
		// MAGIC env var for the magic file
		if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
		($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false) {
			if (($type = finfo_file($finfo, $file)) !== false) {
				// Remove the charset and grab the last content-type
				$type = explode(' ', str_replace('; charset=', ';charset=', $type));
				$type = array_pop($type);
				$type = explode(';', $type);
				$type = trim(array_shift($type));
			}
			finfo_close($finfo);

		// If anyone is still using mime_content_type()
		} elseif (function_exists('mime_content_type'))
			$type = trim(mime_content_type($file));

		if ($type !== false && strlen($type) > 0) return $type;

		// Otherwise do it the old fashioned way
		static $exts = array(
			'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
			'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
			'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
			'zip' => 'application/zip', 'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
			'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
			'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
			'css' => 'text/css', 'js' => 'text/javascript',
			'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
			'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
			'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
			'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
		);
		$ext = strtolower(pathInfo($file, PATHINFO_EXTENSION));
		return isset($exts[$ext]) ? $exts[$ext] : 'application/octet-stream';
	}

	/**
	* Generate the auth string: "AWS AccessKey:Signature"
	*
	* @internal Used by ArsHelperS3Request::getResponse()
	* @param string $string String to sign
	* @return string
	*/
	public static function __getSignature($string) {
		return 'AWS '.self::$__accessKey.':'.self::__getHash($string);
	}

	/**
	* Creates a HMAC-SHA1 hash
	*
	* This uses the hash extension if loaded
	*
	* @internal Used by __getSignature()
	* @param string $string String to sign
	* @return string
	*/
	public static function __getHash($string) {
		return base64_encode(extension_loaded('hash') ?
		hash_hmac('sha1', $string, self::$__secretKey, true) : pack('H*', sha1(
		(str_pad(self::$__secretKey, 64, chr(0x00)) ^ (str_repeat(chr(0x5c), 64))) .
		pack('H*', sha1((str_pad(self::$__secretKey, 64, chr(0x00)) ^
		(str_repeat(chr(0x36), 64))) . $string)))));
	}

}

final class ArsHelperS3Request {
	private $verb, $bucket, $uri, $resource = '', $parameters = array(),
	$amzHeaders = array(), $headers = array(
		'Host' => '', 'Date' => '', 'Content-MD5' => '', 'Content-Type' => ''
	);
	public $fp = false, $size = 0, $data = false, $response;


	/**
	* Constructor
	*
	* @param string $verb Verb
	* @param string $bucket Bucket name
	* @param string $uri Object URI
	* @return mixed
	*/
	function __construct($verb, $bucket = '', $uri = '', $defaultHost = 's3.amazonaws.com') {
		$this->verb = $verb;
		$this->bucket = $bucket;
		$this->uri = $uri !== '' ? '/'.str_replace('%2F', '/', rawurlencode($uri)) : '/';
		
		if ($this->bucket !== '') {
			$this->headers['Host'] = $this->bucket.'.'.$defaultHost;
			$this->resource = '/'.$this->bucket.$this->uri;
		} else {
			$this->headers['Host'] = $defaultHost;
			//$this->resource = strlen($this->uri) > 1 ? '/'.$this->bucket.$this->uri : $this->uri;
			$this->resource = $this->uri;
		}
		$this->headers['Date'] = gmdate('D, d M Y H:i:s T');

		$this->response = new STDClass;
		$this->response->error = false;
	}


	/**
	* Set request parameter
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}


	/**
	* Set request header
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setHeader($key, $value) {
		$this->headers[$key] = $value;
	}


	/**
	* Set x-amz-meta-* header
	*
	* @param string $key Key
	* @param string $value Value
	* @return void
	*/
	public function setAmzHeader($key, $value) {
		$this->amzHeaders[$key] = $value;
	}


	/**
	* Get the S3 response
	*
	* @return object | false
	*/
	public function getResponse() {
		$query = '';
		if (sizeof($this->parameters) > 0) {
			$query = substr($this->uri, -1) !== '?' ? '?' : '&';
			foreach ($this->parameters as $var => $value)
				if ($value == null || $value == '') $query .= $var.'&';
				// Parameters should be encoded (thanks Sean O'Dea)
				else $query .= $var.'='.rawurlencode($value).'&';
			$query = substr($query, 0, -1);
			$this->uri .= $query;

			if (array_key_exists('acl', $this->parameters) ||
			array_key_exists('location', $this->parameters) ||
			array_key_exists('torrent', $this->parameters) ||
			array_key_exists('logging', $this->parameters) ||
			array_key_exists('uploads', $this->parameters) ||
			array_key_exists('uploadId', $this->parameters) ||
			array_key_exists('partNumber', $this->parameters))
				$this->resource .= $query;
		}
		$url = ((ArsHelperAmazons3::$useSSL && extension_loaded('openssl')) ?
		'https://':'http://').$this->headers['Host'].$this->uri;

		// Basic setup
		$curl = curl_init();
		@curl_setopt($curl, CURLOPT_CAINFO, AKEEBA_CACERT_PEM);
		curl_setopt($curl, CURLOPT_USERAGENT, 'AkeebaReleaseSystem/S3Integration');

		if (ArsHelperAmazons3::$useSSL) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, (stristr(PHP_OS, 'WIN') ? false : true));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, (stristr(PHP_OS, 'WIN') ? false : true));
		}

		curl_setopt($curl, CURLOPT_URL, $url);

		// Headers
		$headers = array(); $amz = array();
		foreach ($this->amzHeaders as $header => $value)
			if (strlen($value) > 0) $headers[] = $header.': '.$value;
		foreach ($this->headers as $header => $value)
			if (strlen($value) > 0) $headers[] = $header.': '.$value;

		// Collect AMZ headers for signature
		foreach ($this->amzHeaders as $header => $value)
			if (strlen($value) > 0) $amz[] = strtolower($header).':'.$value;

		// AMZ headers must be sorted
		if (sizeof($amz) > 0) {
			sort($amz);
			$amz = "\n".implode("\n", $amz);
		} else $amz = '';

		// Authorization string (CloudFront stringToSign should only contain a date)
		if( $this->headers['Host'] == 'cloudfront.amazonaws.com' ) {
			$stringToSign = $this->headers['Date'];
		} else {
			$stringToSign = $this->verb."\n".$this->headers['Content-MD5']."\n".
			$this->headers['Content-Type']."\n".$this->headers['Date'].$amz."\n".$this->resource;
		}
		
		$headers[] = 'Authorization: ' . ArsHelperAmazons3::__getSignature($stringToSign);

		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
		curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, '__responseHeaderCallback'));
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

		// Request types
		switch ($this->verb) {
			case 'GET': break;
			case 'PUT': case 'POST': // POST only used for CloudFront
				if ($this->fp !== false) {
					curl_setopt($curl, CURLOPT_PUT, true);
					curl_setopt($curl, CURLOPT_INFILE, $this->fp);
					if ($this->size >= 0)
						curl_setopt($curl, CURLOPT_INFILESIZE, $this->size);
				} elseif ($this->data !== false) {
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
					if ($this->size >= 0)
						curl_setopt($curl, CURLOPT_BUFFERSIZE, $this->size);
				} else
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->verb);
			break;
			case 'HEAD':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
				curl_setopt($curl, CURLOPT_NOBODY, true);
			break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
			default: break;
		}

		// Execute, grab errors
		if (curl_exec($curl))
			$this->response->code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		else
			$this->response->error = array(
				'code' => curl_errno($curl),
				'message' => curl_error($curl),
				'resource' => $this->resource
			);

		@curl_close($curl);

		// Parse body into XML
		if ($this->response->error === false && isset($this->response->headers['type']) &&
		$this->response->headers['type'] == 'application/xml' && isset($this->response->body)) {
			$this->response->body = simplexml_load_string($this->response->body);

			// Grab S3 errors
			if (!in_array($this->response->code, array(200, 204)) &&
			isset($this->response->body->Code, $this->response->body->Message)) {
				$this->response->error = array(
					'code' => (string)$this->response->body->Code,
					'message' => (string)$this->response->body->Message
				);
				if (isset($this->response->body->Resource))
					$this->response->error['resource'] = (string)$this->response->body->Resource;
				unset($this->response->body);
			}
		}

		// Clean up file resources
		if ($this->fp !== false && is_resource($this->fp)) fclose($this->fp);

		return $this->response;
	}


	/**
	* CURL write callback
	*
	* @param resource &$curl CURL resource
	* @param string &$data Data
	* @return integer
	*/
	private function __responseWriteCallback(&$curl, &$data) {
		if ( in_array($this->response->code, array(200,206)) && $this->fp !== false)
			return fwrite($this->fp, $data);
		else
			$this->response->body .= $data;
		return strlen($data);
	}


	/**
	* CURL header callback
	*
	* @param resource &$curl CURL resource
	* @param string &$data Data
	* @return integer
	*/
	private function __responseHeaderCallback(&$curl, &$data) {
		if (($strlen = strlen($data)) <= 2) return $strlen;
		if (substr($data, 0, 4) == 'HTTP')
			$this->response->code = (int)substr($data, 9, 3);
		else {
			list($header, $value) = explode(': ', trim($data), 2);
			if ($header == 'Last-Modified')
				$this->response->headers['time'] = strtotime($value);
			elseif ($header == 'Content-Length')
				$this->response->headers['size'] = (int)$value;
			elseif ($header == 'Content-Type')
				$this->response->headers['type'] = $value;
			elseif ($header == 'ETag')
				$this->response->headers['hash'] = $value{0} == '"' ? substr($value, 1, -1) : $value;
			elseif (preg_match('/^x-amz-meta-.*$/', $header))
				$this->response->headers[$header] = is_numeric($value) ? (int)$value : $value;
		}
		return $strlen;
	}

}