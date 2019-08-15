<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Select;
use Joomla\CMS\Language\Text;

/** @var \Akeeba\ReleaseSystem\Admin\View\VisualGroups\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\Releases $item */
$item = $this->getItem();
?>
<section class="akeeba-panel">
    <form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form--horizontal">
		<h3><?php echo Text::_('COM_ARS_RELEASE_BASIC_LABEL') ?></h3>
        <div class="akeeba-container--50-50">
            <div>
                <div class="akeeba-form-group">
					<label for="category_id"><?php echo Text::_('COM_ARS_RELEASES_FIELD_CATEGORY'); ?></label>

                    <?php echo Select::categories($item->category_id, 'category_id')?>
                </div>

                <div class="akeeba-form-group">
					<label for="version"><?php echo Text::_('COM_ARS_RELEASES_FIELD_VERSION'); ?></label>

                    <input type="text" name="version" id="version" value="<?php echo $this->escape($item->version); ?>" />
                </div>

                <div class="akeeba-form-group">
					<label for="alias"><?php echo Text::_('COM_ARS_RELEASES_FIELD_ALIAS'); ?></label>

                    <input type="text" name="alias" id="alias" value="<?php echo $this->escape($item->alias); ?>" />
                </div>

                <div class="akeeba-form-group">
					<label for="maturity"><?php echo Text::_('COM_ARS_RELEASES_FIELD_MATURITY'); ?></label>

                    <?php echo Select::maturity('maturity', $item->maturity)?>
                </div>

                <div class="akeeba-form-group">
					<label for="hits"><?php echo Text::_('JGLOBAL_HITS'); ?></label>

                    <input type="text" name="hits" id="hits" value="<?php echo $this->escape($item->hits); ?>" />
                </div>

                <div class="akeeba-form-group">
					<label for="published"><?php echo Text::_('JPUBLISHED'); ?></label>

					<?php echo JHtml::_('FEFHelper.select.booleanswitch', 'published', $item->published);?>
                </div>
            </div>

            <div>
                <div class="akeeba-form-group">
					<label for="access"><?php echo Text::_('JFIELD_ACCESS_LABEL'); ?></label>

					<?php echo Select::accessLevel('access', $item->access);?>
                </div>

                <div class="akeeba-form-group">
					<label for="show_unauth_links"><?php echo Text::_('COM_ARS_COMMON_SHOW_UNAUTH_LINKS'); ?></label>

					<?php echo JHtml::_('FEFHelper.select.booleanswitch', 'show_unauth_links', $item->show_unauth_links);?>
                </div>

                <div class="akeeba-form-group">
					<label for="redirect_unauth"><?php echo Text::_('COM_ARS_COMMON_REDIRECT_UNAUTH'); ?></label>

                    <input type="text" name="redirect_unauth" id="redirect_unauth" value="<?php echo $this->escape($item->redirect_unauth); ?>" />
                </div>

                <div class="akeeba-form-group">
					<label for="groups"><?php echo Text::_('COM_ARS_COMMON_CATEGORIES_GROUPS_LABEL'); ?></label>

					<?php echo Select::subscriptionGroups('groups[]', $item->groups, array('multiple' => true))?>
                </div>

                <div class="akeeba-form-group">
					<label for="created"><?php echo Text::_('COM_ARS_RELEASES_FIELD_RELEASED'); ?></label>

					<?php echo JHtml::calendar($item->created, 'created', 'created')?>
                </div>

                <div class="akeeba-form-group">
					<label for="language"><?php echo Text::_('JFIELD_LANGUAGE_LABEL'); ?></label>

					<?php echo Select::languages('language', $item->language)?>
                </div>
            </div>
        </div>

        <div class="akeeba-container--50-50">
            <div>
				<?php echo JEditor::getInstance($this->container->platform->getConfig()->get('editor', 'tinymce'))
					->display('description', $item->description, '97%', '200', '50', '20', true); ?>
            </div>
            <div>
				<?php echo JEditor::getInstance($this->container->platform->getConfig()->get('editor', 'tinymce'))
					->display('notes', $item->notes, '97%', '200', '50', '20', true); ?>
            </div>
        </div>

        <div class="akeeba-hidden-fields-container">
            <input type="hidden" name="option" value="com_ars" />
            <input type="hidden" name="view" value="Releases" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="id" id="id" value="<?php echo (int)$item->id; ?>" />
            <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1" />
        </div>
    </form>
</section>