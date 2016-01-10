<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Update\Update;
use JComponentHelper;
use JFile;
use JFactory;
use JLoader;
use JTable;
use JUpdate;
use JFolder;
use JInstallerHelper;
use JInstaller;

/**
 * Handles component updates
 *
 * This class will check for available extension updates, send update notification emails and install the available
 * updates.
 */
class Updates extends Update
{
	/**
	 * Public constructor. Initialises the protected members as well.
	 *
	 * @param array $config
	 */
	public function __construct($config = array())
	{
		$config['update_component'] = 'pkg_ars';
		$config['update_sitename']  = 'Akeeba Release System';
		$config['update_site']      = 'http://cdn.akeebabackup.com/updates/ars.xml';

		parent::__construct($config);
	}

	/**
	 * Handle automatic updates. Sends update notification emails and/or installs a new version automatically.
	 *
	 * @return  array
	 */
	public function autoupdate()
	{
		$return = array(
			'message' => ''
		);

		// First of all let's check if there are any updates
		$updateInfo = (object) $this->getUpdates(true);

		// There are no updates, there's no point in continuing
		if (!$updateInfo->hasUpdate)
		{
			return array(
				'message' => array("No available updates found")
			);
		}

		$return['message'][] = "Update detected, version: " . $updateInfo->version;

		// Ok, an update is found, what should I do?
		$params     = JComponentHelper::getParams('com_ars');
		$autoupdate = $params->get('autoupdateCli', 1);

		// Let's notifiy the user
		if ($autoupdate == 1 || $autoupdate == 2)
		{
			$email = $params->get('notificationEmail');

			if (!$email)
			{
				$return['message'][] = "There isn't an email for notifications, no notification will be sent.";
			}
			else
			{
				// Ok, I can send it out, but before let's check if the user set any frequency limit
				$numfreq    = $params->get('notificationFreq', 1);
				$freqtime   = $params->get('notificationTime', 'day');
				$lastSend   = $this->getLastSend();
				$shouldSend = false;

				if (!$numfreq)
				{
					$shouldSend = true;
				}
				else
				{
					$check = strtotime('-' . $numfreq . ' ' . $freqtime);

					if ($lastSend < $check)
					{
						$shouldSend = true;
					}
					else
					{
						$return['message'][] = "Frequency limit hit, I won't send any email";
					}
				}

				if ($shouldSend)
				{
					if ($this->sendNotificationEmail($updateInfo->version, $email))
					{
						$return['message'][] = "E-mail(s) correctly sent";
					}
					else
					{
						$return['message'][] = "An error occurred while sending e-mail(s). Please double check your settings";
					}

					$this->setLastSend();
				}
			}
		}

		// Let's download and install the latest version
		if ($autoupdate == 1 || $autoupdate == 3)
		{
			$return['message'][] = $this->updateComponent();
		}

		return $return;
	}

	/**
	 * Sends an update notification email
	 *
	 * @param   string $version The newest available version
	 * @param   string $email   The email address of the recipient
	 *
	 * @return  boolean  The result from JMailer::send()
	 */
	private function sendNotificationEmail($version, $email)
	{
		$email_subject = <<<ENDSUBJECT
THIS EMAIL IS SENT FROM YOUR SITE "[SITENAME]" - Update available
ENDSUBJECT;

		$email_body = <<<ENDBODY
This email IS NOT sent by the authors of Akeeba Release System. It is sent automatically
by your own site, [SITENAME]

================================================================================
UPDATE INFORMATION
================================================================================

Your site has determined that there is an updated version of Akeeba Release System
available for download.

New version number: [VERSION]

This email is sent to you by your site to remind you of this fact. The authors
of the software will never contact you about available updates.

================================================================================
WHY AM I RECEIVING THIS EMAIL?
================================================================================

This email has been automatically sent by a CLI script you, or the person who built
or manages your site, has installed and explicitly activated. This script looks
for updated versions of the software and sends an email notification to all
Super Users. You will receive several similar emails from your site, up to 6
times per day, until you either update the software or disable these emails.

To disable these emails, please contact your site administrator.

If you do not understand what this means, please do not contact the authors of
the software. They are NOT sending you this email and they cannot help you.
Instead, please contact the person who built or manages your site.

================================================================================
WHO SENT ME THIS EMAIL?
================================================================================

This email is sent to you by your own site, [SITENAME]

ENDBODY;

		$jconfig  = JFactory::getConfig();
		$sitename = $jconfig->get('sitename');

		$substitutions = array(
			'[VERSION]'  => $version,
			'[SITENAME]' => $sitename
		);

		$email_subject = str_replace(array_keys($substitutions), array_values($substitutions), $email_subject);
		$email_body    = str_replace(array_keys($substitutions), array_values($substitutions), $email_body);

		$mailer = JFactory::getMailer();

		$mailfrom = $jconfig->get('mailfrom');
		$fromname = $jconfig->get('fromname');

		$mailer->setSender(array($mailfrom, $fromname));
		$mailer->addRecipient($email);
		$mailer->setSubject($email_subject);
		$mailer->setBody($email_body);

		return $mailer->Send();
	}

