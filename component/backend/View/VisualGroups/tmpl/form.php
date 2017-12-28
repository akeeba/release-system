<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright 2010-2017 Akeeba Ltd / Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Select;

/** @var \Akeeba\ReleaseSystem\Admin\View\VisualGroups\Html $this */

defined('_JEXEC') or die;

?>
<section class="akeeba-panel">
    <form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form--horizontal">
        <div class="akeeba-container--50-50">
            <div>
                <div class="akeeba-form-group">
                    <label for="title">
                        <?php echo JText::_('LBL_VGROUPS_TITLE'); ?>
                    </label>

                    <input type="text" name="title" id="title" value="<?php echo $this->escape($this->item->title); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="title">
						<?php echo JText::_('JPUBLISHED'); ?>
                    </label>

                    <?php echo Select::booleanswitch('published', $this->item->published)?>
                </div>

                <div class="akeeba-form-group">
                    <label for="description">
						<?php echo \JText::_('LBL_VGROUPS_DESCRIPTION'); ?>
                    </label>

					<?php echo JEditor::getInstance($this->container->platform->getConfig()->get('editor', 'tinymce'))->display('description', $this->item->description, '97%', '200', '50', '20', true); ?>
                </div>

            </div>
        </div>

        <div class="akeeba-hidden-fields-container">
            <input type="hidden" name="option" value="com_ars" />
            <input type="hidden" name="view" value="VisualGroups" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="id" id="id" value="<?php echo (int)$this->item->id; ?>" />
            <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1" />
        </div>
    </form>
</section>