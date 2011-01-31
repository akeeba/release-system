<?php

define( '_JPA_MAJOR', 1 ); // JPA Format major version number
define( '_JPA_MINOR', 0 ); // JPA Format minor version number

/**
 * Creates JPA archives
 * @author Nicholas K. Dionysopoulos
 * @version $Id$
 * @package akeebabuilder
 * @copyright Copyright (c)2009-2011 Nicholas K. Dionysopoulos
 * @license GNU GPL version 3 or, at your option, any later version
 */
class JPAMaker
{
	/** @var string Full pathname to the archive file */
	private $dataFileName;

	/** @var int Number of files in the archive */
	private $fileCount;

	/** @var int Size in bytes of the data read from the filesystem */
	private $uncompressedSize;

	/** @var int Size in bytes of the data stored in the archive */
	private $compressedSize;

	/** @var string Standard header signature */
	private $sigStdHeader = "\x4A\x50\x41";	// Standard Header signature

	/** @var string Entity Block signature */
	private $sigEntityHeader = "\x4A\x50\x46";	//

	/** @var string Error message */
	public $error;

	/**
	 * Creates or overwrites a new JPA archive
	 * @param $archive The full path to the JPA archive
	 * @return bool True on success
	 */
	public function create($archive)
	{
		$this->dataFileName = $archive;

		// Try to kill the archive if it exists
		$fp = @fopen( $this->dataFileName, "wb" );
		if (!($fp === false)) {
			@ftruncate( $fp,0 );
			@fclose( $fp );
		} else {
			if( file_exists($this->dataFileName) ) @unlink( $this->dataFileName );
			@touch( $this->dataFileName );
		}

		if(!is_writable($archive))
		{
			$this->error = 'Can\'t open '.$archive.' for writing';
			return false;
		}

		// Write the initial instance of the archive header
		$this->writeArchiveHeader();

		if(!empty($error)) return false;

		return true;
	}

	/**
	 * Adds a file to the archive
	 * @param $from The full pathname to the file you want to add to the archive
	 * @param $to [optional] The relative pathname to store in the archive
	 * @return bool True on success
	 */
	public function addFile($from, $to = null)
	{
		// See if it's a directory
		$isDir = is_dir($from);
		// Get real size before compression
		$fileSize = $isDir ? 0 : filesize($from);
		// Decide if we will compress
		if ($isDir) {
			$compressionMethod = 0; // don't compress directories...
		} else {
			$compressionMethod = 1; // Compress all files
		}
		$compressionMethod = function_exists("gzcompress") ? $compressionMethod : 0;
		$storedName = empty($to) ? $from : $to;
		$storedName = self::TranslateWinPath($storedName);

		/* "Entity Description Block" segment. */
		$unc_len = &$fileSize; // File size
		$storedName .= ($isDir) ? "/" : "";
		if ($compressionMethod == 1) {
			if( function_exists("file_get_contents") )
			{
				$udata = @file_get_contents( $from );
			}
			else
			{
				// Argh... the hard way!
				$udatafp = @fopen( $from, "rb" );
				if( !($udatafp === false) ) {
					$udata = "";
					while( !feof($udatafp) ) {
						$udata .= fread($udatafp, 524288);
					}
					fclose( $udatafp );
				} else {
					$udata = false;
				}
			}

			if ($udata === FALSE) {
				return false;
			} else {
				// Proceed with compression
				$zdata   = @gzcompress($udata);
				if ($zdata === false) {
					// If compression fails, let it behave like no compression was available
					$c_len = &$unc_len;
					$compressionMethod = 0;
				} else {
					unset( $udata );
					$zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
					$c_len   = strlen($zdata);
				}
			}
		}
		else
		{
			$c_len = $unc_len;
		}

		$this->compressedSize += $c_len; // Update global data
		$this->uncompressedSize += $fileSize; // Update global data
		$this->fileCount++;

		// Get file permissions
		$perms = @fileperms( $from );

		// Calculate Entity Description Block length
		$blockLength = 21 + strlen($storedName) ;

		// Open data file for output
		$fp = @fopen( $this->dataFileName, "ab");
		if ($fp === false)
		{
			$this->error = "Could not open archive file '{$this->dataFileName}' for append!";
			return false;
		}

		$this->writeToArchive( $fp, $this->sigEntityHeader ); // Entity Description Block header
		if($this->error) return false;

		$this->writeToArchive( $fp, pack('v', $blockLength) ); // Entity Description Block header length
		$this->writeToArchive( $fp, pack('v', strlen($storedName) ) ); // Length of entity path
		$this->writeToArchive( $fp, $storedName ); // Entity path
		$this->writeToArchive( $fp, pack('C', ($isDir ? 0 : 1) ) ); // Entity type
		$this->writeToArchive( $fp, pack('C', $compressionMethod ) ); // Compression method
		$this->writeToArchive( $fp, pack('V', $c_len ) ); // Compressed size
		$this->writeToArchive( $fp, pack('V', $unc_len ) ); // Uncompressed size
		$this->writeToArchive( $fp, pack('V', $perms ) ); // Entity permissions

		/* "File data" segment. */
		if ($compressionMethod == 1) {
			// Just dump the compressed data
			$this->writeToArchive( $fp, $zdata );
			if($this->error) return false;
			unset( $zdata );
		} elseif (!$isDir) {
			// Copy the file contents, ignore directories
			$zdatafp = @fopen( $from, "rb" );
			while( !feof($zdatafp) ) {
				$zdata = fread($zdatafp, 524288);
				$this->writeToArchive( $fp, $zdata );
				if($this->error) return false;
			}
			fclose( $zdatafp );
		}

		fclose( $fp );

		// ... and return TRUE = success
		return true;
	}

