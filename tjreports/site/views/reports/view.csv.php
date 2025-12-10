<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjreports
 * @copyright  Copyright (C) 2005 - 2018. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
require_once JPATH_LIBRARIES . '/techjoomla/view/csv.php';
require_once JPATH_ADMINISTRATOR . '/components/com_tjreports/helpers/tjreports.php';

/**
 * CSV class for a list of Tjreports.
 *
 * @since  1.0.0
 */
class TjreportsViewReports extends TjExportCsv
{
	protected $limitStart;

	protected $data;

	protected $recordCnt;

	protected $headers;

	protected $fileName;
	/**
	 * call exportCsv function from techjoomla (techjoomla.view.csv) library.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  Object|Boolean in case of success instance and failure - boolean
	 *
	 * @since	1.6
	 */
	public function display($tpl = null)
	{
		$input = Factory::getApplication()->input;
		$user  = Factory::getUser();
		$canDo = TjreportsHelper::getActions();
		$reportId = $input->post->get('reportId');
		$userAuthorisedExport = $user->authorise('core.export', 'com_tjreports.tjreport.' . $reportId);

		if (!$canDo->get('core.export') || !$user)
		{
			// Redirect to the list screen.
			$redirect = Route::_('index.php?option=com_tjreports&view=reports', false);
			Factory::getApplication()->redirect($redirect, Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}
		else
		{
			if ($input->get('task') == 'download')
			{
				$fileName = $input->get('file_name');
				$this->download($fileName);
				Factory::getApplication()->close();
			}
			else
			{
				if ($userAuthorisedExport)
				{
					$this->getItems();

					parent::display();
				}
				else
				{
					Factory::getApplication()->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');

					return false;
				}
			}
		}
	}

	/**
	 * Get the data for CSV file.
	 *
	 * @return  Mixed
	 *
	 * @since	1.1
	 */
	private function getItems()
	{
		JLoader::import('components.com_reports.models.reports', JPATH_SITE);
		$reportModel = new TjreportsModelReports;
		$input = Factory::getApplication()->input;
		$this->limitStart = $input->get('limitstart');
		$this->limit = $reportModel->getState('list.limit');
		$reportId = $input->post->get('reportId');

		$reportData = $reportModel->getReportNameById($reportId);
		$pluginName = $reportData->plugin;

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $pluginName);
		$model = BaseDatabaseModel::getInstance($pluginName, 'TjreportsModel');

		$model->loadLanguage($pluginName);

		$input->set('limit', $this->limit);
		$input->set('limitstart', $this->limitStart);

		$this->recordCnt = $model->getTotal();
		$items = $model->getItems();
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
						$subTextTitle = 'PLG_TJREPORTS_' . strtoupper($pluginName . '_' . $keyDetails[0] . '_' . $keyDetails[1] . '_TITLE');
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
					$colTitle = 'PLG_TJREPORTS_' . strtoupper($pluginName . '_' . $colKey . '_TITLE');
				}
				else
				{
					$colTitle = $columns[$colKey]['title'];
				}

				$colTitleArray[] = Text::_($colTitle);
			}
		}

		$this->headers = $colTitleArray;
		$pluginTitle = str_replace(" ", "_", $reportData->title);

		// Remove special character from plugin name
		$pluginTitle = preg_replace('/[^A-Za-z0-9\-]/', '', $pluginTitle);
		$this->fileName = rtrim(strtolower($pluginTitle)) . "_report_" . date("Y-m-d_H-i", time());

		// Loop through items
		// foreach ((array) $items as $itemKey => &$item)
		foreach ($items as &$item)
		{
			$itemCSV = array();

			foreach ($colToshow as $arrayKey => $key)
			{
				if (is_array($key))
				{
					foreach ($key as $subkey => $subVal)
					{
						$final_text_value = $this->filterValue($item[$arrayKey][$subkey]);
						$itemCSV[] = $final_text_value;
					}
				}
				else
				{
					$final_text_value = $this->filterValue($item[$key]);
					$itemCSV[] = $final_text_value;
				}
			}

			$item = $itemCSV;
		}

		$this->data = $items;
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
		$finalValue = strip_tags($data ? $data : '');

		// Remove double Quotes from the data
		// $finalValue = str_replace('"', '', $finalValue);

		// Remove single Quotes from the data
		// $finalValue = str_replace("'", '', $finalValue);

		// Remove tabs and newlines from the data
		$finalValue = preg_replace('/(\r\n|\r|\n)+/', " ", $finalValue);

		// Remove extra spaces from the data
		$finalValue = preg_replace('/\s+/', " ", $finalValue);

		return $finalValue;
	}
}
