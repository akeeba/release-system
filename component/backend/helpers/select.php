<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class ArsHelperSelect
{
	private static $countries = array(
		'' => '----',
		'AD' =>'Andorra', 'AE' =>'United Arab Emirates', 'AF' =>'Afghanistan',
		'AG' =>'Antigua and Barbuda', 'AI' =>'Anguilla', 'AL' =>'Albania',
		'AM' =>'Armenia', 'AN' =>'Netherlands Antilles', 'AO' =>'Angola',
		'AQ' =>'Antarctica', 'AR' =>'Argentina', 'AS' =>'American Samoa',
		'AT' =>'Austria', 'AU' =>'Australia', 'AW' =>'Aruba',
		'AX' =>'Aland Islands', 'AZ' =>'Azerbaijan', 'BA' =>'Bosnia and Herzegovina',
		'BB' =>'Barbados', 'BD' =>'Bangladesh',	'BE' =>'Belgium',
		'BF' =>'Burkina Faso', 'BG' =>'Bulgaria', 'BH' =>'Bahrain',
		'BI' =>'Burundi', 'BJ' =>'Benin', 'BL' =>'Saint Barthélemy',
		'BM' =>'Bermuda', 'BN' =>'Brunei Darussalam', 'BO' =>'Bolivia, Plurinational State of',
		'BR' =>'Brazil', 'BS' =>'Bahamas', 'BT' =>'Bhutan', 'BV' =>'Bouvet Island',
		'BW' =>'Botswana', 'BY' =>'Belarus', 'BZ' =>'Belize', 'CA' =>'Canada',
		'CC' =>'Cocos (Keeling) Islands', 'CD' =>'Congo, the Democratic Republic of the',
		'CF' =>'Central African Republic', 'CG' =>'Congo', 'CH' =>'Switzerland',
		'CI' =>'Cote d\'Ivoire', 'CK' =>'Cook Islands', 'CL' =>'Chile',
		'CM' =>'Cameroon', 'CN' =>'China', 'CO' =>'Colombia', 'CR' =>'Costa Rica',
		'CU' =>'Cuba', 'CV' =>'Cape Verde', 'CX' =>'Christmas Island', 'CY' =>'Cyprus',
		'CZ' =>'Czech Republic', 'DE' =>'Germany', 'DJ' =>'Djibouti', 'DK' =>'Denmark',
		'DM' =>'Dominica', 'DO' =>'Dominican Republic', 'DZ' =>'Algeria',
		'EC' =>'Ecuador', 'EE' =>'Estonia', 'EG' =>'Egypt', 'EH' =>'Western Sahara',
		'ER' =>'Eritrea', 'ES' =>'Spain', 'ET' =>'Ethiopia', 'FI' =>'Finland',
		'FJ' =>'Fiji', 'FK' =>'Falkland Islands (Malvinas)', 'FM' =>'Micronesia, Federated States of',
		'FO' =>'Faroe Islands', 'FR' =>'France', 'GA' =>'Gabon', 'GB' =>'United Kingdom',
		'GD' =>'Grenada', 'GE' =>'Georgia', 'GF' =>'French Guiana', 'GG' =>'Guernsey',
		'GH' =>'Ghana', 'GI' =>'Gibraltar', 'GL' =>'Greenland', 'GM' =>'Gambia',
		'GN' =>'Guinea', 'GP' =>'Guadeloupe', 'GQ' =>'Equatorial Guinea', 'GR' =>'Greece',
		'GS' =>'South Georgia and the South Sandwich Islands', 'GT' =>'Guatemala',
		'GU' =>'Guam', 'GW' =>'Guinea-Bissau', 'GY' =>'Guyana', 'HK' =>'Hong Kong',
		'HM' =>'Heard Island and McDonald Islands', 'HN' =>'Honduras', 'HR' =>'Croatia',
		'HT' =>'Haiti', 'HU' =>'Hungary', 'ID' =>'Indonesia', 'IE' =>'Ireland',
		'IL' =>'Israel', 'IM' =>'Isle of Man', 'IN' =>'India', 'IO' =>'British Indian Ocean Territory',
		'IQ' =>'Iraq', 'IR' =>'Iran, Islamic Republic of', 'IS' =>'Iceland',
		'IT' =>'Italy', 'JE' =>'Jersey', 'JM' =>'Jamaica', 'JO' =>'Jordan',
		'JP' =>'Japan', 'KE' =>'Kenya', 'KG' =>'Kyrgyzstan', 'KH' =>'Cambodia',
		'KI' =>'Kiribati', 'KM' =>'Comoros', 'KN' =>'Saint Kitts and Nevis',
		'KP' =>'Korea, Democratic People\'s Republic of', 'KR' =>'Korea, Republic of',
		'KW' =>'Kuwait', 'KY' =>'Cayman Islands', 'KZ' =>'Kazakhstan',
		'LA' =>'Lao People\'s Democratic Republic', 'LB' =>'Lebanon',
		'LC' =>'Saint Lucia', 'LI' =>'Liechtenstein', 'LK' =>'Sri Lanka',
		'LR' =>'Liberia', 'LS' =>'Lesotho', 'LT' =>'Lithuania', 'LU' =>'Luxembourg',
		'LV' =>'Latvia', 'LY' =>'Libyan Arab Jamahiriya', 'MA' =>'Morocco',
		'MC' =>'Monaco', 'MD' =>'Moldova, Republic of', 'ME' =>'Montenegro',
		'MF' =>'Saint Martin (French part)', 'MG' =>'Madagascar', 'MH' =>'Marshall Islands',
		'MK' =>'Macedonia, the former Yugoslav Republic of', 'ML' =>'Mali',
		'MM' =>'Myanmar', 'MN' =>'Mongolia', 'MO' =>'Macao', 'MP' =>'Northern Mariana Islands',
		'MQ' =>'Martinique', 'MR' =>'Mauritania', 'MS' =>'Montserrat', 'MT' =>'Malta',
		'MU' =>'Mauritius', 'MV' =>'Maldives', 'MW' =>'Malawi', 'MX' =>'Mexico',
		'MY' =>'Malaysia', 'MZ' =>'Mozambique', 'NA' =>'Namibia', 'NC' =>'New Caledonia',
		'NE' =>'Niger', 'NF' =>'Norfolk Island', 'NG' =>'Nigeria', 'NI' =>'Nicaragua',
		'NL' =>'Netherlands', 'NO' =>'Norway', 'NP' =>'Nepal', 'NR' =>'Nauru', 'NU' =>'Niue',
		'NZ' =>'New Zealand', 'OM' =>'Oman', 'PA' =>'Panama', 'PE' =>'Peru', 'PF' =>'French Polynesia',
		'PG' =>'Papua New Guinea', 'PH' =>'Philippines', 'PK' =>'Pakistan', 'PL' =>'Poland',
		'PM' =>'Saint Pierre and Miquelon', 'PN' =>'Pitcairn', 'PR' =>'Puerto Rico',
		'PS' =>'Palestinian Territory, Occupied', 'PT' =>'Portugal', 'PW' =>'Palau',
		'PY' =>'Paraguay', 'QA' =>'Qatar', 'RE' =>'Reunion', 'RO' =>'Romania',
		'RS' =>'Serbia', 'RU' =>'Russian Federation', 'RW' =>'Rwanda', 'SA' =>'Saudi Arabia',
		'SB' =>'Solomon Islands', 'SC' =>'Seychelles', 'SD' =>'Sudan', 'SE' =>'Sweden',
		'SG' =>'Singapore', 'SH' =>'Saint Helena, Ascension and Tristan da Cunha',
		'SI' =>'Slovenia', 'SJ' =>'Svalbard and Jan Mayen', 'SK' =>'Slovakia',
		'SL' =>'Sierra Leone', 'SM' =>'San Marino', 'SN' =>'Senegal', 'SO' =>'Somalia',
		'SR' =>'Suriname', 'ST' =>'Sao Tome and Principe', 'SV' =>'El Salvador',
		'SY' =>'Syrian Arab Republic', 'SZ' =>'Swaziland', 'TC' =>'Turks and Caicos Islands',
		'TD' =>'Chad', 'TF' =>'French Southern Territories', 'TG' =>'Togo',
		'TH' =>'Thailand', 'TJ' =>'Tajikistan', 'TK' =>'Tokelau', 'TL' =>'Timor-Leste',
		'TM' =>'Turkmenistan', 'TN' =>'Tunisia', 'TO' =>'Tonga', 'TR' =>'Turkey',
		'TT' =>'Trinidad and Tobago', 'TV' =>'Tuvalu', 'TW' =>'Taiwan, Province of China',
		'TZ' =>'Tanzania, United Republic of', 'UA' =>'Ukraine', 'UG' =>'Uganda',
		'UM' =>'United States Minor Outlying Islands', 'US' =>'United States',
		'UY' =>'Uruguay', 'UZ' =>'Uzbekistan', 'VA' =>'Holy See (Vatican City State)',
		'VC' =>'Saint Vincent and the Grenadines', 'VE' =>'Venezuela, Bolivarian Republic of',
		'VG' =>'Virgin Islands, British', 'VI' =>'Virgin Islands, U.S.', 'VN' =>'Viet Nam',
		'VU' =>'Vanuatu', 'WF' =>'Wallis and Futuna', 'WS' =>'Samoa', 'YE' =>'Yemen',
		'YT' =>'Mayotte', 'ZA' =>'South Africa', 'ZM' =>'Zambia', 'ZW' =>'Zimbabwe'
	);

	public static function decodeCountry($cCode)
	{
		if(array_key_exists($cCode, self::$countries))
		{
			return self::$countries[$cCode];
		}
		else
		{
			return $cCode;
		}
	}

	protected static function genericlist($list, $name, $attribs, $selected, $idTag)
	{
		if(empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';
			foreach($attribs as $key=>$value)
			{
				$temp .= $key.' = "'.$value.'"';
			}
			$attribs = $temp;
		}
		
		return JHTML::_('select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	public static function categorytypes($selected = null, $id = 'type', $attribs = array() )
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_ARS_CATEGORIES_TYPE_SELECT').' -');
		$options[] = JHTML::_('select.option','normal',JText::_('COM_ARS_CATEGORIES_TYPE_NORMAL'));
		$options[] = JHTML::_('select.option','bleedingedge',JText::_('COM_ARS_CATEGORIES_TYPE_BLEEDINGEDGE'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function booleanlist( $name, $attribs = null, $selected = null )
	{
		$options = array(
			JHTML::_('select.option','','---'),
			JHTML::_('select.option',  '0', JText::_( 'JNo' ) ),
			JHTML::_('select.option',  '1', JText::_( 'JYes' ) )
		);
		return self::genericlist($options, $name, $attribs, $selected, $name);
	}

	public static function countries($selected = null, $id = 'country', $attribs = array())
	{
		$options = array(
			JHTML::_('select.option','','---'),
		);
		foreach(self::$countries as $code => $name)
		{
			$options[] = JHTML::_('select.option', $code, $name );
		}
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}


	public static function published($selected = null, $id = 'enabled', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_ARS_COMMON_STATE_SELECT_LABEL').' -');
		$options[] = JHTML::_('select.option',0,JText::_('JUNPUBLISHED'));
		$options[] = JHTML::_('select.option',1,JText::_('JPUBLISHED'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function akeebasubsgroups($selected = null, $name = 'groups')
	{
		if(!is_array($selected))
		{
			if(empty($selected)) {
				$selected = array();
			} else {
				$selected = explode(',',$selected);
			}
		}
		
		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/filtering.php';
		$hasAkeebaSubs = ArsHelperFiltering::hasAkeebaSubs();
		
		if($hasAkeebaSubs) {
			$groups = ArsHelperFiltering::getAkeebaGroups();
	
			$html = '';
	
			if(count($groups))
			{
				$options = array();
				foreach($groups as $group) {
					$item = '<input type="checkbox" class="checkbox" name="'.$name.'[]" value="'.$group->id.'" ';
					if(in_array($group->id, $selected)) $item .= ' checked="checked" ';
					$item .= '/> '.$group->title;
					$options[] = $item;
				}
				$html = implode("\n&nbsp;", $options);
			}			
		} else {
			$html = '';
		}

		return $html;
	}

	public static function categories($selected = null, $id = 'category', $attribs = array())
	{
		$items = FOFModel::getTmpInstance('Categories','ArsModel')
			->nobeunpub(1)
			->getItemList(true);

		$options = array();
		$options[] = JHTML::_('select.option',0,'- '.JText::_('COM_ARS_COMMON_CATEGORY_SELECT_LABEL').' -');
		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->id,$item->title);
		}
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function maturities($selected = null, $id = 'maturity', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('COM_ARS_RELEASES_MATURITY_SELECT').' -');
		
		$maturities = array('alpha','beta','rc','stable');
		foreach($maturities as $maturity) $options[] = JHTML::_('select.option',$maturity,JText::_('COM_ARS_RELEASES_MATURITY_'.strtoupper($maturity)));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function releases($selected = null, $id = 'release', $attribs = array(), $category_id = null)
	{
		$model = FOFModel::getTmpInstance('Releases','ArsModel');
		if(!empty($category_id)) $model->setState('category', $category_id);
		if(empty($category_id)) $model->setState('nobeunpub', 1);
		$items = $model->getItemList(true);

		$options = array();

		if(count($items) && empty($category_id))
		{
			$cache = array();
			foreach($items as $item)
			{
				if(!array_key_exists($item->cat_title, $cache)) $cache[$item->cat_title] = array();
				$cache[$item->cat_title][] = (object)array('id' => $item->id, 'version' => $item->version);
			}

			foreach($cache as $category => $releases)
			{
				if(!empty($options)) $options[] = JHTML::_('select.option','</OPTGROUP>');
				$options[] = JHTML::_('select.option','<OPTGROUP>',$category);
				foreach($releases as $release)
				{
					$options[] = JHTML::_('select.option',$release->id,$release->version);
				}
			}
		}
		elseif(count($items)) foreach($items as $item)
		{
			if($item->category_id == $category_id)
				$options[] = JHTML::_('select.option',$item->id,$item->version);
		}

	   array_unshift($options, JHTML::_('select.option',0,'- '.JText::_('COM_ARS_COMMON_SELECT_RELEASE_LABEL').' -'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function itemtypes($selected = null, $id = 'type', $attribs = array())
	{
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_ITEMS_TYPE_SELECT').' -');

		$types = array('file','link');
		foreach($types as $type) $options[] = JHTML::_('select.option',$type,JText::_('LBL_ITEMS_TYPE_'.strtoupper($type)));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function getFiles($selected = null, $release_id = 0, $item_id = 0, $id = 'type', $attribs = array())
	{
		require_once JPATH_ADMINISTRATOR.'/components/com_ars/helpers/amazons3.php';
		
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_ITEMS_FILENAME_SELECT').' -');

		// Try to figure out a directory
		$directory = null;
		if(!empty($release_id))
		{
			// Get the release
			$release = FOFModel::getTmpInstance('Releases','ArsModel')
				->getItem((int)$release_id);
			
			// Get the category
			$category = FOFModel::getTmpInstance('Categories','ArsModel')
				->getItem((int)$release->category_id);

			// Get which directory to use
			$directory = $category->directory;
			
			$potentialPrefix = substr($directory, 0, 5);
			$potentialPrefix = strtolower($potentialPrefix);
			$useS3 = ($potentialPrefix == 's3://');
			
			if($useS3) {
				$directory = substr($directory, 5);
				$s3 = ArsHelperAmazons3::getInstance();
				$items = $s3->getBucket('', $directory.'/');
				if(empty($items)) {
					$directory = null;
				}
			} else {
				jimport('joomla.filesystem.folder');
				if(!JFolder::exists($directory))
				{
					$directory = JPATH_ROOT.'/'.$directory;
					if(!JFolder::exists($directory)) {
						$directory = null;
					}
				}
			}
		}

		// Get a list of files already used in this category (so as not to show them again, he he!)
		$files = array();
		if(!empty($directory))
		{
			$items = FOFModel::getTmpInstance('Items','ArsModel')
				->category($release->category_id)
				->release('false')
				->getItemList(true);

			if(!empty($items))
			{
				// Walk through the list and find the currently selected filename
				$currentFilename = '';
				foreach($items as $item) {
					if($item->id == $item_id) {
						$currentFilename = $item->filename;
						break;
					}
				}
				
				// Remove already used filenames except the currently selected filename
				reset($items);
				foreach($items as $item) {
					if(($item->filename != $currentFilename) && !empty($item->filename)) {
						$files[] = $item->filename;
					}
				}				
				$files = array_unique($files);
			}
		}

		// Produce a list of files and remove the items in the $files array
		$useFiles = array();
		if(!empty($directory))
		{
			if($useS3) {
				$s3 = ArsHelperAmazons3::getInstance();
				$allFiles = $s3->getBucket('', $directory, null, null, null, true);
				if(!empty($allFiles)) foreach($allFiles as $aFile => $info) {
					$aFile = ltrim(substr($aFile, strlen($directory)), '/');
					if(in_array($aFile, $files)) continue;
					$useFiles[] = $aFile;
				}
			} else {
				$allFiles = JFolder::files($directory, '.', 3, true);
				$root = str_replace('\\', '/', $directory);
				if(!empty($allFiles)) foreach($allFiles as $aFile)
				{
					$aFile = str_replace('\\', '/', $aFile);
					$aFile = ltrim(substr($aFile, strlen($root)), '/');
					if(in_array($aFile, $files)) continue;
					$useFiles[] = $aFile;
				}
			}
		}

		$options = array();
		if(!empty($useFiles)) foreach($useFiles as $file)
		{
			$options[] = JHTML::_('select.option', $file, $file);
		}
			
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function updatetypes($selected = null, $id = 'type', $attribs = array() )
	{
		$types = array('components','libraries','modules','packages','plugins','files','templates');
		$options = array();
		$options[] = JHTML::_('select.option','','- '.JText::_('LBL_UPDATETYPES_SELECT').' -');
		foreach($types as $type)
		{
			$options[] = JHTML::_('select.option',$type,JText::_('LBL_UPDATETYPES_'.strtoupper($type)));
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function updatestreams($selected = null, $id = 'updatestream', $attribs = array())
	{
		$items = FOFModel::getTmpInstance('Updatestreams','ArsModel')
			->getItemList(true);

		$options = array();
		$options[] = JHTML::_('select.option',0,'- '.JText::_('LBL_ITEMS_UPDATESTREAM_SELECT').' -');
		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->id,$item->name.' ('.$item->element.')');
		}
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
	
	/**
	 * Renders the name of an access level group in Joomla! 1.6
	 * @param $access_level_id int The numeric access level
	 */	
	public static function renderaccess($access_level_id)
	{
		static $levelMap = null;

		if(is_null($levelMap)) {
			$db = JFactory::getDBO();
			$query = 'SELECT `id`, `title` FROM `#__viewlevels`';
			$db->setQuery($query);
			$levelMap = $db->loadAssocList('id','title');
		}
		
		if(array_key_exists($access_level_id, $levelMap)) {
			return $levelMap[$access_level_id];
		} else {
			return 'UNKNOWN '.$access_level_id;
		}
	}
	
	public static function languages($selected = null, $id = 'language', $attribs = array() )
	{
		jimport('joomla.language.helper');
		$languages = JLanguageHelper::getLanguages('lang_code');
		$options = array();
		$options[] = JHTML::_('select.option','*',JText::_('JALL_LANGUAGE'));
		if(!empty($languages)) foreach($languages as $key => $lang)
		{
			$options[] = JHTML::_('select.option',$key,$lang->title);
		}
		
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
	
	public static function renderlanguage($langCode)
	{
		static $langs = array();
		
		if(empty($langs)) {
			jimport('joomla.language.helper');
			$languages = JLanguageHelper::getLanguages('lang_code');
			
			$langs['*'] = JText::_('JALL_LANGUAGE');
			if(!empty($languages)) foreach($languages as $key => $lang) {
				$langs[$key] = $lang->title;
			}
		}
		
		if(array_key_exists($langCode, $langs)) {
			return $langs[$langCode];
		} else {
			return $langCode;
		}
	}
	
	public static function vgroups($selected = null, $id = 'vgroup', $attribs = array() )
	{
		$items = FOFModel::getTmpInstance('Vgroups','ArsModel')
			->getItemList(true);

		$options = array();
		$options[] = JHTML::_('select.option',0,'- '.JText::_('LBL_VGROUP_SELECT').' -');
		if(count($items)) foreach($items as $item)
		{
			$options[] = JHTML::_('select.option',$item->id,$item->title);
		}
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
	
	public static function clientid($selected = null, $id = 'clientid', $attribs = array() )
	{
		$options = array();
		$options[] = JHTML::_('select.option','1',JText::_('LBL_CLIENTID_BACKEND'));
		$options[] = JHTML::_('select.option','0',JText::_('LBL_CLIENTID_FRONTEND'));

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
	
	
	public static function environmenticon( $id, $attribs = array() )
	{
		static $items = array();
		
		if (!isset($items[$id])) {
			$items[$id] = clone FOFModel::getTmpInstance('Environments','ArsModel')
				->getItem($id);
		}
		
		$base_folder = rtrim(JURI::base(), '/');
		if(substr($base_folder, -13) == 'administrator') $base_folder = rtrim(substr($base_folder, 0, -13), '/');        

		return JHtml::image( $base_folder.'/media/com_ars/environments/' . $items[$id]->icon, $items[$id]->title, $attribs );
	}
	
	
	public static function environmenticons( $selected = null, $id = 'icon', $attribs = array() )
	{
		jimport('joomla.filesystem.folder');
		$directory	= JPATH_ROOT . '/media/com_ars/environments';
		$options[]	= JHTML::_('select.option','','- '.JText::_( 'LBL_ITEMS_FILENAME_SELECT' ) . ' -');
		
		$files	= JFolder :: files( $directory );
		
		if (! empty( $files ) ) {
			foreach ( $files as $file ) {
				$options[] = JHTML::_('select.option', $file, $file);
			}
		}
		
		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
	
	
	public static function environments( $selected = null, $id = 'environments', $attribs = array() )
	{
		$items = FOFModel::getTmpInstance('Environments','ArsModel')
				->getItemList(true);
		
		$options	= array();
		$options[]	= JHTML::_('select.option','','- '.JText::_( 'LBL_ITEMS_ENVIRONMENT_SELECT' ) . ' -');
		
		if (! empty( $items ) ) {
			foreach ( $items as $item ) {
				$options[] = JHTML::_('select.option',$item->id,$item->title );
			}
		}
		
		$attribs['multiple'] = 'yes';
		return self::genericlist($options, $id.'[]', $attribs, $selected, $id);
	}
	
	public static function getVisualGroupName($vgroup_id)
	{
		$vgroups = null;
		
		if(is_null($vgroups)) {
			$items = FOFModel::getTmpInstance('Vgroups','ArsModel')
				->published(1)
				->getItemList(true);
			
			if(count($items)) foreach($items as $item) {
				$vgroups[$item->id] = $item->title;
			}
		}
		
		if(array_key_exists($vgroup_id, $vgroups)) {
			return $vgroups[$vgroup_id];
		} else {
			return '';
		}
	}
}