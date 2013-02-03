<?php

$component = 'com_ars';
$txproject = 'ars';
$root = dirname(__FILE__);

$action = "scan_leaf_txinit";
$symlink_lang = 'pt-BR';

/**
 * Does a translation already exist in .tx/config?
 * 
 * @param   string  $key   The translation key to check
 * @param   string  $path  The translation path to check (overrides key)
 * 
 * @return  boolean True if the key already exists
 */
function does_translation_exist($key, $path = null)
{
	static $translations = null;
	static $paths = null;
	
	if (is_null($translations))
	{
		$rawData = parse_ini_file(__DIR__.'/.tx/config', true);
		$translations = array_keys($rawData);
		foreach($rawData as $section => $data)
		{
			if (!isset($data['file_filter']))
			{
				continue;
			}
			$path = substr($data['file_filter'], 0, strpos('<lang>', $data['file_filter']) - 1);
			$paths[] = $path;
		}
	}
	
	if (in_array($key, $translations))
	{
		return true;
	}
	elseif(!empty($path))
	{
		return in_array($path, $paths);
	}
	
	return false;
}

/**
 * Process a translation INI file, converting from Joomla! 1.5 to 1.6+ format.
 * The original file is replaced with the fixed file.
 * 
 * @param   string  $file  The full path to the file to be fixed
 */
function fix_file($file)
{
	echo basename($file)."\n";
	$fp = fopen($file, 'rt');
	if($fp == false) die('Could not open file.');
	$out = '';
	while(!feof($fp)) {
		$line = fgets($fp);
		$trimmed = trim($line);

		// Transform comments
		if(substr($trimmed,0,1) == '#') {
			$out .= ';'.substr($trimmed,1)."\n";
			continue;
		}

		if(substr($trimmed,0,1) == ';') {
			$out .= "$trimmed\n";
			continue;
		}

		// Detect blank lines
		if(empty($trimmed)) {
			$out .= "\n";
			continue;
		}

		// Process key-value pairs
		list($key, $value) = explode('=', $trimmed, 2);
		$value = trim($value, '"');
		$value = str_replace('\\"', "'", $value);
		$value = str_replace('"_QQ_"', "'", $value);
		$value = str_replace('"', "'", $value);
		$key = strtoupper($key);
		$key = trim($key);
		$out .= "$key=\"$value\"\n";
	}
	fclose($fp);

	file_put_contents($file, $out);
}

function scan_leaf_fixfile($slugArray, $rootDir)
{
	foreach(new DirectoryIterator($rootDir) as $oLang)
	{
		if(!$oLang->isDir()) continue;
		if($oLang->isDot()) continue;
		$lang = $oLang->getFilename();
		
		$files = glob($rootDir.'/'.$lang.'/*.ini');
		foreach($files as $f) {
			fix_file($f);
		}
	}
}

function scan_leaf_symlink($slugArray, $rootDir)
{
	global $symlink_lang;
	
	foreach(new DirectoryIterator($rootDir) as $oLang)
	{
		if(!$oLang->isDir()) continue;
		if($oLang->isDot()) continue;
		$lang = $oLang->getFilename();
		
		$files = glob($rootDir.'/'.$lang.'/*.ini');
		foreach($files as $f) {
			if(substr(basename($f), 0, 5) != $symlink_lang) continue;
			echo basename($f)."\n";
			#copy($f, __DIR__.'/000/'.basename($f));
			copy(__DIR__.'/000/'.basename($f), $f);
		}
	}
}

function scan_leaf_txinit($slugArray, $rootDir) {
	global $root, $txproject;
	
	if(!file_exists('tx.sh')) {
		file_put_contents('tx.sh', '#!/bin/bash'."\n");
		chmod('tx.sh', 0755);
	}
	
	$files = glob($rootDir.'/en-GB/*.ini');
	$slug = implode($slugArray, '_');
	foreach($files as $f) {
		
		if(substr($f, -8) == '.sys.ini') {
			$slug .= '_sys';
		} elseif(substr($f, -9) == '.menu.ini') {
			$slug .= '_menu';
		} else {
			$slug .= '_main';
		}
		
		$file_proto = basename($f);
		$file_proto = substr($file_proto, 5);
		$file_proto = $rootDir.'/<lang>/<lang>'.$file_proto;
		$file_proto = substr($file_proto, strlen($root)+1);
		
		$checkPath = substr($rootDir, strlen($root) + 1);
		
		if(!does_translation_exist($txproject.'.'.$slug, $checkPath))
		{
			echo $rootDir."\n";
			$cmd = "tx set --auto-local -r $txproject.$slug '$file_proto' --source-lang en-GB";
			$cmd .= ' --execute';

			//passthru($cmd);
			$fp = fopen('tx.sh', 'at');
			fwrite($fp, $cmd."\n");
			fclose($fp);
		}
	}
}

$myRoot = $root.'/translations';
foreach(new DirectoryIterator($myRoot) as $oArea)
{
	if(!$oArea->isDir()) continue;
	if($oArea->isDot()) continue;
	$area = $oArea->getFilename();
	
	$areaDir = $myRoot.'/'.$area;
	$slug = array();
	switch($area) {
		case 'component':
			$slug[] = $component;
			break;
		
		case 'modules':
			$slug[] = 'mod';
			break;
		
		case 'plugins':
			$slug[] = 'plg';
			break;
		
		default:
			break;
	}
	
	if(empty($slug)) continue;
	
	foreach(new DirectoryIterator($areaDir) as $oFolder)
	{
		if(!$oFolder->isDir()) continue;
		if($oFolder->isDot()) continue;
		$folder = $oFolder->getFilename();
		
		$slug[] = $folder;
		$folderDir = $areaDir.'/'.$folder;
		
		if(is_dir($folderDir.'/en-GB')) {
			// A component
			call_user_func($action, $slug, $folderDir);
		} else {
			// A module or plugin
			foreach(new DirectoryIterator($folderDir) as $oExtension)
			{
				if(!$oExtension->isDir()) continue;
				if($oExtension->isDot()) continue;
				$extension = $oExtension->getFilename();
				
				$slug[] = $extension;
				$extensionDir = $folderDir.'/'.$extension;
				if(is_dir($extensionDir.'/en-GB')) {
					call_user_func($action, $slug, $extensionDir);
				}
				array_pop($slug);
			}
		}
		
		array_pop($slug);
	}
}