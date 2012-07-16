<?php
require_once '../phingext/pclzip.php';

function scan($root)
{
	$ret = array();
	
	// Scan component frontend languages
	_mergeLangRet($ret, _scanLangDir($root.'/component/frontend'), 'frontend');

	// Scan component backend languages
	_mergeLangRet($ret, _scanLangDir($root.'/component/backend'), 'backend');

	// Scan modules, admin
	try {
		foreach(new DirectoryIterator($root.'/modules/admin') as $mname) {
			if($mname->isDot()) continue;
			if(!$mname->isDir()) continue;
			$module = $mname->getFilename();
			_mergeLangRet($ret, _scanLangDir($root.'/modules/admin/'.$module), 'backend');
		}
	} catch (Exception $exc) {
		//echo $exc->getTraceAsString();
	}

	// Scan modules, site
	try {
		foreach(new DirectoryIterator($root.'/modules/site') as $mname) {
			if($mname->isDot()) continue;
			if(!$mname->isDir()) continue;
			$module = $mname->getFilename();
			_mergeLangRet($ret, _scanLangDir($root.'/modules/site/'.$module), 'backend');
		}
	} catch (Exception $exc) {
		//echo $exc->getTraceAsString();
	}
		
	// Scan plugins
	try {
		foreach(new DirectoryIterator($root.'/plugins') as $fldname) {
			if($fldname->isDot()) continue;
			if(!$fldname->isDir()) continue;
			$path = $root.'/plugins/'.$fldname->getFilename();
			// Scan this folder for plugins
			try {
				foreach(new DirectoryIterator($path) as $pname) {
					if($pname->isDot()) continue;
					if(!$pname->isDir()) continue;
					$plugin = $pname->getFilename();
					_mergeLangRet($ret, _scanLangDir($path.'/'.$plugin), 'backend');
				}
			} catch (Exception $exc) {
				//echo $exc->getTraceAsString();
			}
		}
	} catch (Exception $exc) {
		//echo $exc->getTraceAsString();
	}
		
	return $ret;
}

function _mergeLangRet(&$ret, $temp, $area = 'frontend')
{
	foreach($temp as $lang => $files) {
		$existing = array();
		if(array_key_exists($lang, $ret)) {
			if(array_key_exists($area, $ret[$lang])) {
				$existing = $ret[$lang][$area];
			}
		}
		$ret[$lang][$area] = array_merge($existing, $files);
	}
}

function _scanLangDir($path)
{
	$langs = array();
	try {
		foreach(new DirectoryIterator($path) as $file) {
			if($file->isDot()) continue;
			if(!$file->isDir()) continue;
			$langs[] = $file->getFileName();
		}
	} catch (Exception $exc) {
		//echo $exc->getTraceAsString();
	}
	
	$ret = array();
	foreach($langs as $lang) {
		try {
			foreach(new DirectoryIterator($path.'/'.$lang) as $file) {
				if(!$file->isFile()) continue;
				$fname = $file->getFileName();
				if(substr($fname,-4) != '.ini') continue;
				$ret[$lang][] = $path.'/'.$lang.'/'.$fname;
			}
		} catch (Exception $exc) {
			//echo $exc->getTraceAsString();
		}
	}
	
	return $ret;
}

echo <<<ENDBANNER
BuildLang 1.0
Copyright (c)2012 Nicholas K. Dionysopoulos - AkeebaBackup.com


ENDBANNER;

// Load the properties
$props = parse_ini_file(dirname(__FILE__).'/../build.properties');

// Get some basic parameters
$packageName = $props['langbuilder.packagename'];
$softwareName = $props['langbuilder.software'];

// Instanciate S3
require_once('S3.php');
$s3 = new S3($props['s3.access'], $props['s3.private']);
$s3Bucket = $props['s3.bucket'];
$s3Path = $props['s3.path'];

// Scan languages
$root = realpath(dirname(__FILE__).'/../../translations');
$langs = scan($root);
ksort($langs);
$numlangs = count($langs);
echo "Found $numlangs languages\n\n";

