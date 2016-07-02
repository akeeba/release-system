<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use Akeeba\ReleaseSystem\Admin\Model\Categories;
use Akeeba\ReleaseSystem\Admin\Model\Environments;
use Akeeba\ReleaseSystem\Admin\Model\Items;
use Akeeba\ReleaseSystem\Admin\Model\Releases;
use FOF30\Container\Container;
use JHtml;

defined('_JEXEC') or die;

abstract class Select
{

	/**
	 * A list of all the ISO 2-country codes and the full country names in English
	 *
	 * @var array
	 */
	private static $countries = array(
		''   => '----',
		'AD' => 'Andorra',
		'AE' => 'United Arab Emirates',
		'AF' => 'Afghanistan',
		'AG' => 'Antigua and Barbuda',
		'AI' => 'Anguilla',
		'AL' => 'Albania',
		'AM' => 'Armenia',
		'AN' => 'Netherlands Antilles',
		'AO' => 'Angola',
		'AQ' => 'Antarctica',
		'AR' => 'Argentina',
		'AS' => 'American Samoa',
		'AT' => 'Austria',
		'AU' => 'Australia',
		'AW' => 'Aruba',
		'AX' => 'Aland Islands',
		'AZ' => 'Azerbaijan',
		'BA' => 'Bosnia and Herzegovina',
		'BB' => 'Barbados',
		'BD' => 'Bangladesh',
		'BE' => 'Belgium',
		'BF' => 'Burkina Faso',
		'BG' => 'Bulgaria',
		'BH' => 'Bahrain',
		'BI' => 'Burundi',
		'BJ' => 'Benin',
		'BL' => 'Saint Barthélemy',
		'BM' => 'Bermuda',
		'BN' => 'Brunei Darussalam',
		'BO' => 'Bolivia, Plurinational State of',
		'BR' => 'Brazil',
		'BS' => 'Bahamas',
		'BT' => 'Bhutan',
		'BV' => 'Bouvet Island',
		'BW' => 'Botswana',
		'BY' => 'Belarus',
		'BZ' => 'Belize',
		'CA' => 'Canada',
		'CC' => 'Cocos (Keeling) Islands',
		'CD' => 'Congo, the Democratic Republic of the',
		'CF' => 'Central African Republic',
		'CG' => 'Congo',
		'CH' => 'Switzerland',
		'CI' => 'Cote d\'Ivoire',
		'CK' => 'Cook Islands',
		'CL' => 'Chile',
		'CM' => 'Cameroon',
		'CN' => 'China',
		'CO' => 'Colombia',
		'CR' => 'Costa Rica',
		'CU' => 'Cuba',
		'CV' => 'Cape Verde',
		'CX' => 'Christmas Island',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DE' => 'Germany',
		'DJ' => 'Djibouti',
		'DK' => 'Denmark',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'DZ' => 'Algeria',
		'EC' => 'Ecuador',
		'EE' => 'Estonia',
		'EG' => 'Egypt',
		'EH' => 'Western Sahara',
		'ER' => 'Eritrea',
		'ES' => 'Spain',
		'ET' => 'Ethiopia',
		'FI' => 'Finland',
		'FJ' => 'Fiji',
		'FK' => 'Falkland Islands (Malvinas)',
		'FM' => 'Micronesia, Federated States of',
		'FO' => 'Faroe Islands',
		'FR' => 'France',
		'GA' => 'Gabon',
		'GB' => 'United Kingdom',
		'GD' => 'Grenada',
		'GE' => 'Georgia',
		'GF' => 'French Guiana',
		'GG' => 'Guernsey',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GL' => 'Greenland',
		'GM' => 'Gambia',
		'GN' => 'Guinea',
		'GP' => 'Guadeloupe',
		'GQ' => 'Equatorial Guinea',
		'GR' => 'Greece',
		'GS' => 'South Georgia and the South Sandwich Islands',
		'GT' => 'Guatemala',
		'GU' => 'Guam',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HK' => 'Hong Kong',
		'HM' => 'Heard Island and McDonald Islands',
		'HN' => 'Honduras',
		'HR' => 'Croatia',
		'HT' => 'Haiti',
		'HU' => 'Hungary',
		'ID' => 'Indonesia',
		'IE' => 'Ireland',
		'IL' => 'Israel',
		'IM' => 'Isle of Man',
		'IN' => 'India',
		'IO' => 'British Indian Ocean Territory',
		'IQ' => 'Iraq',
		'IR' => 'Iran, Islamic Republic of',
		'IS' => 'Iceland',
		'IT' => 'Italy',
		'JE' => 'Jersey',
		'JM' => 'Jamaica',
		'JO' => 'Jordan',
		'JP' => 'Japan',
		'KE' => 'Kenya',
		'KG' => 'Kyrgyzstan',
		'KH' => 'Cambodia',
		'KI' => 'Kiribati',
		'KM' => 'Comoros',
		'KN' => 'Saint Kitts and Nevis',
		'KP' => 'Korea, Democratic People\'s Republic of',
		'KR' => 'Korea, Republic of',
		'KW' => 'Kuwait',
		'KY' => 'Cayman Islands',
		'KZ' => 'Kazakhstan',
		'LA' => 'Lao People\'s Democratic Republic',
		'LB' => 'Lebanon',
		'LC' => 'Saint Lucia',
		'LI' => 'Liechtenstein',
		'LK' => 'Sri Lanka',
		'LR' => 'Liberia',
		'LS' => 'Lesotho',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'LV' => 'Latvia',
		'LY' => 'Libyan Arab Jamahiriya',
		'MA' => 'Morocco',
		'MC' => 'Monaco',
		'MD' => 'Moldova, Republic of',
		'ME' => 'Montenegro',
		'MF' => 'Saint Martin (French part)',
		'MG' => 'Madagascar',
		'MH' => 'Marshall Islands',
		'MK' => 'Macedonia, the former Yugoslav Republic of',
		'ML' => 'Mali',
		'MM' => 'Myanmar',
		'MN' => 'Mongolia',
		'MO' => 'Macao',
		'MP' => 'Northern Mariana Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MS' => 'Montserrat',
		'MT' => 'Malta',
		'MU' => 'Mauritius',
		'MV' => 'Maldives',
		'MW' => 'Malawi',
		'MX' => 'Mexico',
		'MY' => 'Malaysia',
		'MZ' => 'Mozambique',
		'NA' => 'Namibia',
		'NC' => 'New Caledonia',
		'NE' => 'Niger',
		'NF' => 'Norfolk Island',
		'NG' => 'Nigeria',
		'NI' => 'Nicaragua',
		'NL' => 'Netherlands',
		'NO' => 'Norway',
		'NP' => 'Nepal',
		'NR' => 'Nauru',
		'NU' => 'Niue',
		'NZ' => 'New Zealand',
		'OM' => 'Oman',
		'PA' => 'Panama',
		'PE' => 'Peru',
		'PF' => 'French Polynesia',
		'PG' => 'Papua New Guinea',
		'PH' => 'Philippines',
		'PK' => 'Pakistan',
		'PL' => 'Poland',
		'PM' => 'Saint Pierre and Miquelon',
		'PN' => 'Pitcairn',
		'PR' => 'Puerto Rico',
		'PS' => 'Palestinian Territory, Occupied',
		'PT' => 'Portugal',
		'PW' => 'Palau',
		'PY' => 'Paraguay',
		'QA' => 'Qatar',
		'RE' => 'Reunion',
		'RO' => 'Romania',
		'RS' => 'Serbia',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'SA' => 'Saudi Arabia',
		'SB' => 'Solomon Islands',
		'SC' => 'Seychelles',
		'SD' => 'Sudan',
		'SE' => 'Sweden',
		'SG' => 'Singapore',
		'SH' => 'Saint Helena, Ascension and Tristan da Cunha',
		'SI' => 'Slovenia',
		'SJ' => 'Svalbard and Jan Mayen',
		'SK' => 'Slovakia',
		'SL' => 'Sierra Leone',
		'SM' => 'San Marino',
		'SN' => 'Senegal',
		'SO' => 'Somalia',
		'SR' => 'Suriname',
		'ST' => 'Sao Tome and Principe',
		'SV' => 'El Salvador',
		'SY' => 'Syrian Arab Republic',
		'SZ' => 'Swaziland',
		'TC' => 'Turks and Caicos Islands',
		'TD' => 'Chad',
		'TF' => 'French Southern Territories',
		'TG' => 'Togo',
		'TH' => 'Thailand',
		'TJ' => 'Tajikistan',
		'TK' => 'Tokelau',
		'TL' => 'Timor-Leste',
		'TM' => 'Turkmenistan',
		'TN' => 'Tunisia',
		'TO' => 'Tonga',
		'TR' => 'Turkey',
		'TT' => 'Trinidad and Tobago',
		'TV' => 'Tuvalu',
		'TW' => 'Taiwan, Province of China',
		'TZ' => 'Tanzania, United Republic of',
		'UA' => 'Ukraine',
		'UG' => 'Uganda',
		'UM' => 'United States Minor Outlying Islands',
		'US' => 'United States',
		'UY' => 'Uruguay',
		'UZ' => 'Uzbekistan',
		'VA' => 'Holy See (Vatican City State)',
		'VC' => 'Saint Vincent and the Grenadines',
		'VE' => 'Venezuela, Bolivarian Republic of',
		'VG' => 'Virgin Islands, British',
		'VI' => 'Virgin Islands, U.S.',
		'VN' => 'Viet Nam',
		'VU' => 'Vanuatu',
		'WF' => 'Wallis and Futuna',
		'WS' => 'Samoa',
		'YE' => 'Yemen',
		'YT' => 'Mayotte',
		'ZA' => 'South Africa',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe'
	);

