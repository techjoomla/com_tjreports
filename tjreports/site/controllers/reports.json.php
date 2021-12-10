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
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjreportsControllerReports extends AdminController
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
			$app = Factory::getApplication();
			$db        		= Factory::getDBO();
			$input 			= $app->input;
			$post			= $input->post->getArray();
			$current_user 	= Factory::getUser()->id;

			if (empty($post))
			{
				throw new Exception('Post data cannot be empty.');

				return;
			}

			$queryName 	= $db->escape($input->get('queryName'));
			$alias 		= trim($queryName);

			if ($alias)
			{
				if (Factory::getConfig()->get('unicodeslugs') == 1)
				{
					$alias = OutputFilter::stringURLUnicodeSlug($alias);
				}
				else
				{
					$alias = OutputFilter::stringURLSafe($alias);
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

				echo new JsonResponse(null, 'Could not insert data.', true);
			}
			else
			{
				$id = $db->insertid();
				$insert_object->id = $id;

				$extension = Factory::getApplication()->input->get('option');
				PluginHelper::importPlugin('tjreports');
				Factory::getApplication()->triggerEvent('onAfterTjReportsReportSave', array($extension, $insert_object, true));

				$app->enqueueMessage('Data save successfully.');
				echo new JsonResponse('Done');
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
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
			$app = Factory::getApplication();
			$db        		= Factory::getDBO();
			$input 			= $app->input;
			$queryId = $input->get('queryId', 0, 'INT');

			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_tjreports/models');
			$model = BaseDatabaseModel::getInstance('Report', 'TjreportsModel');

			$result = $model->delete($queryId);

			echo new JsonResponse($result);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}
}
