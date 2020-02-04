<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Reports
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjreportsControllerReports extends JControllerAdmin
{
	/**
	 * Save a query for report engine
	 *
	 * @return true
	 *
	 * @since 1.0.0
	 */
	public function saveQuery()
	{
		try
		{
			$app = JFactory::getApplication();
			$db        		= JFactory::getDBO();
			$input 			= $app->input;
			$post			= $input->post->getArray();
			$current_user 	= JFactory::getUser()->id;

			if (empty($post))
			{
				throw new Exception('Post data cannot be empty.');

				return;
			}

			$queryName 	= $db->escape($input->get('queryName'));
			$alias 		= trim($queryName);

			if ($alias)
			{
				if (JFactory::getConfig()->get('unicodeslugs') == 1)
				{
					$alias = JFilterOutput::stringURLUnicodeSlug($alias);
				}
				else
				{
					$alias = JFilterOutput::stringURLSafe($alias);
				}
			}

			$model 			= $this->getModel('reports');
			$validVars 		= $model->getValidRequestVars();
			$reportData		= $input->post->getArray($validVars);
			$reportParams	= json_encode($reportData);

			$insert_object          = new stdClass;
			$insert_object->id      = '';
			$insert_object->title   = $queryName;
			$insert_object->alias   = $alias;
			$insert_object->plugin  = $db->escape($post['reportToBuild']);
			$insert_object->client  = $db->escape($post['client']);
			$insert_object->parent  = (int) $db->escape($post['reportId']);
			$insert_object->default = 0;
			$insert_object->userid  = $current_user;
			$insert_object->param   = $reportParams;

			if (!$db->insertObject('#__tj_reports', $insert_object, 'id'))
			{
				$app->enqueueMessage($db->stderr());

				echo new JResponseJson(null, 'Could not insert data.', true);
			}
			else
			{
				$id = $db->insertid();
				$insert_object->id = $id;

				$dispatcher = JEventDispatcher::getInstance();
				$extension = JFactory::getApplication()->input->get('option');
				JPluginHelper::importPlugin('tjreports');
				$dispatcher->trigger('tjReportsOnAfterReportSave', array($extension, $insert_object, true));

				$app->enqueueMessage('Data save successfully.');
				echo new JResponseJson('Done');
			}
		}
		catch (Exception $e)
		{
			echo new JResponseJson($e);
		}
	}

	/**
	 * Function used to delete reports
	 *
	 * @return  boolean
	 *
	 * @since  1.0
	 */
	public function deleteQuery()
	{
		try
		{
			$app = JFactory::getApplication();
			$db        		= JFactory::getDBO();
			$input 			= $app->input;
			$queryId = $input->get('queryId', 0, 'INT');

			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_tjreports/models');
			$model = JModelLegacy::getInstance('Report', 'TjreportsModel');

			$result = $model->delete($queryId);

			echo new JResponseJson($result);
		}
		catch (Exception $e)
		{
			echo new JResponseJson($e);
		}
	}
}
