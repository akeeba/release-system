<?php
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

/**
 * Incoming var from the include
 * @var     string  $option     Component name, ie com_foobar
 * @var     int     $id         Id of the current record
 */

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG;
$sefConfig = & shRouter::shGetConfig();  
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin( $lang, $shLangName, $shLangIso, $option);
if ($dosef == false) return;
// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//$shLangIso = shLoadPluginLanguage( 'com_XXXXX', $shLangIso, '_SEF_SAMPLE_TEXT_STRING');
// ------------------  load language file - adjust as needed ----------------------------------------

if( !function_exists( 'shArsCategoryName' ))
{
    function shArsCategoryName($id)
    {
        $sefConfig = shRouter::shGetConfig();
        $database  = JFactory::getDBO();

        $database->setQuery( "SELECT alias FROM #__ars_categories WHERE id = ".$id );
        $name = str_replace( '.', $sefConfig->replacement, $database->loadResult() );

        return $name;
    }
}

if( !function_exists( 'shArsReleaseName' ))
{
    function shArsReleaseName($id)
    {
        $sefConfig = shRouter::shGetConfig();
        $database  = JFactory::getDBO();

        $database->setQuery( "SELECT alias FROM #__ars_releases WHERE id = ".$id );
        $name = str_replace( '.', $sefConfig->replacement, $database->loadResult() );

        return $name;
    }
}

if( !function_exists( 'shArsItemName' ))
{
    function shArsItemName($id)
    {
        $sefConfig = shRouter::shGetConfig();
        $database  = JFactory::getDBO();

        $database->setQuery( "SELECT alias FROM #__ars_items WHERE id = ".$id );
        $name = str_replace( '.', $sefConfig->replacement, $database->loadResult() );

        return $name;
    }
}

if( !function_exists( 'shArsGetCategoryFromRelease' ))
{
    function shArsGetCategoryFromRelease($id)
    {
        $database  = JFactory::getDBO();

        $database->setQuery( "SELECT category_id FROM #__ars_releases WHERE id = ".$id );
        $catid = $database->loadResult();

        return $catid;
    }
}

if( !function_exists( 'shArsGetReleaseFromItem' ))
{
    function shArsGetReleaseFromItem($id)
    {
        $database  = JFactory::getDBO();

        $database->setQuery( "SELECT release_id FROM #__ars_items WHERE id = ".$id );
        $catid = $database->loadResult();

        return $catid;
    }
}

if (!function_exists( 'shArsDownloadMenuName'))
{
    function shArsDownloadMenuName($task, $Itemid, $option, $shLangName)
    {
        $sefConfig           = &shRouter::shGetConfig();
        $shArsDownloadName = shGetComponentPrefix($option);

        if( empty($shArsDownloadName) ) $shArsDownloadName = getMenuTitle($option, $task, $Itemid, null, $shLangName);
        if( empty($shArsDownloadName) || $shArsDownloadName == '/' ) $shArsDownloadName = 'AkeebaReleaseSystem';

        return str_replace( '.', $sefConfig->replacement, $shArsDownloadName );
    }
}

// remove common URL from GET vars list, so that they don't show up as query string in the URL
shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('lang');
shRemoveFromGETVarsList('view');
shRemoveFromGETVarsList('id');
shRemoveFromGETVarsList('layout');
shRemoveFromGETVarsList('format');
if( isset($Itemid) ) shRemoveFromGETVarsList('Itemid');

// make sure we don't error out on accessing undefined variables
$task    = isset($task) ? @$task : null;
$Itemid  = isset($Itemid) ? @$Itemid : null;
$title[] = shArsDownloadMenuName($task, $Itemid, $option, $shLangName);

switch ($view)
{
    case 'category':
        $title[] = shArsCategoryName($id);
        break;
    case 'release':
        $catid   = shArsGetCategoryFromRelease($id);
        $title[] = shArsCategoryName($id);
        $title[] = shArsReleaseName($id);
        break;
    case 'download':
        $relid   = shArsGetReleaseFromItem($id);
        $catid   = shArsGetCategoryFromRelease($relid);
        $title[] = shArsCategoryName($catid);
        $title[] = shArsReleaseName($relid);
        $title[] = shArsItemName($id);
        break;
}

$title[] = '/';

// ------------------  standard plugin finalize function - don't change ---------------------------  
if ($dosef){
   $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString, 
      (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null), 
      (isset($shLangName) ? @$shLangName : null));
}      
// ------------------  standard plugin finalize function - don't change ---------------------------
  