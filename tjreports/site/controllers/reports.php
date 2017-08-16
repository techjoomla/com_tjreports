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
	 * Function used to export data in csv format
	 *
	 * @return  jexit
	 *
	 * @since   1.0.0
	 */
	public function csvexport()
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();

		if (!$user->authorise('core.export', 'com_tjreports'))
		{
			if ($user->guest)
			{
				$return = base64_encode(JUri::getInstance());
				$login_url_with_return = JRoute::_('index.php?option=com_users&view=login&return=' . $return);
				$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'notice');
				$app->redirect($login_url_with_return, 403);
			}
			else
			{
				$app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		$input 	= JFactory::getApplication()->input;
		$pluginName = $input->post->get('reportToBuild');

		JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $pluginName);
		$model = JModelLegacy::getInstance($pluginName, 'TjreportsModel');

		$model->loadLanguage($pluginName);
		$input->set('limit', 0);
		$items     = $model->getItems();
		$columns   = $model->columns;
		$colToshow = $model->getState('colToshow');

		$csvData     = null;
		$csvData_arr = $colTitleArray = array();

		foreach ($colToshow as $index => $detail)
		{
			if (strpos($index, '::'))
			{
				$indexArray   = explode('::', $index);
				$contentTitle = $indexArray[1];
				$contentId    = $indexArray[0];

				foreach ($detail as $subKey => $subDetail)
				{
					$keyDetails   = explode('::', $subKey);

					if (!isset($columns[$subKey]['title']))
					{
						$subTextTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $keyDetails[0] . '_' . $keyDetails[1] . '_TITLE');
					}
					else
					{
						$subTextTitle = $columns[$subKey]['title'];
					}

					$colTitleArray[] = $contentTitle . ' ' . JText::sprintf($subTextTitle, $contentTitle, $contentId);
				}
			}
			else
			{
				$colKey = $detail;

				if (!isset($columns[$colKey]['title']))
				{
					$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
				}
				else
				{
					$colTitle = $columns[$colKey]['title'];
				}

				$colTitleArray[] = JText::_($colTitle);
			}
		}

		$csvData .= implode(',', $colTitleArray);
		$csvData .= "\n";
		echo $csvData;

		$csvData  = '';
		$filename = "tjreports_" . $pluginName . "_report_" . date("Y-m-d_H-i", time());

		// Set CSV headers
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $filename . ".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		// Loop through items
		foreach ((array) $items as $itemKey => $item)
		{
			$itemCSV = array();

			foreach ($colToshow as $arrayKey => $key)
			{
				if (is_array($key))
				{
					foreach ($key as $subkey => $subVal)
					{
						$final_text_value = $this->filterValue($item[$arrayKey][$subkey]);

						// Add data in the Quotes and asign it in the csv array
						$itemCSV[] = '"' . $final_text_value . '"';
					}
				}
				else
				{
					$final_text_value = $this->filterValue($item[$key]);

					// Add data in the Quotes and asign it in the csv array
					$itemCSV[] = '"' . $final_text_value . '"';
				}
			}

			// TRIGGER After csv body add extra fields
			echo implode(',', $itemCSV) . "\n";
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
		$db        		= JFactory::getDBO();
		$input 			= JFactory::getApplication()->input;
		$post			= $input->post->getArray();
		$current_user 	= JFactory::getUser()->id;

		$queryName 	= $db->escape($post['queryName']);
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

		$result = array('success' => true);

		if (!$db->insertObject('#__tj_reports', $insert_object, 'id'))
		{
			$result['error'] = $db->stderr();
			$result['success'] = false;

			return false;
		}

		echo json_encode($result);
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

	/**
	 * Function used to delete reports
	 *
	 * @param   ARRAY  $data  The data to filter
	 *
	 * @return  boolean
	 *
	 * @since  1.0
	 */
	private function filterValue($data)
	{
		// Remove double Quotes from the data
		$finalValue = strip_tags($data);

		// Remove double Quotes from the data
		$finalValue = str_replace('"', '', $finalValue);

		// Remove single Quotes from the data
		$finalValue = str_replace("'", '', $finalValue);

		// Remove tabs and newlines from the data
		$finalValue = preg_replace('/(\r\n|\r|\n)+/', " ", $finalValue);

		// Remove extra spaces from the data
		$finalValue = preg_replace('/\s+/', " ", $finalValue);

		return $finalValue;
	}
}
