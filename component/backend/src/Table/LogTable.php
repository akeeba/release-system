<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Utilities\IpHelper;

/**
 * ARS download attempts logs
 *
 * @property int    $id          Primary key
 * @property int    $user_id     User ID for this download attempt
 * @property int    $item_id     ID of the download Item
 * @property string $accessed_on Date and time stamp of the download attempt
 * @property string $referer     HTTP Referer
 * @property string $ip          IP address of the user accessing this item
 * @property int    $authorized  Was the download authorized?
 */
class LogTable extends AbstractTable
{
	public function __construct(DatabaseDriver $db, DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__ars_log', 'id', $db, $dispatcher);
	}

	protected function onBeforeCheck()
	{
		$app = Factory::getApplication();
		$db  = $this->getDbo();

		if (empty($this->user_id))
		{
			$user          = $app->getIdentity();
			$this->user_id = $user->id;
		}

		if (empty($this->item_id))
		{
			// Yeah, I know, the Model shouldn't access the input directly but this saves us a lot of code in the
			// front-end models where we're logging downloads.
			$this->item_id = $app->input->getInt('id', 0);
		}

		if (empty($this->accessed_on) || ($this->accessed_on === $db->getNullDate()))
		{
			$this->accessed_on = (clone Factory::getDate())->toSql();
		}

		if (empty($this->referer) && isset($_SERVER['HTTP_REFERER']))
		{
			$this->referer = $app->input->server->getString('HTTP_REFERER', '');
		}

		$this->referer = $this->referer ?? '';

		/**
		 * Fun fact. I had originally written the IP helper code for Admin Tools. Since I needed it in my other
		 * extensions I moved it to FOF 2. Joomla 3 shipped with FOF 2. When they decided they wouldn't ship a newer
		 * FOF version with Joomla 4 they copied the IP helper from FOF 2 into Joomla itself. So now I am using the
		 * core IP helper which is essentially the code I wrote ten years ago myself. Bonus points: it's now someone
		 * else's problem to maintain :D
		 */
		$this->ip = $this->ip ?: IpHelper::getIp();
	}
}