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
jimport('joomla.application.component.view');
jimport('techjoomla.view.csv');
JLoader::import('components.com_tjreports.helpers.tjreports', JPATH_ADMINISTRATOR);

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
		$input = JFactory::getApplication()->input;
		$user  = JFactory::getUser();
		$canDo = TjreportsHelper::getActions();
		$reportId = $input->post->get('reportId');
		$userAuthorisedExport = $user->authorise('core.export', 'com_tjreports.tjreport.' . $reportId);

		if (!$canDo->get('core.export') || !$user)
		{
			// Redirect to the list screen.
			$redirect = JRoute::_('index.php?option=com_tjreports&view=reports', false);
			JFactory::getApplication()->redirect($redirect, JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}
		else
		{
			if ($input->get('task') == 'download')
			{
				$fileName = $input->get('file_name');
				$this->download($fileName);
				JFactory::getApplication()->close();
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
					JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));

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
		$input = JFactory::getApplication()->input;
		$this->limitStart = $input->get('limitstart');
		$this->limit = $reportModel->getState('list.limit');
		$reportId = $input->post->get('reportId');

		$reportData = $reportModel->getReportNameById($reportId);
		$pluginName = $reportData->plugin;

		JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $pluginName);
		$model = JModelLegacy::getInstance($pluginName, 'TjreportsModel');

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

					$colTitleArray[] = $contentTitle . ' ' . JText::sprintf($subTextTitle, $contentTitle, $contentId);
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

				$colTitleArray[] = JText::_($colTitle);
			}
		}

		$this->headers = $colTitleArray;
		$pluginTitle = str_replace(" ", "_", $reportData->title);
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