	/**
	 * Creates a generic SELECT element
	 *
	 * @param   array  $list     A list of options generated by JHtml::_('select.option'), calls
	 * @param   string $name     The field name
	 * @param   array  $attribs  HTML attributes for the field
	 * @param   mixed  $selected The pre-selected value
	 * @param   string $idTag    The HTML id attribute of the field (do NOT add in $attribs)
	 *
	 * @return  string  The HTML for the SELECT field
	 */
	protected static function genericlist($list, $name, $attribs, $selected, $idTag)
	{
		if (empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';

			foreach ($attribs as $key => $value)
			{
				$temp .= $key . ' = "' . $value . '"';
			}

			$attribs = $temp;
		}

		return JHTML::_('select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	/**
	 * Returns a country selection list
	 *
	 * @param   string $selected Currently selected value
	 * @param   string $id       HTML element's name and id attribute value
	 * @param   array  $attribs  Other HTML element attributes
	 *
	 * @return  string  The HTML for the SELECT field
	 */
	public static function countries($selected = null, $id = 'country', $attribs = array())
	{
		$options = self::countryOptions();

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	/**
	 * Gets the list of countries in JHtml Options format
	 *
	 * @return  array
	 */
	public static function countryOptions()
	{
		$options = array(
			JHTML::_('select.option', '', '---'),
		);

		foreach (self::$countries as $code => $name)
		{
			$options[] = JHTML::_('select.option', $code, $name);
		}

		return $options;
	}

	/**
	 * Renders the environment icon using an internal cache
	 *
	 * @param   int   $id      Environment ID
	 * @param   array $attribs Any HTML attributes for the IMG element
	 *
	 * @return  string  The HTML for the IMG element
	 */
	public static function environmentIcon($id, $attribs = array())
	{
		static $items = null;

		if (is_null($items))
		{
			/** @var Environments $environmentsModel */
			$environmentsModel = Container::getInstance('com_ars')->factory->model('Environments')->tmpInstance();
			// We use getItemsArray instead of get to fetch an associative array
			$items = $environmentsModel->getItemsArray(0, 0, true);
		}

		if (!isset($items[ $id ]))
		{
			return '';
		}

		$base_folder = rtrim(\JUri::base(), '/');

		if (substr($base_folder, - 13) == 'administrator')
		{
			$base_folder = rtrim(substr($base_folder, 0, - 13), '/');
		}

		return \JHtml::image($base_folder . '/media/com_ars/environments/' . $items[ $id ]->icon, $items[ $id ]->title, $attribs);
	}

	public static function releases($selected = null, $id = 'release', $attribs = array(), $category_id = null)
	{
		$container = Container::getInstance('com_ars');

		/** @var Releases $model */
		$model = $container->factory->model('Releases')->tmpInstance();

		if (!empty($category_id))
		{
			$model->setState('category', $category_id);
		}

		if (empty($category_id))
		{
			$model->setState('nobeunpub', 1);
		}

		$items = $model->get(true);

		$options = array();

		if (!$items->count())
		{
			return $options;
		}

		if (empty($category_id))
		{
			$cache = array();

			/** @var Releases $item */
			foreach ($items as $item)
			{
				if (!array_key_exists($item->category->title, $cache))
				{
					$cache[ $item->category->title ] = array();
				}

				$cache[ $item->category->title ][] = (object) array('id' => $item->id, 'version' => $item->version);
			}

			foreach ($cache as $category => $releases)
			{
				if (!empty($options))
				{
					$options[] = JHtml::_('select.option', '</OPTGROUP>');
				}

				$options[] = JHtml::_('select.option', '<OPTGROUP>', $category);

				foreach ($releases as $release)
				{
					$options[] = JHtml::_('select.option', $release->id, $release->version);
				}
			}
		}
		else
		{
			/** @var Releases $item */
			foreach ($items as $item)
			{
				if ($item->category_id == $category_id)
				{
					$options[] = JHtml::_('select.option', $item->id, $item->version);
				}
			}
		}

		array_unshift($options, JHtml::_('select.option', 0, '- ' . \JText::_('COM_ARS_COMMON_SELECT_RELEASE_LABEL') . ' -'));

		return $options;
	}

	public static function categories($selected = null, $id = 'category', $attribs = array())
	{
		$container = Container::getInstance('com_ars');

		/** @var Categories $categoriesModel */
		$categoriesModel = $container->factory->model('Categories')->tmpInstance();

		$items = $categoriesModel->nobeunpub(1)->get(true);

		$options   = array();
		$options[] = JHTML::_('select.option', 0, '- ' . \JText::_('COM_ARS_COMMON_CATEGORY_SELECT_LABEL') . ' -');

		if (count($items))
		{
			foreach ($items as $item)
			{
				$options[] = JHTML::_('select.option', $item->id, $item->title);
			}
		}

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}

	public static function categoriesOptions()
	{
		static $options = null;

		if (!is_array($options))
		{
			$db = \JFactory::getDbo();

			$query = $db->getQuery(true)
			            ->select(['id', 'title'])
			            ->from('#__ars_categories');
			$db->setQuery($query);

			$items = $db->loadAssocList();

			$options   = array();

			if (count($items))
			{
				foreach ($items as $item)
				{
					$options[$item['id']] = [
						'key'   => $item['id'],
						'value' => $item['title']
					];
				}
			}
		}

		return $options;
	}

	public static function categoryTitlesOfReleases()
	{
		return self::releasesOptions('categories');
	}

	public static function releasesOptions($return = 'options', $modelName = 'Items')
	{
		static $options = null;
		static $releaseCategoryMap = [];

		if (!is_array($options))
		{
			$container = Container::getInstance('com_ars');
			/** @var Items $model */
			$model = $container->factory->model('Items');
			$category = $model->getState('category');

			$db = \JFactory::getDbo();

			$query = $db->getQuery(true)
			            ->select(['id', 'category_id', 'version'])
			            ->from('#__ars_releases')
						->order([
							$db->qn('category_id') . ' ASC',
							$db->qn('id') . ' ASC',
						]);

			if ($category)
			{
				$query->where($db->qn('category_id') . ' = ' . $db->q($category));
			}

			$categoriesOptions = self::categoriesOptions();

			$db->setQuery($query);

			$items = $db->loadAssocList();

			$options   = array();
			$lastCategoryId = null;

			if (count($items))
			{
				foreach ($items as $item)
				{
					$releaseCategoryMap[$item['id']] =  $categoriesOptions[$item['category_id']]['value'];

					if (!$category && ($lastCategoryId != $item['category_id']))
					{
						if (!empty($lastCategoryId))
						{
							$options[] = JHTML::_('select.option', '</OPTGROUP>', $categoriesOptions[$lastCategoryId]['value']);
						}

						$lastCategoryId = $item['category_id'];
						$options[] = JHTML::_('select.option', '<OPTGROUP>', $categoriesOptions[$lastCategoryId]['value']);
					}

					$options[] = JHTML::_('select.option', $item['id'], $item['version']);
				}

				if (!$category && !empty($lastCategoryId))
				{
					$options[] = JHTML::_('select.option', '</OPTGROUP>', $categoriesOptions[$lastCategoryId]['value']);
				}
			}
		}

		switch ($return)
		{
			case 'categories':
				return $releaseCategoryMap;

			case 'options':
			default:
				return $options;
				break;
		}
	}

	public static function getFiles($selected = null, $release_id = 0, $item_id = 0, $id = 'type', $attribs = array())
	{
		$container = Container::getInstance('com_ars');

		/** @var Items $model */
		$model = $container->factory->model('Items')->tmpInstance();

		$options = $model->getFilesOptions($release_id, $item_id);

		return self::genericlist($options, $id, $attribs, $selected, $id);
	}
}