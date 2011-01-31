<?php
require_once "phing/Task.php";
require_once 'phing/tasks/system/MatchingTask.php';
include_once 'phing/util/SourceFileScanner.php';
include_once 'phing/mappers/MergeMapper.php';
include_once 'phing/util/StringHelper.php';
require_once 'jpalib.php';

if(!defined('DS')) define('DS', '/');

if (!function_exists('fnmatch')) {
	function fnmatch($pattern, $string) {
		return @preg_match(
                '/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
		array('*' => '.*', '?' => '.?')) . '$/i', $string
		);
	}
}

/**
 * Documentation export from DocBook XML to a DocImport package file
 * @version $Id$
 * @package akeebabuilder
 * @copyright Copyright (c)2009-2011 Nicholas K. Dionysopoulos
 * @license GNU GPL version 3 or, at your option, any later version
 * @author nicholas
 */
class DocexportTask extends matchingTask
{
	/** @var string Version number of this package, if defined with --version */
	private $version = 'svn';

	/** @var string DocBook XSL stylesheets path, if defined with --xslstyleroot */
	private $xsl_stylesheet_root = '/usr/share/sgml/docbook/xsl-stylesheets';

	private $outdir;

	private $title;

	private $docbook_file;

	private $imagefile_directory;

	private $docbook_relimages;

	private $docimport_package;

	private $temproot;

	/**
	 * Initialises internal variables
	 */
	function init()
	{
		if(DIRECTORY_SEPARATOR == '\\')
		{
			$this->temproot = str_replace('\\', '/', sys_get_temp_dir());
		}
		else
		{
			$this->temproot = sys_get_temp_dir();
		}

		// Post process some configuration arguments
		$root = getcwd();

		$this->imagefile_directory = dirname($this->docbook_file).DS.$this->imagefile_directory;
		if(empty($this->outdir)) $this->outdir = $root;
		$this->docimport_package = $this->outdir.DS.$this->docimport_package.'-'.$this->version.'.jpa';

		if(DIRECTORY_SEPARATOR == '\\')
		{
			$this->docbook_file = str_replace('\\', '/', $this->docbook_file);
			$this->imagefile_directory = str_replace('\\', '/', $this->imagefile_directory);
			$this->docimport_package = str_replace('\\', '/', $this->docimport_package);
		}
	}

	public function setVersion($value)
	{
		$this->version = $value;
	}

	public function setOutdir($value)
	{
		$this->outdir = $value;
	}

	public function setXslstyleroot($value)
	{
		$this->xsl_stylesheet_root = $value;
	}

	public function setTemproot($value)
	{
		$this->temproot = $value;
	}

	public function setTitle($value)
	{
		$this->title = $value;
	}

	public function setDocbookfile($value)
	{
		$this->docbook_file = $value;
	}

	public function setImagedir($value)
	{
		$this->imagefile_directory = $value;
	}

	public function setDocbookimages($value)
	{
		$this->docbook_relimages = $value;
	}

	public function setPackage($value)
	{
		$this->docimport_package = $value;
	}

