<?php
/**
 * sh404SEF support for com_XXXXX component.
 * Author : 
 * contact :
 * 
 * This is a sample sh404SEF native plugin file
 *    
 */
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

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

// remove common URL from GET vars list, so that they don't show up as query string in the URL
shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('lang');

if (!empty($Itemid))
{
    shRemoveFromGETVarsList('Itemid');
}

if (!empty($limit))  
{
    shRemoveFromGETVarsList('limit');
}

if (isset($limitstart)) 
{
    shRemoveFromGETVarsList('limitstart');
}
    

// start by inserting the menu element title (just an idea, this is not required at all)
$task         = isset($task) ? $task : null;
$Itemid       = isset($Itemid) ? $Itemid : null;
$shSampleName = shGetComponentPrefix($option); 
$shSampleName = empty($shSampleName) ? getMenuTitle($option, $task, $Itemid, null, $shLangName) : $shSampleName;
$shSampleName = (empty($shSampleName) || $shSampleName == '/') ? 'SampleCom':$shSampleName;

switch ($task) {
  case 'task1':
  case 'task2' :
    $dosef = false;  // these tasks do not require SEF URL
  break;

  default:
    $title[] = $sh_LANG[$shLangIso]['COM_SH404SEF_VIEW_SAMPLE'];// insert a 'View sample' string, 
                                                               // according to language
                                                               // only if you have defined the 
    if (!empty($sampleId)) {                                   // fetch some data about the content
      $q = 'SELECT id, title FROM #__samplenames WHERE id = '.$database->Quote($sampleId);  // select clause includes id, even if
      $database->setQuery($q);                                 // we don't need it, in order for Joomfish to 
                                                               // return a translated version 
      
      if (shTranslateUrl($option, $shLangName))               // get it in the right language
        $sampleTitle = $database->loadObject( );
      else $sampleTitle = $database->loadObject( false);        // second param at false forces Joomfish to 
                                                               // return default language version instead of
                                                               // current language
      
      if ($sampleTitle) {                                      // if we found a title for this element
        $title[] = $sampleTitle->title;                        // insert it in URL array
        shRemoveFromGETVarsList('sampleId');                   // remove sampleId var from GET vars list
                                                               // as we have found a text equivalent
        shMustCreatePageId( 'set', true);                      // NEW: ask sh404sef to create a short URL for this SEF URL (pageId)                                                        
      }  
    }
    shRemoveFromGETVarsList('task');                           // also remove task, as it is not needed
                                                               // because we can revert the SEF URL without
                                                               // it
}
  
// ------------------  standard plugin finalize function - don't change ---------------------------  
if ($dosef){
   $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString, 
      (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null), 
      (isset($shLangName) ? @$shLangName : null));
}      
// ------------------  standard plugin finalize function - don't change ---------------------------
  