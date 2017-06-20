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
	 * Function used to get filtered data
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function getFilterData()
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
		$input = JFactory::getApplication()->input;
		$post  = $input->post;

		$filterData  = $post->get('filterValue', '', 'ARRAY');
		$filterTitle = $post->get('filterName', '', 'ARRAY');

		$limit     = $post->get('limit', '20', 'INT');
		$page      = $post->get('page', '0', 'INT');
		$sortCol   = $post->get('sortCol', '', 'STRING');
		$sortOrder = $post->get('sortOrder', '', 'STRING');
		$colNames  = $post->get('colToShow', '', 'ARRAY');
		$action    = $post->get('action', '', 'STRING');
		$allow_permission    = $post->get('allow_permission', '', 'INT');

		$limit_start = 0;

		if ($page > 0)
		{
			$limit_start = $limit * ($page - 1);
		}

		$filters = array();
		$count   = count($filterData);
		$i       = 0;

		for ($i = 0; $i <= $count - 1; $i++)
		{
			if (isset($filterTitle[$i]) && isset($filterData[$i]))
			{
				$filters[$filterTitle[$i]] = $filterData[$i];
			}
		}

		$model = $this->getModel('reports');
		$data  = $model->getData($filters, $colNames, $limit, $limit_start, $sortCol, $sortOrder, $action);

		echo json_encode($data, true);
		jexit();
	}

	/**
	 * Function used to export data in csv format
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function csvexport()
	{
		$mainframe  = JFactory::getApplication();
		$user = JFactory::getUser();

		if (!$user->authorise('core.export', 'com_tjreports'))
		{
			if ($user->guest)
			{
				$return = base64_encode(JUri::getInstance());
				$login_url_with_return = JRoute::_('index.php?option=com_users&view=login&return=' . $return);
				$mainframe->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'notice');
				$mainframe->redirect($login_url_with_return, 403);
			}
			else
			{
				$mainframe->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
				$mainframe->setHeader('status', 403, true);

				return;
			}
		}

		$input = JFactory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');
		$reportId = $input->get('reportId', '', 'INT');

		if (empty($reportName))
		{
			$reportName = $mainframe->getUserState('com_tjreports' . '.reportName', '');
		}

		if (empty($reportId))
		{
			$reportId = $mainframe->getUserState('com_tjreports' . '.reportId', '');
		}

		$colNames      = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_colNames', '');
		$filters       = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_filters', '');
		$sortCol       = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_sortCol', 'asc');
		$sortOrder     = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_sortOrder', 'asc');
		$rows_to_fetch = 'all';
		$limit_start   = 0;
		$action        = 'csv';

		$created_by = 0;
		$user = JFactory::getUser();

		$allow_permission = $input->get('allow_permission', '', 'INT');

		if (!empty($reportId))
		{
			if (!$allow_permission)
			{
				$created_by = $user->id;
			}
		}

		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('tjreports');
		$data = $dispatcher->trigger('plg' . $reportName . 'GetData', array(
																				$filters,
																				$colNames,
																				$rows_to_fetch,
																				$limit_start,
																				$sortCol,
																				$sortOrder,
																				$created_by
																			)
									);

		$data = $data[0];

		$csvData     = null;
		$csvData_arr = array();

		foreach ($data['colToshow'] as $eachColumn)
		{
			$calHeading    = strtoupper($eachColumn);
			$plgReport     = strtoupper($reportName);

			// Commented because taking the dynamically selected col to be exported above name above - Amit Udale
			// 	$calHeading    = 'PLG_TJREPORTS_' . $plgReport . '_' . $calHeading;
			$csvData_arr[] = '"' . $calHeading . '"';
		}

		$csvData .= implode(',', $csvData_arr);
		$csvData .= "\n";
		echo $csvData;

		$csvData  = '';
		$filename = "tjreports_" . $reportName . "_report_" . date("Y-m-d_H-i", time());

		// Set CSV headers
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		foreach ($data['items'] as $key => $value)
		{
			$csvData      = '';
			$csvData_arr1 = array();

			foreach ($value as $index => $finalValue)
			{
				if (in_array($index, $data['colToshow']))
				{
					// Remove double Quotes from the data
					$finalValue       = str_replace('"', '', $finalValue);

					// Remove single Quotes from the data
					$finalValue       = str_replace("'", '', $finalValue);

					// Remove tabs and newlines from the data
					$finalValue2      = preg_replace('/(\r\n|\r|\n)+/', " ", $finalValue);

					// Remove extra spaces from the data
					$final_text_value = preg_replace('/\s+/', " ", $finalValue2);

					// Add data in the Quotes and asign it in the csv array
					$csvData_arr1[] = '"' . $final_text_value . '"';
				}
			}

			// TRIGGER After csv body add extra fields
			$csvData = implode(',', $csvData_arr1);
			echo $csvData . "\n";
		}

		jexit();
	}

	/**
	 * Save a query for report engine
	 *
	 * @return true
	 *
	 * @since 1.0.0
	 */
	public function saveQuery()
	{
		$db        = JFactory::getDBO();
		$mainframe  = JFactory::getApplication();
		$input = JFactory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');
		$current_user = $input->get('current_user', '', 'INT');

		// Take only first element as url might have more than on client

		$clients_str = $input->get('client', '', 'STRING');
		$clients = explode(",", $clients_str);

		$client = $clients[0];

		$reportId = $input->get('reportId', '0', 'INT');

		if (empty($reportName))
		{
			$reportName = $mainframe->getUserState('com_tjreports' . '.reportName', '');
		}

		$colNames      = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_colNames', '');
		$filters       = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_filters', '');
		$sortCol       = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_sortCol', '');
		$sortOrder     = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_sortOrder', '');
		$rows_to_fetch = 'all';
		$limit_start   = 0;

		$post = $input->post;

		$sort = array();

		if (!empty($sortCol) && !empty($sortOrder))
		{
			$sort[] = $sortCol;
			$sort[] = $sortOrder;
		}

		$currentTime = new JDate('now');
		$queryName = $post->get('queryName', '', 'STRING');

		$res = array();
		$res['colToshow']          = $colNames;
		$res['sort']          = $sort;
		$res['filters']          = $filters;
		$res['privacy']          = '';
		$res['created_on']          = $currentTime;
		$res['last_accessed_on']          = $currentTime;
		$res['hash']          = '';

		$params = json_encode($res);

		$alias = $queryName;
		$alias = trim($alias);

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

		$insert_object                = new stdClass;
		$insert_object->id            = '';
		$insert_object->title          = $queryName;
		$insert_object->alias         = $alias;
		$insert_object->plugin        = $reportName;
		$insert_object->client        = $client;
		$insert_object->parent        = $reportId;
		$insert_object->default       = 0;
		$insert_object->userid        = $current_user;
		$insert_object->param         = $params;

		if (!$db->insertObject('#__tj_reports', $insert_object, 'id'))
		{
			echo $db->stderr();

			return false;
		}

		echo json_encode(1);
		jexit();
	}

	/**
	 * Function used to pdf export
	 *
	 * @return  html
	 *
	 * @since  1.0
	 */
	public function pdfExport()
	{
		require_once JPATH_SITE . '/components/com_tjlms/models/review.php';
		$app = JFactory::getApplication();

		$jinput = JFactory::getApplication()->input;

		$user = JFactory::getUser();
		$track_id = $jinput->get('track_id', '0', 'INT');
		$curriculam_id = $jinput->get('curId', '0', 'INT');
		$user_id = $user->id;

		if ($user_id && $curriculam_id)
		{
			$model  = $this->getModel('Review', 'TjlmsModel');
			$return = $model->pdfExport($track_id, $user_id, $curriculam_id);
		}
		else
		{
			return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
		}

		jexit();
	}

	/**
	 * Function used to get all reports
	 *
	 * @return  json
	 *
	 * @since  1.0
	 */
	public function getreport()
	{
		$input = JFactory::getApplication()->input;

		$report_id = $input->get('reportToLoad', '', 'INT');

		if (!empty($report_id))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from('#__tj_reports as r');
			$query->where('r.id =' . $report_id);

			$db->setQuery($query);
			$reports = $db->loadObject();

			echo json_encode($reports);

			die();
			jexit();
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
		$cid = JFactory::getApplication()->input->get('cid', '', 'array');
		$model = JModelLegacy::getInstance('Report', 'TjreportsModel');

		$result = $model->delete($cid);
		echo new JResponseJson($result);
		jexit();
	}

// This is useful for manager report
/**
	* Function used to delete reports
	*
	* @return  json
	*
	* @since  1.0
	*/
	public function setUserType()
	{
		$userTypeId = JFactory::getApplication()->input->get('userTypeId', '', 'int');
		$setUserType = JFactory::getApplication()->setUserState('setUserType', $userTypeId);

		echo new JResponseJson($setUserType);
		jexit();
	}
}
