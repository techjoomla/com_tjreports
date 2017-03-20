<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjreports
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require_once JPATH_COMPONENT . '/helpers/tjreports.php';

/**
 * View class for a list of Tjreports.
 *
 * @since  1.0.0
 */
class TjreportsViewReports extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$canDo = TjreportsHelpersTjreports::getActions();
		$user_id = JFactory::getUser()->id;
		$input = JFactory::getApplication()->input;
		$TjreportsModelReports = new TjreportsModelReports;
		$app = JFactory::getApplication();
/*
		if (!$canDo->get('view.reports'))
		{
			JError::raiseError(500, JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}
*/

		$client = $input->get('client', '', 'STRING');

		// Get all vendars from backend
		if (empty($client))
		{
			$params = JComponentHelper::getParams('com_tjreports');
			$client = $params->get('vendars');
			$input->set('client', $client);
		}

		// Get saved data
		$queryId = $input->get('queryId', '0', 'INT');
		$reportToBuild = $input->get('reportToBuild', '', 'STRING');
		$reportId = $input->get('reportId', '', 'INT');

		$this->options		= $this->get('reportoptions');

		$user       = JFactory::getUser();

		if ($reportId)
		{
			$allow_permission = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);
			$input->set('allow_permission', $allow_permission);
		}

		$input->set('reportId', $reportId);

		// Get respected plugin data
		$this->items		= $this->get('Data');

		// Get all columns of that report
		$this->colNames	= $this->get('ColNames');

		// Get saved queries by the logged in users

		$this->saveQueries = $TjreportsModelReports->getSavedQueries($user_id, $reportToBuild);

		// Call helper function
		$TjreportsHelper = new TjreportsHelpersTjreports;
		$TjreportsHelper->getLanguageConstant();

		// Get all enable plugins
		$this->enableReportPlugins = $this->get('enableReportPlugins');

		// Get saved data
		$queryId = $input->get('queryId', '0', 'INT');

		$this->colToshow = array();

		if ($queryId != 0)
		{
			$model = $this->getModel();
			$colToSelect = array('colToshow');
			$QueryData = $model->getQueryData($queryId);
			$param = json_decode($QueryData->param);
			$this->colToshow = $param->colToshow;
		}

		$input = JFactory::getApplication()->input;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		if (!empty($this->saveQueries))
		{
			$saveQueries = array();
			$saveQueries[] = JHTML::_('select.option', '', JText::_('COM_TJREPORTS_SELONE_QUERY'));

			foreach ($this->saveQueries as $eachQuery)
			{
				$saveQueries[] = JHTML::_('select.option', $eachQuery->plugin . '_' . $eachQuery->id, $eachQuery->title);
			}

			$this->saveQueriesList = $saveQueries;
		}

		parent::display($tpl);
	}
}
