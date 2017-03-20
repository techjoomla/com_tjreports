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
require_once JPATH_SITE . '/components/com_tjreports/helpers/tjreports.php';
require_once JPATH_SITE . '/components/com_tjreports/models/reports.php';

// Import Csv export button
jimport('techjoomla.tjtoolbar.button.csvexport');

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
		$user       = JFactory::getUser();
		$user_id    = $user->id;
		$input = JFactory::getApplication()->input;

		if (!$canDo->get('view.reports'))
		{
			JError::raiseError(500, JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		// Get saved data
		$queryId = $input->get('queryId', '0', 'INT');
		$client = $input->get('client', '', 'STRING');
		$reportToBuild = $input->get('reportToBuild', '', 'STRING');
		$reportId = $input->get('reportId', '', 'INT');

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

		$TjreportsModelReports = new TjreportsModelReports;

		// Get saved queries by the logged in users
		$this->saveQueries = $TjreportsModelReports->getSavedQueries($user_id, $reportToBuild);

		// Call helper function
		$TjreportsHelper = new TjreportsHelpersTjreports;
		$TjreportsHelper->getLanguageConstant();

		// Get all enable plugins
		$this->enableReportPlugins = $this->get('enableReportPlugins');

		$this->colToshow = array();

		if ($queryId != 0)
		{
			$model = $this->getModel();
			$colToSelect = array('colToshow');

			$QueryData = $model->getQueryData($queryId);

			$param = json_decode($QueryData->param);
			$this->colToshow = $param->colToshow;
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->addToolbar();

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

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  Toolbar instance
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		// Old code

		$bar = JToolBar::getInstance('toolbar');
		JToolBarHelper::title(JText::_('COM_TJREPORTS_TITLE_REPORT'), 'list');

		$button = "<a class='btn' class='button'
			type='submit' onclick=\"Joomla.submitbutton('reports.csvexport');\" href='#'><span title='Export'
			class='icon-download'></span>" . JText::_('COM_TJREPORTS_CSV_EXPORT') . "</a>";
		$bar->appendButton('Custom', $button);

/*
		foreach ($this->enableReportPlugins as $eachPlugin) :
				$button = "<a class='btn button report-btn' id='" . $eachPlugin->element . "'
							 onclick=\"loadReport('" . $eachPlugin->element . "'); \" ><span
							class='icon-list'></span>" . JText::_($eachPlugin->name) . "</a>";
				$bar->appendButton('Custom', $button);
		endforeach;
*/

/*
 * 		// CSV export code for ajax based
		$this->extra_sidebar = '';

		require_once JPATH_SITE . '/helpers/tjreports.php';
		$state	= $this->get('State');
		$bar = JToolBar::getInstance('toolbar');
		JToolBarHelper::title(JText::_('COM_TJREPORTS_TITLE'), 'list');

		if (!empty($this->items))
		{
			$message = array();
			$message['success'] = JText::_("COM_TJREPORTS_EXPORT_FILE_SUCCESS");
			$message['error'] = JText::_("COM_TJREPORTS_EXPORT_FILE_ERROR");
			$message['inprogress'] = JText::_("COM_TJREPORTS_EXPORT_FILE_NOTICE");
			$bar->appendButton('CsvExport', $message);
		}

		$this->extra_sidebar = '';
*/
	}
}
