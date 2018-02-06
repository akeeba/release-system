<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Html;
use Akeeba\ReleaseSystem\Admin\Helper\Select;

/** @var \Akeeba\ReleaseSystem\Admin\View\AutoDescriptions\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\AutoDescriptions $item */
$item = $this->getItem();
?>
<section class="akeeba-panel">
    <form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form--horizontal">
        <div class="akeeba-container--50-50">
            <div>
                <h3><?php echo JText::_('COM_ARS_RELEASE_BASIC_LABEL')?></h3>

                <div class="akeeba-form-group">
                    <label for="category"><?php echo JText::_('LBL_AUTODESC_CATEGORY'); ?></label>

					<?php echo Select::categories($item->category, 'category')?>
                </div>

                <div class="akeeba-form-group">
                    <label for="packname"><?php echo JText::_('LBL_AUTODESC_PACKNAME'); ?></label>

                    <input type="text" name="packname" id="packname" value="<?php echo $this->escape($item->packname); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="title"><?php echo JText::_('LBL_AUTODESC_TITLE'); ?></label>

                    <input type="text" name="title" id="title" value="<?php echo $this->escape($item->title); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="environments"><?php echo JText::_('LBL_ITEMS_ENVIRONMENTS'); ?></label>

                    <?php echo Select::environments('environments', $item->environments)?>
                </div>

                <div class="akeeba-form-group">
                    <label for="published"><?php echo JText::_('JPUBLISHED'); ?></label>

					<?php echo JHtml::_('FEFHelper.select.booleanswitch', 'published', $item->published);?>
                </div>
            </div>

            <div>
				<?php echo JEditor::getInstance($this->container->platform->getConfig()->get('editor', 'tinymce'))
                            ->display('description', $item->description, '97%', '200', '50', '20', true); ?>
            </div>
        </div>

        <div class="akeeba-hidden-fields-container">
            <input type="hidden" name="option" value="com_ars" />
            <input type="hidden" name="view" value="AutoDescriptions" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="id" id="id" value="<?php echo $item->id; ?>" />
            <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1" />
        </div>
    </form>
</section>