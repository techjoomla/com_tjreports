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

JLoader::register('PrivacyPlugin', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/plugin.php');
JLoader::register('PrivacyRemovalStatus', JPATH_ADMINISTRATOR . '/components/com_privacy/helpers/removal/status.php');

use Joomla\CMS\User\User;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * TJReports Privacy Plugin.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgPrivacyTjreports extends PrivacyPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db;

	/**
	 * Reports the privacy related capabilities for this plugin to site administrators.
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
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
	 * @since   __DEPLOY_VERSION__
	 */
	public function onPrivacyExportRequest(PrivacyTableRequest $request, JUser $user = null)
	{
		if (!$user)
		{
			return array();
		}

		/** @var JTableUser $user */
		$userTable = User::getTable();
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
	 * @since   __DEPLOY_VERSION__
	 */
	private function createTJReportsUserReports(JTableUser $user)
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
	 * @since   __DEPLOY_VERSION__
	 */
	public function onPrivacyRemoveData(PrivacyTableRequest $request, JUser $user = null)
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
