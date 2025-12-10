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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel;

$actionlogsHelperPath = JPATH_ADMINISTRATOR . '/components/com_actionlogs/helpers/actionlogs.php';
if (file_exists($actionlogsHelperPath)) {
	require_once $actionlogsHelperPath;
}

/**
 * TJReports Actions Logging Plugin.
 *
 * @since  1.0.3
 */
class PlgActionlogTjreports extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    JApplicationCms
	 * @since  1.0.3
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0.3
	 */
	protected $db;

	/**
	 * Load plugin language file automatically so that it can be used inside component
	 *
	 * @var    boolean
	 * @since  1.0.3
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
	 * @since   1.0.3
	 */
	protected function addLog($messages, $messageLanguageKey, $context, $userId = null)
	{
		if (JVERSION >= '4.0')
		{
			$model = new ActionlogModel;
		}
		else
		{
			$actionlogModelPath = JPATH_ADMINISTRATOR . '/components/com_actionlogs/models/actionlog.php';
			if (file_exists($actionlogModelPath)) {
				require_once $actionlogModelPath;
			}

			/* @var ActionlogsModelActionlog $model */
			$model = BaseDatabaseModel::getInstance('Actionlog', 'ActionlogsModel');
		}

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
	 * @since    1.0.3
	 */
	public function tjReportsOnAfterReportSave($context, $table, $isNew)
	{
		if (!$this->params->get('logActionForReportCreate', 1))
		{
			return;
		}

		$context = Factory::getApplication()->input->get('option');

		$user = Factory::getUser();

		if ($isNew && !empty($table->client))
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_TJREPORTS_REPORT_ADDED_WITH_CLIENT';
			$action             = 'add';
		}
		elseif($isNew && empty($table->client))
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_TJREPORTS_REPORT_ADDED';
			$action             = 'add';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_TJREPORTS_REPORT_UPDATED';
			$action             = 'update';
		}

		if ($table->client)
		{
			$language = Factory::getLanguage();
			$language->load($table->client);
		}

		$message = array(
			'action'      => $action,
			'id'          => $table->id,
			'title'       => $table->title,
			'plugin'      => $table->plugin,
			'client'      => Text::_(strtoupper($table->client)),
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
	 * @since    1.0.3
	 */
	public function tjReportsOnAfterReportDelete($context, $table)
	{
		if (!$this->params->get('logActionForReportDelete', 1))
		{
			return;
		}

		$context            = Factory::getApplication()->input->get('option');
		$user               = Factory::getUser();

		if (!empty($table->client))
		{
			$language = Factory::getLanguage();
			$language->load($table->client);

			$messageLanguageKey = 'PLG_ACTIONLOG_TJREPORTS_REPORT_DELETED_WITH_CLIENT';
		}
		else
		{
			$messageLanguageKey = 'PLG_ACTIONLOG_TJREPORTS_REPORT_DELETED';
		}

		$message = array(
				'action'      => 'delete',
				'id'          => $table->id,
				'title'       => $table->title,
				'plugin'      => $table->plugin,
				'client'      => Text::_(strtoupper($table->client)),
				'userid'      => $user->id,
				'username'    => $user->username,
				'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $user->id,
			);

		$this->addLog(array($message), $messageLanguageKey, $context, $user->id);
	}
}