	/**
	 * Updates the Standard Header with current information
	 * @return bool True on success
	 */
	public function finalize()
	{
		$this->writeArchiveHeader();
		if($this->error) return false;
		return true;
	}

	/**
	 * Outputs a Standard Header at the top of the file
	 * @return bool True on success
	 */
	private function writeArchiveHeader()
	{
		$fp = @fopen( $this->dataFileName, 'r+' );
		if($fp === false)
		{
			$this->error = 'Could not open '.$this->dataFileName.' for writing. Check permissions and open_basedir restrictions.';
			return false;
		}
		$this->writeToArchive( $fp, $this->sigStdHeader );					// ID string (JPA)
		if($this->error) return false;
		$this->writeToArchive( $fp, pack('v', 19) );							// Header length; fixed to 19 bytes
		$this->writeToArchive( $fp, pack('C', _JPA_MAJOR ) );					// Major version
		$this->writeToArchive( $fp, pack('C', _JPA_MINOR ) );					// Minor version
		$this->writeToArchive( $fp, pack('V', $this->fileCount ) );			// File count
		$this->writeToArchive( $fp, pack('V', $this->uncompressedSize ) );	// Size of files when extracted
		$this->writeToArchive( $fp, pack('V', $this->compressedSize ) );		// Size of files when stored
		@fclose( $fp );
		return true;
	}

	/**
	 * Write to file, defeating magic_quotes_runtime settings (pure binary write)
	 * @param handle $fp Handle to a file
	 * @param string $data The data to write to the file
	 */
	private function writeToArchive( $fp, $data )
	{
		$len = strlen( $data );
		$ret = fwrite( $fp, $data, $len );
		if($ret === FALSE)
		{
			$this->error = "Can't write to archive";
			return false;
		}

		return true;
	}

	private static function TranslateWinPath( $p_path )
	{
		if (stristr(php_uname(), 'windows')){
			// Change potential windows directory separator
			if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0, 1) == '\\')){
				$p_path = strtr($p_path, '\\', '/');
			}
		}
		return $p_path;
	}

}