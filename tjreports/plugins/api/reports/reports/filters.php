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
class ReportsApiResourceFilters extends ApiResource
{
	/**
	 * Function get filters data
	 *
	 * @return boolean
	 */
	public function get()
	{
		$app         = Factory::getApplication();
		$jinput      = $app->input;
		$reportName  = $jinput->getString('id');

		if (!isset($reportName))
		{
			ApiError::raiseError(400, Text::_('PLG_API_REPORTS_REPORT_NAME_MISSSING'), 'APIValidationException');
		}

		$lang = Factory::getLanguage();
		//load default joomla language file
		$lang->load('', JPATH_ADMINISTRATOR, 'en-GB', true);

		// Make object of the tjreports plugin to load filters for
		JLoader::import('plugins.tjreports.' . $reportName . "." . $reportName, JPATH_SITE);
		$className = 'TjreportsModel' . ucfirst($reportName);

		if (!class_exists($className))
		{
			ApiError::raiseError(400, Text::_('PLG_API_REPORTS_REPORT_NAME_INVALID'), 'APIValidationException');
		}

		$reportPlugin = new $className;

		$filters = $reportPlugin->displayFilters();
		$filter_array = [];
		foreach ($filters[0] as $key => $value) {
			$value['name'] = $key;
			$filter_array[] = $value;
		}

		$this->plugin->setResponse($filter_array);
	}
}
