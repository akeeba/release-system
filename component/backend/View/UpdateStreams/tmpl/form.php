<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Akeeba\ReleaseSystem\Admin\Helper\Select;

/** @var \Akeeba\ReleaseSystem\Admin\View\UpdateStreams\Html $this */

defined('_JEXEC') or die;

/** @var \Akeeba\ReleaseSystem\Admin\Model\UpdateStreams $item */
$item = $this->getItem();

?>
<section class="akeeba-panel">
    <form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form--horizontal">
        <h3><?php echo JText::_('LBL_ARS_UPDATESTREAMS_BASIC')?></h3>
        <div class="akeeba-container--50-50">
            <div>
                <div class="akeeba-form-group">
                    <label for="name">
                        <?php echo JText::_('LBL_UPDATES_NAME'); ?>
                    </label>

                    <input type="text" name="name" id="name" value="<?php echo $this->escape($item->name); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="alias">
						<?php echo JText::_('JFIELD_ALIAS_LABEL'); ?>
                    </label>

                    <input type="text" name="alias" id="alias" value="<?php echo $this->escape($item->alias); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="type">
						<?php echo JText::_('LBL_UPDATES_TYPE'); ?>
                    </label>

                    <?php echo Select::updateTypes('type', $item->type) ?>
                </div>

                <div class="akeeba-form-group">
                    <label for="category">
						<?php echo JText::_('COM_ARS_RELEASES_FIELD_CATEGORY'); ?>
                    </label>

					<?php echo Select::categories($item->category, 'category') ?>
                </div>

                <div class="akeeba-form-group">
                    <label for="packname">
						<?php echo JText::_('LBL_UPDATES_PACKNAME'); ?>
                    </label>

                    <input type="text" name="packname" id="packname" value="<?php echo $this->escape($item->packname); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="element">
						<?php echo JText::_('LBL_UPDATES_ELEMENT'); ?>
                    </label>

                    <input type="text" name="element" id="element" value="<?php echo $this->escape($item->element); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="client_id">
						<?php echo JText::_('LBL_RELEASES_CLIENT_ID'); ?>
                    </label>

					<?php echo Select::client_id('client_id', $item->client_id) ?>
                </div>

                <div class="akeeba-form-group">
                    <label for="folder">
						<?php echo JText::_('LBL_UPDATES_FOLDER'); ?>
                    </label>

                    <input type="text" name="folder" id="folder" value="<?php echo $this->escape($item->folder); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="jedid">
						<?php echo JText::_('LBL_UPDATES_JEDID'); ?>
                    </label>

                    <input type="text" name="jedid" id="jedid" value="<?php echo $this->escape($item->jedid); ?>" />
                </div>

                <div class="akeeba-form-group">
                    <label for="published">
						<?php echo JText::_('JPUBLISHED'); ?>
                    </label>

					<?php echo JHtml::_('FEFHelper.select.booleanswitch', 'published', $item->published);?>
                </div>
            </div>
        </div>

        <div class="akeeba-hidden-fields-container">
            <input type="hidden" name="option" value="com_ars" />
            <input type="hidden" name="view" value="UpdateStreams" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="id" id="id" value="<?php echo (int)$item->id; ?>" />
            <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1" />
        </div>
    </form>
</section>