	public function main()
	{

		// Set some variables as globals
		global $files, $images, $titles, $tempdir, $ordTable, $usedImages;

		$this->init();

		// Get a temporary directory
		$tempdir = $this->temproot.DS.'docexport';
		$i=0;
		// Does this directory already exist? If so, try creating a different name.
		while(file_exists($tempdir))
		{
			$i++;
			$tempdir = $this->temproot.DS.'docexport'.$i;
		}

		// Create the temporary directory
		if(@mkdir($tempdir) === false)
		{
			// Oops! I couldn't create a temporary directory. Crap!
			throw new BuildException('Sorry, I couldn\'t create a temporary directory.');
			exit;
		}

		$this->log("Processing {$this->title}");

		// Call DocBook XML parsing
		$this->log("Generating XHTML files. Please wait...");
		$commandline = 'xsltproc --nonet --xinclude --novalid --stringparam html.stylesheet ' .
			'jpmanual.css --stringparam base.dir '.$tempdir.DS.' --stringparam admon.graphics 1 '.
			'--stringparam use.id.as.filename 1 --stringparam use.id.as.filename 1 '.
			'--stringparam toc.section.depth 5 --stringparam chunk.section.depth 3 ';
		$xslroot = $this->xsl_stylesheet_root.DS.'xhtml'.DS.'chunk.xsl';
		if(DIRECTORY_SEPARATOR == '\\')
		{
			$commandline .= '"'.$xslroot.'"';
		}
		else
		{
			$commandline .= str_replace(" ",'\\ ',$xslroot);
		}
		$commandline .= ' ';
		if(DIRECTORY_SEPARATOR == '\\')
		{
			$commandline .= '"'.$this->docbook_file.'"';
		}
		else
		{
			$commandline .= str_replace(" ",'\\ ',$this->docbook_file);
		}
		$tempResult = shell_exec( $commandline );
		unset($tempResult);

		// Scan for HTML files
		$this->log("Scanning for generated XHTML files");
		$handle = opendir($tempdir);
		$id = 0;
		$files = array();
		while( $file = readdir($handle) )
		{
			if( fnmatch('*.html', $file) && !is_dir($file) )
			{
				$files[$file] = ++$id;
			}
		}
		closedir($handle);
		unset($handle); unset($file); unset($id);

		// Scan for image files
		$this->log("Scanning for image files");
		$handle = opendir($this->imagefile_directory);
		$id = 0;
		$images = array();
		while( $file = readdir($handle) )
		{
			if( !is_dir($file) && in_array($this->getFileExtension($file), array('png','jpg','jpeg','gif','bmp')) )
			{
				$images[$file] = ++$id;
			}
		}
		closedir($handle);
		unset($handle); unset($file); unset($id);

		// Parse HTML files
		$this->log("Parsing HTML files");
		$ordTable = array();
		$usedImages = array();
		foreach( $files as $filename => $id)
		{
			$test = $this->processHTML($tempdir.DS.$filename, $id);
			unset($test);
		}

		// Create the JPA archiver instance
		$archiver =& new JPAMaker;
		$archiver->create($this->docimport_package);

		// Generate packing list
		$this->log("Generating package list");
		$packlist = '[images]'."\n";
		foreach( $images as $filename => $id )
		{
			$packlist .= 'image'.$id.'="'.$filename.'"'."\n";
		}
		$packlist .= "[articles]\n";
		foreach( $files as $filename => $id )
		{
			$filename=str_replace('.html','',$filename);
			$packlist .= 'file'.$id.'="'.$filename.'"'."\n";
		}
		$packlist .= "[titles]\n";
		foreach( $titles as $filename => $title )
		{
			$filename=str_replace('.html','',$filename);
			$packlist .= $filename.'="'.$title.'"'."\n";
		}
		$packlist .= "[order]\n";
		foreach( $ordTable as $filename => $order )
		{
			$filename=str_replace('.html','',$filename);
			$packlist .= $filename.'='.$order."\n";
		}
		// Add packing list
		file_put_contents($tempdir.DS.'packlist.tmp', $packlist);
		$archiver->addFile($tempdir.DS.'packlist.tmp', 'packlist');
		unlink($tempdir.DS.'packlist.tmp');

		// Add processed HTML files; the idea is to follow the article order
		$this->log("Adding processed HTML to the archive");
		foreach( $ordTable as $filename => $order )
		{
			$id = $files[$filename];
			$myfile = $tempdir.DS.'file'.$id.'.dat';
			$archiver->addFile($myfile, 'file'.$id.'.dat');
			unlink($myfile);
		}

		$this->log("Adding images to the archive");
		foreach( $images as $filename => $id )
		{
			$archiver->addFile($this->imagefile_directory.DS.$filename, 'image'.$id.'.dat');
		}

		// Finalize archive
		$archiver->finalize();

		// Cleanup
		$this->log("Cleaning up");
		$this->advancedRmdir($tempdir);

		$this->log("Max memory used: ". memory_get_peak_usage());
	}

