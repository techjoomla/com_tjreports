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

/**
 * Tjreports API to get the tjreports plugin those support Google studio connector
 *
 * @since  1.1.6
 */
class ReportsApiResourceGdsreports extends ApiResource
{
	/**
	 * Function get reports
	 *
	 * @return boolean
	 */
	public function get()
	{
		// Create object of tjreports plugin class
		JLoader::import('components.com_tjreports.models.reports', JPATH_SITE);
		$reportModel = new TjreportsModelreports;

		$reports 	= $reportModel->getenableReportPlugins();
		$reportsArray = array();

		foreach ($reports as $report)
		{
			$pluginModel = $reportModel->getPluginModel($report['plugin']);

			if (method_exists($pluginModel, 'getGDSFields'))
			{
				$reportsArray[] = $report;
			}
		}

		$this->plugin->setResponse($reportsArray);
	}
}
