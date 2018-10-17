<?php
/**
 * @package     TJReport
 * @subpackage  PlgActionlogTjreports
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (c) 2009-2018 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later.
 */

// No direct access.
defined('_JEXEC') or die();

JLoader::register('ActionlogsHelper', JPATH_ADMINISTRATOR . '/components/com_actionlogs/helpers/actionlogs.php');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * TJReports Actions Logging Plugin.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgActionlogTjreports extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 * @since  __DEPLOY_VERSION__
	 */
	protected $db;

	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Proxy for ActionlogsModelUserlog addLog method
	 *
	 * This method adds a record to #__action_logs contains (message_language_key, message, date, context, user)
	 *
	 * @param   array   $messages            The contents of the messages to be logged
	 * @param   string  $messageLanguageKey  The language key of the message
	 * @param   string  $context             The context of the content passed to the plugin
	 * @param   int     $userId              ID of user perform the action, usually ID of current logged in user
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function addLog($messages, $messageLanguageKey, $context, $userId = null)
	{
		JLoader::register('ActionlogsModelActionlog', JPATH_ADMINISTRATOR . '/components/com_actionlogs/models/actionlog.php');

		/* @var ActionlogsModelActionlog $model */
		$model = BaseDatabaseModel::getInstance('Actionlog', 'ActionlogsModel');
		$model->addLog($messages, $messageLanguageKey, $context, $userId);
	}

	/**
	 * On saving report data logging method
	 *
	 * Method is called after user data is stored in the database.
	 * This method logs who created/edited any user's data
	 *
	 * @param   String   $context  com_tjreports
	 * @param   Object   $table    Holds the new report data.
	 * @param   Boolean  $isNew    True if a new report is stored.
	 *
	 * @return  void
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function tjReportsOnAfterReportSave($context, $table, $isNew)
	{
		if (!$this->params->get('logActionForReportCreate', 1))
		{
			return;
		}

		$context = JFactory::getApplication()->input->get('option');

		$user = JFactory::getUser();

		if ($isNew && !empty($table->client))
		{
			$messageLanguageKey = 'PLG_ACTIONLOGS_JTREPORTS_REPORT_ADDED_WITH_CLIENT';
			$action             = 'add';
		}
		elseif($isNew && empty($table->client))
		{
			$messageLanguageKey = 'PLG_ACTIONLOGS_JTREPORTS_REPORT_ADDED';
			$action             = 'add';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOGS_JTREPORTS_REPORT_UPDATED';
			$action             = 'update';
		}

		if ($table->client)
		{
			$language = JFactory::getLanguage();
			$language->load($table->client);
		}

		$message = array(
			'action'      => $action,
			'id'          => $table->id,
			'title'       => $table->title,
			'plugin'      => $table->plugin,
			'client'      => JText::_(strtoupper($table->client)),
			'itemlink'    => 'index.php?option=com_tjreports&task=tjreport.edit&id=' . $table->id,
			'userid'      => $user->id,
			'username'    => $user->username,
			'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
		);

		$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
	}

	/**
	 * On saving report data logging method
	 *
	 * Method is called after user data is stored in the database.
	 * This method logs who created/edited any user's data
	 *
	 * @param   String  $context  com_tjreports
	 * @param   Object  $table    Holds the new report data.
	 *
	 * @return  void
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	public function tjReportsOnAfterReportDelete($context, $table)
	{
		if (!$this->params->get('logActionForReportDelete', 1))
		{
			return;
		}

		$context            = JFactory::getApplication()->input->get('option');
		$user               = JFactory::getUser();

		if (!empty($table->client))
		{
			$language = JFactory::getLanguage();
			$language->load($table->client);

			$messageLanguageKey = 'PLG_ACTIONLOGS_JTREPORTS_REPORT_DELETED_WITH_CLIENT';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOGS_JTREPORTS_REPORT_DELETED';
		}

		$message = array(
				'action'      => 'delete',
				'id'          => $table->id,
				'title'       => $table->title,
				'plugin'      => $table->plugin,
				'client'      => JText::_(strtoupper($table->client)),
				'userid'      => $user->id,
				'username'    => $user->username,
				'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
	}
}