	function advancedRmdir($path) {
		$origipath = $path;
		$handler = opendir($path);
		while (true) {
			$item = readdir($handler);
			if ($item == "." or $item == "..") {
				continue;
			} elseif (gettype($item) == "boolean") {
				closedir($handler);
				if (!@rmdir($path)) {
					return false;
				}
				if ($path == $origipath) {
					break;
				}
				$path = substr($path, 0, strrpos($path, "/"));
				$handler = opendir($path);
			} elseif (is_dir($path."/".$item)) {
				closedir($handler);
				$path = $path."/".$item;
				$handler = opendir($path);
			} else {
				unlink($path."/".$item);
			}
		}
		return true;
	}

	function getFileExtension($filepath)
	{
		preg_match('/[^?]*/', $filepath, $matches);
		$string = $matches[0];

		$pattern = preg_split('/\./', $string, -1, PREG_SPLIT_OFFSET_CAPTURE);

		# check if there is any extension
		if(count($pattern) == 1)
		{
			return '';
		}

		if(count($pattern) > 1)
		{
			$filenamepart = $pattern[count($pattern)-1][0];
			preg_match('/[^?]*/', $filenamepart, $matches);
			return $matches[0];
		}
	}

	function processHTML($filename, $thisid)
	{
		global $titles, $files, $images, $tempdir, $ordTable, $usedImages;

		$filedata = file_get_contents($filename);

		if(basename($filename) == 'index.html')
		{
			$error_reporting = error_reporting(E_ERROR);
			$domdoc = new DOMDocument();
			$success = $domdoc->loadXML($filedata);

			if(!$success) die('ERROR: '.$domdoc->getErrorString());

			$order = 0;
			$ordTable = array(
					'index.html'	=> 0
			);
			// Get a list of anchor elements (<a href="...">)
			$anchors =& $domdoc->getElementsByTagName('a');
			foreach($anchors as $anchor)
			{
				// Grab the href
				$href = $anchor->getAttribute('href');
				// Kill any page anchors from the URL, e.g. #some-anchor
				$hashlocation = strpos($href, '#');
				if($hashlocation !== false)
				{
					$href = substr($href, 0, $hashlocation);
				}
				// Only precess if this page is not already found
				if(!array_key_exists($href, $ordTable) && ($href != ''))
				{
					if(substr($href, 0, 7) != 'mailto:')
					{
						$order++;
						$ordTable[$href] = $order;
					}
				}

				unset($href);
				unset($anchor);

				error_reporting($error_reporting);
			}
			unset($anchors);
		}

		// Extract the title
		$startOfTitle = strpos($filedata, '<title>') + 7;
		$endOfTitle = strpos($filedata, '</title>');
		$title = substr($filedata, $startOfTitle, $endOfTitle - $startOfTitle);

		// Extract the body
		$startOfContent = strpos($filedata, '<body>') + 6;
		$endOfContent = strpos($filedata, '</body>');
		$filedata = '<div id="docimport">' . substr($filedata, $startOfContent, $endOfContent - $startOfContent) . '</div>';

		// Store the title
		$titles[basename($filename)] = $title;

		// Replace links to other XHTML files with {{fileXX}}
		foreach($files as $filename => $id)
		{
			$filedata = str_replace('href="'.$filename, 'href="{{file'.$id.'}}', $filedata);
		}

		// Replace links to image files with {{imageXX}}
		$imageroot = $this->docbook_relimages;
		foreach($images as $filename => $id)
		{
			$occurence = strpos($filedata, 'src="'.$imageroot.'/'.$filename);
			if($occurence !== false)
			{
				$usedImages[basename($filename)] = true; // Mark this image as used
			}
			$filedata = str_replace('src="'.$imageroot.'/'.$filename, 'src="{{image'.$id.'}}', $filedata);
		}

		// Save the result to the archive
		file_put_contents($tempdir.DS.'file'.$thisid.'.dat', $filedata);
	}
}