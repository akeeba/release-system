<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

use Joomla\CMS\Language\Text;

/** @var \Akeeba\ReleaseSystem\Admin\View\Environments\Html $this */

defined('_JEXEC') or die;

?>
<section class="akeeba-panel">
    <form action="index.php" method="post" name="adminForm" id="adminForm" class="akeeba-form--horizontal">
        <div class="akeeba-container--50-50">
            <div>
                <div class="akeeba-form-group">
					<label for="title"><?php echo Text::_('LBL_ENVIRONMENTS_TITLE'); ?></label>

                    <input type="text" name="title" id="title" value="<?php echo $this->escape($this->item->title); ?>" />
                </div>

                <div class="akeeba-form-group">
					<label for="xmltitle"><?php echo Text::_('LBL_ENVIRONMENT_XMLTITLE'); ?></label>

                    <input type="text" name="xmltitle" id="xmltitle" value="<?php echo $this->escape($this->item->xmltitle); ?>" />
					<span><?php echo Text::_('LBL_ENVIRONMENT_XMLTITLE_TIP') ?></span>
                </div>

            </div>
        </div>

        <div class="akeeba-hidden-fields-container">
            <input type="hidden" name="option" value="com_ars" />
            <input type="hidden" name="view" value="Environments" />
            <input type="hidden" name="task" value="" />
            <input type="hidden" name="id" id="id" value="<?php echo (int)$this->item->id; ?>" />
            <input type="hidden" name="<?php echo $this->container->platform->getToken(true); ?>" value="1" />
        </div>
    </form>
</section>