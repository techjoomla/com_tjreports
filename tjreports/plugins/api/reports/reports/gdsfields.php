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
 * Tjreports API Gs fields class
 * This is used to get the fields for the Google studio connector
 *
 * @since  1.1.6
 */
class ReportsApiResourceGdsfields extends ApiResource
{
	/**
	 * Function to get fields
	 *
	 * @return json
	 */
	public function get()
	{
		$app        = Factory::getApplication();
		$jinput     = $app->input;
		$reportName = $app->input->getString('id');

		if (empty($reportName))
		{
			$reportName = $app->input->getString('report');
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

		// If plugin does not have getGoogleDsFields throw error
		if (!method_exists($reportPlugin, 'getGDSFields'))
		{
			ApiError::raiseError(400, Text::_('PLG_API_REPORTS_REPORT_NO_GOOGLESTUDIO_SUPPORT'), 'APIValidationException');
		}

		// Load language files
		$lang = Factory::getLanguage();
		$lang->load('com_tjreports', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_tjreports_' . $reportName, JPATH_SITE . "/plugins/tjreports/" . $reportName, 'en-GB', true);

		$this->plugin->setResponse($reportPlugin->getGDSFields());
	}
}
