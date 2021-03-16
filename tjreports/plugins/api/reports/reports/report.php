<?php
/**
 * @package      Tjreports
 * @subpackage   API
 *
 * @author       Techjoomla <extensions@techjoomla.com>
 * @copyright    Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Tjreports API report class
 *
 * @since  1.0.0
 */
class ReportsApiResourceReport extends ApiResource
{
	/**
	 * Function to get report data from tjreports plugin
	 *
	 * @return json
	 */
	public function post()
	{
		$app        = Factory::getApplication();
		$jinput     = $app->input;
		$formData   = $jinput->post;
		$reportName = $app->input->getString('id');

		if (empty($reportName))
		{
			$reportName = $formData->getString('report');
		}

		if (!isset($reportName))
		{
			ApiError::raiseError(400, Text::_('PLG_API_REPORTS_REPORT_NAME_MISSSING'), 'APIValidationException');
		}

		// Create object of tjreports plugin class

		JLoader::import('plugins.tjreports.' . $reportName . "." . $reportName, JPATH_SITE);
		$className = 'TjreportsModel' . ucfirst($reportName);

		if (!class_exists($className))
		{
			ApiError::raiseError(400, Text::_('PLG_API_REPORTS_REPORT_NAME_INVALID'), 'APIValidationException');
		}

		$reportPlugin = new $className;

		// Load language files
		$lang = Factory::getLanguage();
		$lang->load('com_tjreports', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_tjreports_' . $reportName, JPATH_SITE . "/plugins/tjreports/" . $reportName, 'en-GB', true);

		// Get filters and cols
		$reportId = $reportPlugin->getDefaultReport($reportName);
		$reportFilters = ($formData->get('filters')) ? $formData->get('filters') : [];
		$reportCols    = ($formData->get('colToshow')) ? $formData->get('colToshow') : [];

		// Set reportId in input
		$app->input->set('reportId', $reportId);

		$reportPlugin->setState('filters', $reportFilters);
		$reportPlugin->setState('colToshow', $reportCols);
		$reportPlugin->setState('reportId', $reportId);

		// Get results and errors if any
		$report = $reportPlugin->getItems();
		$errors = $reportPlugin->getTJRMessages();

		if (!empty($errors))
		{
			ApiError::raiseError(400, $errors[0], 'APIValidationException');
		}

		// @TODO Handle else condition first to reduce nesting
		if (!empty($reportCols))
		{
			foreach ($report as $key => $value)
			{
				foreach ($value as $k => $v)
				{
					if (!in_array($k, $reportCols))
					{
						unset($value[$k]);
					}
				}

				$report[$key] = $value;
			}
		}

		$this->plugin->customAttributes->set("total", $reportPlugin->getTotal());
		$this->plugin->setResponse($report);
	}
}
