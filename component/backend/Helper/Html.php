<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright 2010-2017 Akeeba Ltd / Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use FOF30\Container\Container;
use FOF30\View\DataView\DataViewInterface;
use FOF30\View\DataView\Raw;
use JHtml;
use JText;

defined('_JEXEC') or die;

class Html
{
	public static function ordering(Raw $view, $orderingField, $orderingValue)
	{
		$ordering = $view->getLists()->order == $orderingField;
		$class = 'input-mini';
		$icon = 'icon-menu';

		// Default inactive ordering
		$html  = '<span class="sortable-handler inactive" >';
		$html .= '<span class="' . $icon . '"></span>';
		$html .= '</span>';

		// The modern drag'n'drop method
		if ($view->getPerms()->editstate)
		{
			$disableClassName = '';
			$disabledLabel = '';

			// DO NOT REMOVE! It will initialize Joomla libraries and javascript functions
			$hasAjaxOrderingSupport = $view->hasAjaxOrderingSupport();

			if (!$hasAjaxOrderingSupport['saveOrder'])
			{
				$disabledLabel = JText::_('JORDERINGDISABLED');
				$disableClassName = 'inactive tip-top';
			}

			$orderClass = $ordering ? 'order-enabled' : 'order-disabled';

			$html  = '<div class="' . $orderClass . '">';
			$html .= 	'<span class="sortable-handler ' . $disableClassName . '" title="' . $disabledLabel . '" rel="tooltip">';
			$html .= 		'<span class="' . $icon . '"></span>';
			$html .= 	'</span>';

			if ($ordering)
			{
				$joomla35IsBroken = version_compare(JVERSION, '3.5.0', 'ge') ? 'style="display: none"': '';

				$html .= '<input type="text" name="order[]" ' . $joomla35IsBroken . ' size="5" class="' . $class . ' text-area-order" value="' . $orderingValue . '" />';
			}

			$html .= '</div>';
		}

		return $html;
	}
}