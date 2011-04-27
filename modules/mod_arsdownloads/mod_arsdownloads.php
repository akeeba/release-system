<?php

defined('_JEXEC') or die();

JModel::addIncludePath(JPATH_SITE.DS.'components'.DS.'com_ars'.DS.'models');
JModel::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_ars'.DS.'models');
JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_ars'.DS.'tables');

$ars_backend = JPATH_SITE . DS . 'administrator' . DS . 'components' . DS . 'com_ars' . DS . 'models';
$ars_frontend = JPATH_SITE . DS . 'components' . DS . 'com_ars' . DS . 'models';

include_once($ars_backend.DS.'base.php');
include_once($ars_frontend.DS.'base.php');
include_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_ars'.DS.'tables'.DS.'base.php';

if(!class_exists('MydownloadsModel')) {
	class MydownloadsModel
	{
		public function getItems($streams)
		{
			$items = array();
			
			$model = JModel::getInstance('Update','ArsModel');
			$dlmodel = JModel::getInstance('Download', 'ArsModel');
			
			foreach($streams as $stream_id) {
				$model->getItems($stream_id);
				$temp = $model->items;
				if(empty($temp)) continue;
				
				$i = array_shift($temp);
				
				// Is the user authorized to download this item?
				$iFull = $dlmodel->getItem($i->item_id);
				if(is_null($iFull)) continue;
				if(empty($iFull->groups)) continue;
	
				// Add this item
				$newItem = array(
					'id'			=> $i->item_id,
					'release_id'	=> $i->release_id, 
					'name'			=> $i->name,
					'version'		=> $i->version,
					'maturity'		=> $i->maturity
				);
				$items[] = (object)$newItem;
			}
			
			return $items;
		}
	}
}

$streamsArray = array();
$streams = $params->get('streams','');

if(empty($streams)) return;

$temp = explode(',', $streams);
foreach($temp as $item)
{
	$streamsArray[] = (int)$item;
}

$model = new MydownloadsModel;
$items = $model->getItems($streamsArray);

if(!empty($items)):?>
<table class="adminTable">
<?php echo $params->get('pretext',''); ?>
<?php foreach($items as $i): ?>
	<tr>
		<td width="200"><b><?php echo htmlentities($i->name) ?></b> <?php echo $i->version ?></td>
		<td class="button4">
			<a class="readon" href="<?php echo JRoute::_('index.php?option=com_ars&view=download&format=raw&id='.$i->id) ?>">
				<span>Download</span>
			</a>
		</td>
		<td width="100">
			<a class="readon" href="<?php echo JRoute::_('index.php?option=com_ars&view=release&id='.$i->release_id) ?>">
				<span>View all</span>
			</a>
		</td>
	</tr>
<?php endforeach; ?>
</table>
<?php echo $params->get('posttext',''); ?>
<?php endif; ?>