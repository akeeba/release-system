<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;

/** @var \Akeeba\ReleaseSystem\Admin\View\Categories\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\Categories $item */
$item = $this->getItem();
?>
<section class="akeeba-panel">
    <form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form--horizontal">
        <div class="akeeba-container--50-50">
            <div>
                <h3><?php echo JText::_('COM_ARS_RELEASE_BASIC_LABEL')?></h3>
                <div class="akeeba-form-group">
                    <label for="title"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_TITLE'); ?></label>

                    <input type="text" name="title" id="title" value="<?php echo $this->escape($item->title); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="alias"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_ALIAS'); ?></label>

                    <input type="text" name="alias" id="alias" value="<?php echo $this->escape($item->alias); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="vgroup_id"><?php echo JText::_('LBL_CATEGORIES_VGROUP'); ?></label>

                    <?php echo Select::vgroups('vgroup_id', $item->vgroup_id)?>
                </div>

                <div class="akeeba-form-group">
                    <label for="type"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_TYPE'); ?></label>

					<?php echo Select::categoryType('type', $item->type)?>
                </div>

                <div class="akeeba-form-group">
                    <label for="directory"><?php echo JText::_('COM_ARS_CATEGORIES_FIELD_DIRECTORY'); ?></label>

                    <input type="text" name="directory" id="directory" value="<?php echo $this->escape($item->directory); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="published"><?php echo JText::_('JPUBLISHED'); ?></label>

					<?php echo JHtml::_('FEFHelper.select.booleanswitch', 'published', $item->published);?>
                </div>

                <div class="akeeba-form-group">
                    <label for="access"><?php echo JText::_('JFIELD_ACCESS_LABEL'); ?></label>

					<?php echo Select::accessLevel('access', $item->access);?>
                </div>

                <div class="akeeba-form-group">
                    <label for="show_unauth_links"><?php echo JText::_('COM_ARS_COMMON_SHOW_UNAUTH_LINKS'); ?></label>

					<?php echo JHtml::_('FEFHelper.select.booleanswitch', 'show_unauth_links', $item->show_unauth_links);?>
                </div>

                <div class="akeeba-form-group">
                    <label for="groups"><?php echo JText::_('COM_ARS_COMMON_CATEGORIES_GROUPS_LABEL'); ?></label>

					<?php echo Select::subscriptionGroups('groups[]', $item->groups, array('multiple' => true))?>
                </div>

                <div class="akeeba-form-group">
                    <label for="redirect_unauth"><?php echo JText::_('COM_ARS_COMMON_REDIRECT_UNAUTH'); ?></label>

                    <input type="text" name="redirect_unauth" id="redirect_unauth" value="<?php echo $this->escape($item->redirect_unauth); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="is_supported"><?php echo JText::_('COM_ARS_IS_SUPPORTED'); ?></label>

					<?php echo JHtml::_('FEFHelper.select.booleanswitch', 'is_supported', $item->is_supported);?>
                </div>

                <div class="akeeba-form-group">
                    <label for="language"><?php echo JText::_('JFIELD_LANGUAGE_LABEL'); ?></label>

					<?php echo Select::languages('language', $item->language)?>
                </div>
            </div>

            <div>
				<?php echo JEditor::getInstance($this->container->platform->getConfig()->get('editor', 'tinymce'))
                            ->display('description', $item->description, '97%', '200', '50', '20', true); ?>
            </div>
        </div>

        <div>
            <?php echo Html::rules($item->asset_id, 'rules', 'Categories', 'com_ars', 'category')?>
        </div>

        <div class="akeeba-hidden-fields-container">
            <input type="hidden" name="option" value="com_ars" />
            <input type="hidden" name="view" value="Categories" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="id" id="id" value="<?php echo (int)$item->id; ?>" />
            <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1" />
        </div>
    </form>
</section>