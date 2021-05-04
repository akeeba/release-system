<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var $this \Akeeba\ReleaseSystem\Admin\View\Environments\Html */

defined('_JEXEC') or die;

?>
@extends('any:lib_fof40/Common/browse')

@section('browse-filters')
    @searchfilter('search', 'search', 'COM_ARS_ITEMS_ENVIRONMENTS_TITLE')
@stop

@section('browse-table-header')
    <tr>
        <th width="32">
            @jhtml('FEFHelp.browse.checkall')
        </th>
        <th>
            @sortgrid('title', 'COM_ARS_ITEMS_ENVIRONMENTS_TITLE')
        </th>
    </tr>
@stop

@section('browse-table-body-withrecords')
	<?php $i = 0; ?>
    @foreach($this->items as $row)
        <tr>
            <td><?php echo HTMLHelper::_('grid.id', ++$i, $row->id); ?></td>
            <td>
                <a href="index.php?option=com_ars&view=Environment&task=edit&id={{{ $row->id }}}">
                    {{{ $row->title }}}
                </a>
            </td>
        </tr>
    @endforeach
@stop