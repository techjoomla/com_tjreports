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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


/**
 * Courses list controller class.
 *
 * @since  1.0.0
 */
class TjreportsControllerReports extends AdminController
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
		$app  = Factory::getApplication();
		$user = Factory::getUser();
		$input 	= Factory::getApplication()->input;
		$reportId = $input->post->get('reportId');

		if (!$user->authorise('core.export', 'com_tjreports.tjreport.' . $reportId))
		{
			if ($user->guest)
			{
				$return = base64_encode(Uri::getInstance());
				$login_url_with_return = Route::_('index.php?option=com_users&view=login&return=' . $return);
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
				$app->redirect($login_url_with_return, 403);
			}
			else
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		$this->model = $this->getModel('reports');
		$reportData = $this->model->getReportNameById($reportId);
		$pluginName = $reportData->plugin;

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $pluginName);
		$model = BaseDatabaseModel::getInstance($pluginName, 'TjreportsModel');

		$model->loadLanguage($pluginName);
		$input->set('limit', 0);
		$items     = $model->getItems();
		$columns   = $model->columns;
		$colToshow = $model->getState('colToshow');

		$colTitleArray = array();

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

					$colTitleArray[] = $contentTitle . ' ' . Text::sprintf($subTextTitle, $contentTitle, $contentId);
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

				$colTitleArray[] = Text::_($colTitle);
			}
		}

		$pluginTitle = $reportData->title;
		$filename = strtolower($pluginTitle) . "_report_" . date("Y-m-d_H-i", time());

		// Create a file pointer connected to the output stream
		$output = fopen('php://output', 'w');

		// Put CSV headings first
		fputcsv($output, $colTitleArray);

		// Set CSV headers
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv;');
		header('Content-Disposition: attachment; filename=' . $filename . '.csv');
		header('Content-Transfer-Encoding: binary');

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
						$itemCSV[] = $this->filterValue($item[$arrayKey][$subkey]);
					}
				}
				else
				{
					$itemCSV[] = $this->filterValue($item[$key]);
				}
			}

			fputcsv($output, $itemCSV);
		}

		fclose($output);
		Factory::getApplication()->close();
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
		$app = Factory::getApplication();

		$jinput = Factory::getApplication()->input;

		$user = Factory::getUser();
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
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 404);
		}

		Factory::getApplication()->close();
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

	/**
	 * Function to get default report to be shown
	 *
	 * @return  boolean
	 *
	 * @since  1.0
	 */
	public function defaultReport()
	{
		$input = Factory::getApplication()->input;
		$client = $input->get('client', '', 'STRING');

		$model = $this->getModel('reports');
		$reports = $model->getenableReportPlugins();

		$this->setRedirect(Route::_('index.php?option=com_tjreports&view=reports&client=' . $client . '&reportId=' . $reports[0]['reportId'], false));
	}
}