	/**
	 * Automatically download and install the updated version
	 *
	 * @return  string  The message to show in the CLI output
	 */
	private function updateComponent()
	{
		JLoader::import('joomla.updater.update');

		$db = $this->container->db;

		$upgradeSiteIDs = $this->getUpdateSiteIds();
		$update_site    = array_shift($upgradeSiteIDs);

		$query = $db->getQuery(true)
		            ->select($db->qn('update_id'))
		            ->from($db->qn('#__updates'))
		            ->where($db->qn('update_site_id') . ' = ' . $update_site);

		$uid = $db->setQuery($query)->loadResult();

		$update   = new JUpdate();
		$instance = JTable::getInstance('update');
		$instance->load($uid);
		$update->loadFromXML($instance->detailsurl);

		if (isset($update->get('downloadurl')->_data))
		{
			$url = trim($update->downloadurl->_data);
		}
		else
		{
			return "No download URL found inside XML manifest";
		}

		$config   = JFactory::getConfig();
		$tmp_dest = $config->get('tmp_path');

		if (!$tmp_dest)
		{
			return "Joomla temp directory is empty, please set it before continuing";
		}
		elseif (!JFolder::exists($tmp_dest))
		{
			return "Joomla temp directory does not exists, please set the correct path before continuing";
		}

		$p_file = JInstallerHelper::downloadPackage($url);

		if (!$p_file)
		{
			return "An error occurred while trying to download the latest version";
		}

		// Unpack the downloaded package file
		$package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file);

		if (!$package)
		{
			return "An error occurred while unpacking the file, please double check your Joomla temp directory";
		}

		$installer = new JInstaller;
		$installed = $installer->install($package['extractdir']);

		// Let's cleanup the downloaded archive and the temp folder
		if (JFolder::exists($package['extractdir']))
		{
			JFolder::delete($package['extractdir']);
		}

		if (JFile::exists($package['packagefile']))
		{
			JFile::delete($package['packagefile']);
		}

		if ($installed)
		{
			return "Component successfully updated";
		}
		else
		{
			return "An error occurred while trying to update the component";
		}
	}

	/**
	 * Get the UNIX timestamp of the the last time we sent out an update notification email
	 *
	 * @return  integer
	 */
	private function getLastSend()
	{
		$params = JComponentHelper::getParams('com_ars');

		return $params->get('akeebasubs_autoupdate_lastsend', 0);
	}

	/**
	 * Set the UNIX timestamp of the last time we sent out an update notificatin email to be right now
	 *
	 * @return  void
	 */
	private function setLastSend()
	{
		$db     = $this->container->db;
		$params = JComponentHelper::getParams('com_ars');

		$params->set('akeebasubs_autoupdate_lastsend', time());
		$data = $params->toString();

		$query = $db->getQuery(true)
		            ->update($db->qn('#__extensions'))
		            ->set($db->qn('params') . ' = ' . $db->q($data))
		            ->where($db->qn('extension_id') . ' = ' . $db->q($this->extension_id));
		$db->setQuery($query)->execute();
	}
}