<?php
/**
 * @package     TJReports
 * @subpackage  PlgPrivacyTjreports
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\Table\User as UserTable;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

/**
 * TJReports Privacy Plugin.
 *
 * @since  1.0.3
 */
class PlgPrivacyTjreports extends PrivacyPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.0.3
	 */
	protected $autoloadLanguage = true;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0.3
	 */
	protected $db;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   1.0.3
	 */
	public function onPrivacyCollectAdminCapabilities()
	{
		$this->loadLanguage();

		return array(
			Text::_('PLG_PRIVACY_TJREPORTS') => array(
				Text::_('PLG_PRIVACY_TJREPORTS_PRIVACY_CAPABILITY_USER_REPORTS_DETAIL')
			)
		);
	}

	/**
	 * Processes an export request for TJReports user data
	 *
	 * This event will collect data for the following tables:
	 *
	 * - #__tj_reports
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 *
	 * @since   1.0.3
	 */
	public function onPrivacyExportRequest(PrivacyTableRequest $request, User $user = null)
	{
		if (!$user)
		{
			return array();
		}

		/** @var JTableUser $user */
		$userTable = UserTable::getTable();
		$userTable->load($user->id);

		$domains = array();
		$domains[] = $this->createTJReportsUserReports($userTable);

		return $domains;
	}

	/**
	 * Create the domain for the TJReports user reports
	 *
	 * @param   JTableUser  $user  The JTableUser object to process
	 *
	 * @return  PrivacyExportDomain
	 *
	 * @since   1.0.3
	 */
	private function createTJReportsUserReports(User $user)
	{
		$domain = $this->createDomain('User Reports', 'Reports of user in TJReports');

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName(array('id', 'title', 'plugin', 'userid', 'client', 'default')))
			->from($this->db->quoteName('#__tj_reports'))
			->where($this->db->quoteName('userid') . '=' . $user->id);

		$roles = $this->db->setQuery($query)->loadAssocList();

		if (!empty($roles))
		{
			foreach ($roles as $role)
			{
				$domain->addItem($this->createItemFromArray($role, $role['id']));
			}
		}

		return $domain;
	}

	/**
	 * Removes the data associated with a remove information request
	 *
	 * This event will pseudoanonymise the user account
	 *
	 * @param   PrivacyTableRequest  $request  The request record being processed
	 * @param   JUser                $user     The user account associated with this request if available
	 *
	 * @return  void
	 *
	 * @since   1.0.3
	 */
	public function onPrivacyRemoveData(PrivacyTableRequest $request, User $user = null)
	{
		// This plugin only processes data for registered user accounts
		if (!$user)
		{
			return;
		}

		// If there was an error loading the user do nothing here
		if ($user->guest)
		{
			return;
		}

		$db = $this->db;

		// 1. Delete data from #__tj_reports
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__tj_reports'))
			->where($db->quoteName('userid') . '=' . $user->id)
			->where($db->quoteName('default') . '=' . 0);

		$db->setQuery($query);
		$db->execute();
	}
}