if($argc > 1) {
	$version = $argv[1];
} else {
	$version = '0.0.'.gmdate('YdmHis');
}

date_default_timezone_set('Europe/Athens');

$date = gmdate('d M Y');
$year = gmdate('Y');

$langToName = parse_ini_file('map.ini');

$langHTMLTable = '';
$row = 1;
foreach($langs as $tag => $files) {
	$langName = $langToName[$tag];
	echo "Building $langName ($tag)...\n";
	
	// Get paths to temp and output files
	@mkdir(realpath(dirname(__FILE__).'/../..').'/release/languages');
	$j15ZIPPath = dirname(__FILE__).'/../../release/languages/'.$packageName.'-'.$tag.'-j15.zip';
	$j20ZIPPath = dirname(__FILE__).'/../../release/languages/'.$packageName.'-'.$tag.'-j25.zip';
	$tempXMLPath = realpath(dirname(__FILE__).'/../..').'/release/'.$tag.'.xml';
	
	// Start new ZIP files
	@unlink($j15ZIPPath);
	$zip15 = new PclZip( $j15ZIPPath );
	@unlink($j20ZIPPath);
	$zip20 = new PclZip( $j20ZIPPath );
	
	// Produce the Joomla! 1.5 manifest contents
	$j15XML = <<<ENDHEAD
<?xml version="1.0" encoding="utf-8"?>
<install version="1.5" client="both" type="language" method="upgrade">
    <name><![CDATA[$packageName-$tag]]></name>
    <tag>$tag</tag>
    <version>$version</version>
    <date>$date</date>
    <author><![CDATA[AkeebaBackup.com]]></author>
    <authorurl>http://www.akeebabackup.com</authorurl>
	<copyright>Copyright (C)$year AkeebaBackup.com. All rights reserved.</copyright>
	<license>http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL</license>
    <description><![CDATA[$langName translation file for $softwareName]]></description>

ENDHEAD;
	
	if(array_key_exists('backend', $files)){
		$j15XML .= "\t<administration>\n\t\t<files folder=\"backend\">\n";
		foreach($files['backend'] as $file) {
			$j15XML .= "\t\t\t<filename>".basename($file)."</filename>\n";
		}
		$j15XML .= "\t\t</files>\n\t</administration>\n";
	}
	if(array_key_exists('frontend', $files)){
		$j15XML .= "\t<site>\n\t\t<files folder=\"frontend\">\n";
		foreach($files['frontend'] as $file) {
			$j15XML .= "\t\t\t<filename>".basename($file)."</filename>\n";
		}
		$j15XML .= "\t\t</files>\n\t</site>\n";
	}
	$j15XML .= "\t<params />\n</install>";
	
	// Produce the Joomla! 1.6/1.7/2.5 manifest contents
	$j20XML = <<<ENDHEAD
<?xml version="1.0" encoding="utf-8"?>
<extension type="file" version="1.6" method="upgrade" client="site">
    <name><![CDATA[$packageName-$tag]]></name>
    <author><![CDATA[AkeebaBackup.com]]></author>
    <authorurl>http://www.akeebabackup.com</authorurl>
	<copyright>Copyright (C)$year AkeebaBackup.com. All rights reserved.</copyright>
	<license>http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL</license>
    <version>$version</version>
    <creationDate>$date</creationDate>
    <description><![CDATA[$langName translation file for $softwareName]]></description>
	<fileset>

ENDHEAD;
	
	if(array_key_exists('backend', $files)){
		$j20XML .= "\t\t<files folder=\"backend\" target=\"administrator/language/$tag\">\n";
		foreach($files['backend'] as $file) {
			$j20XML .= "\t\t\t<filename>".baseName($file)."</filename>\n";
		}
		$j20XML .= "\t\t</files>\n";
	}
	if(array_key_exists('frontend', $files)){
		$j20XML .= "\t\t<files folder=\"frontend\" target=\"language/$tag\">\n";
		foreach($files['frontend'] as $file) {
			$j20XML .= "\t\t\t<filename>".baseName($file)."</filename>\n";
		}
		$j20XML .= "\t\t</files>\n";
	}
	$j20XML .= "\t</fileset>\n</extension>";
	
	// Add the manifest (J! 1.5)
	@unlink($tempXMLPath);
	@file_put_contents($tempXMLPath, $j15XML);
	$zip15->add($tempXMLPath,
			PCLZIP_OPT_ADD_PATH, '', 
			PCLZIP_OPT_REMOVE_PATH, dirname($tempXMLPath)
	);
	@unlink($tempXMLPath);
	
	// Add the manifest (J! 2.x)
	@unlink($tempXMLPath);
	@file_put_contents($tempXMLPath, $j20XML);
	$zip20->add($tempXMLPath,
			PCLZIP_OPT_ADD_PATH, '', 
			PCLZIP_OPT_REMOVE_PATH, dirname($tempXMLPath)
	);
	@unlink($tempXMLPath);

	// Add back-end files to archives
	if(array_key_exists('backend', $files)){
		foreach($files['backend'] as $file) {
			$zip15->add($file,
                	PCLZIP_OPT_ADD_PATH, 'backend' ,
                	PCLZIP_OPT_REMOVE_PATH, dirname($file) );
			$zip20->add($file,
                	PCLZIP_OPT_ADD_PATH, 'backend' ,
                	PCLZIP_OPT_REMOVE_PATH, dirname($file) );
		}
	}
	// Add front-end files to archives
	if(array_key_exists('frontend', $files)){
		foreach($files['frontend'] as $file) {
			$zip15->add($file,
                	PCLZIP_OPT_ADD_PATH, 'frontend' ,
                	PCLZIP_OPT_REMOVE_PATH, dirname($file) );
			$zip20->add($file,
                	PCLZIP_OPT_ADD_PATH, 'frontend' ,
                	PCLZIP_OPT_REMOVE_PATH, dirname($file) );
		}
	}
	
	// Close archives
	unset($zip15);
	unset($zip20);
	
	$parts = explode('-', $tag);
	$country = strtolower($parts[1]);
	if($tag == 'ca-ES') {
		$country = 'catalonia';
	}
	
	$base15 = basename($j15ZIPPath);
	$base20 = basename($j20ZIPPath);
	
	$row = 1 - $row;
	$langHTMLTable .= <<<ENDHTML
	<tr class="row$row">
		<td width="16"><img src="http://cdn.akeebabackup.com/language/flags/$country.png" /></td>
		<td width="50" align="center"><tt>$tag</tt></td>
		<td width="250">$langName</td>
		<td>
			Download for
			<a href="http://cdn.akeebabackup.com/language/$packageName/$base15">Joomla! 1.5</a>
			or
			<a href="http://cdn.akeebabackup.com/language/$packageName/$base20">Joomla! 1.6/1.7/2.5</a>
		</td>
	</tr>

ENDHTML;

	// @todo Upload translation files
	echo "\tUploading ".basename($j15ZIPPath)."\n";
	$s3->putObjectFile($j15ZIPPath, $s3Bucket, $s3Path.'/'.$packageName.'/'.basename($j15ZIPPath), S3::ACL_PUBLIC_READ);
	echo "\tUploading ".basename($j20ZIPPath)."\n";
	$s3->putObjectFile($j20ZIPPath, $s3Bucket, $s3Path.'/'.$packageName.'/'.basename($j20ZIPPath), S3::ACL_PUBLIC_READ);
}

$html = @file_get_contents(dirname(__FILE__).'/../../translations/_pages/index.html');
$html = str_replace('[DATE]', gmdate('d M Y H:i:s'), $html);
$html = str_replace('[LANGTABLE]', $langHTMLTable, $html);
$html = str_replace('[YEAR]', gmdate('Y'), $html);

echo "Uploading index.html file\n";
$tempHTMLPath = realpath(dirname(__FILE__).'/../..').'/release/index.html';
@file_put_contents($tempHTMLPath, $html);
$s3->putObjectFile($tempHTMLPath, $s3Bucket, $s3Path.'/'.$packageName.'/index.html', S3::ACL_PUBLIC_READ);
@unlink($tempHTMLPath);

echo "\nDone\n\n";