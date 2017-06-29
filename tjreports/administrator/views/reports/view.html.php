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
require_once JPATH_COMPONENT . '/helpers/tjreports.php';


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

	protected $extension;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		// Check for view report permission from respective extension e.g : com_tjlms
		$reportsModel = $this->getModel();
		$this->extension = $reportsModel->getState('extension');

		$full_client = explode('.', $this->extension);

		// Eg com_tjlms
		$component = $full_client[0];
		$eName = str_replace('com_', '', $component);
		$file = JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component . '/helpers/' . $eName . '.php');

		if (file_exists($file))
		{
			require_once $file;

			$prefix = ucfirst(str_replace('com_', '', $component));
			$cName = $prefix . 'Helper';

			if (class_exists($cName))
			{
				$canDo = $cName::getActions();

				if (!$canDo->get('view.reports'))
				{
					JError::raiseError(500, JText::_('JERROR_ALERTNOAUTHOR'));

					return false;
				}
			}
		}

		$this->user       = JFactory::getUser();
		$this->user_id    = $this->user->id;
		$input = JFactory::getApplication()->input;

		if ($this->extension)
		{
			TjreportsHelper::addSubmenu('reports');
			$this->sidebar = JHtmlSidebar::render();
		}

		// Get saved data
		$queryId = $input->get('queryId', '0', 'INT');
		$client = $input->get('client', '', 'STRING');
		$reportToBuild = $input->get('reportToBuild', '', 'STRING');
		$reportId = $input->get('reportId', '', 'INT');

		if ($reportId)
		{
			$allow_permission = $this->user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);
			$input->set('allow_permission', $allow_permission);
		}

		$input->set('reportId', $reportId);
		$filterData  = $input->get('filters', '', 'ARRAY');
		$model = $this->getModel();

		// Get respected plugin data
		$this->items		= $model->getData($filterData);

		// Get all columns of that report
		$this->colNames	= $this->get('ColNames');

		$TjreportsModelReports = new TjreportsModelReports;

		// Get saved queries by the logged in users
		$this->saveQueries = $TjreportsModelReports->getSavedQueries($this->user_id, $reportToBuild);

		// Call helper function
		$TjreportsHelper = new TjreportsHelpersTjreports;
		$TjreportsHelper->getLanguageConstant();

		// Get all enable plugins
		$this->enableReportPlugins = $TjreportsModelReports->getenableReportPlugins();

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

		// List of plugin
		if ($this->extension)
		{
			foreach ($this->enableReportPlugins as $eachPlugin) :
					$button = "<a class='btn button report-btn' id='" . $eachPlugin->element . "'
				onclick=\"loadReport('" . $eachPlugin->element . "','" . $this->extension . "'); \" ><span
				class='icon-list'></span>" . JText::_($eachPlugin->name) . "</a>";
					$bar->appendButton('Custom', $button);
			endforeach;
		}

		else
		{
			JToolBarHelper::cancel('tjreport.cancel', 'JTOOLBAR_CANCEL');
		}

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
