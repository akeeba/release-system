<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * User entry field, allowing selection of a user from a modal dialog
 * Use it $this->loadAnyTemplate('admin:com_ats/Common/EntryUser', $params)
 *
 * $params is an array defining the following keys (they are expanded into local scope vars automatically):
 *
 * @var string                                 $field       The price field's name, e.g. "user_id"
 * @var \FOF30\Model\DataModel                 $item        The item we're editing
 * @var string                                 $id          The id of the field, default is $field
 * @var bool                                   $readonly    Is this a read only field? Default: false
 * @var string                                 $placeholder Placeholder text, also used as the button's tooltip
 * @var bool                                   $required    Is a value required for this field? Default: false
 * @var string                                 $width       Width of the modal box (default: 800)
 * @var string                                 $height      Height of the modal box (default: 500)
 *
 * Variables made automatically available to us by FOF:
 *
 * @var \FOF30\View\DataView\DataViewInterface $this
 */

$id          = isset($id) ? $id : $field;
$readonly    = isset($readonly) ? ($readonly ? true : false) : false;
$placeholder = isset($placeholder) ? JText::_($placeholder) : JText::_('JLIB_FORM_SELECT_USER');
$userID      = $item->getFieldValue($field, 0);
$user        = $item->getContainer()->platform->getUser($userID);
$width       = isset($width) ? $width : 800;
$height      = isset($height) ? $height : 500;
$class       = isset($class) ? $class : '';
$size        = isset($size) ? $size : 0;
$onchange    = isset($onchange) ? $onchange : '';

$uri = new JUri('index.php?option=com_users&view=users&layout=modal&tmpl=component');
$uri->setVar('required', (isset($required) ? ($required ? 1 : 0) : 0));
$uri->setVar('field', $field);
$url = 'index.php' . $uri->toString(['query']);
?>
@if (version_compare(JVERSION, '3.999999.999999', 'le'))

    @unless($readonly)
        @jhtml('behavior.modal', 'a.userSelectModal_' . $this->escape($field))
        @jhtml('script', 'jui/fielduser.min.js', ['version' => 'auto', 'relative' => true])
    @endunless

    <div class="akeeba-input-group">
        <input readonly type="text"
               id="{{{ $field }}}" value="{{{ $user->username }}}"
               placeholder="{{{ $placeholder }}}" />
        <span class="akeeba-input-group-btn">
		<a href="@route($url)"
           class="akeeba-btn--grey userSelectModal_{{{ $field }}}" title="{{{ $placeholder }}}"
           rel="{handler: 'iframe', size: {x: {{$width}}, y: {{$height}} }}">
			<span class="akion-person"></span>
		</a>
	</span>
    </div>
    @unless($readonly)
        <input type="hidden" id="{{{ $field }}}_id" name="{{{ $field }}}" value="{{ (int) $userID }}" />
    @endunless

@else

    @jlayout('joomla/form/field/user', [
        'name' => $field,
        'id' => $id,
        'class' => $class,
        'size' => $size,
        'value' => $userID,
        'userName' => $user->name,
        'hint' => $placeholder,
        'readonly' => $readonly,
        'required' => $required,
        'onchange' => $onchange,
    ])

    <script type="text/javascript">
        jQuery(document).ready(function () {
            setTimeout(function () {
                var elModal     = document.getElementById("userModal_{{{ $field }}}");
                var elContainer = document.getElementById("akeeba-renderer-fef");
                elContainer.parentElement.appendChild(elModal);
            }, 250);
        })
    </script>
@endif
