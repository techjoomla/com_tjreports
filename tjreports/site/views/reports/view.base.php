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

Use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

jimport('joomla.application.component.view');
JLoader::import('components.com_tjreports.helpers.tjreports', JPATH_ADMINISTRATOR);
JLoader::import('components.com_tjreports.models.tjreports', JPATH_SITE);
JLoader::import('components.com_tjreports.helpers.tjreports', JPATH_SITE);
JLoader::register('JToolBarHelper', JPATH_ADMINISTRATOR . '/includes/toolbar.php');

/**
 * View class for a list of Tjreports.
 *
 * @since  1.0.0
 */
class ReportsViewBase extends JViewLegacy
{
	protected $items;

	protected $pagination;

	protected $state;

	protected $client;

	protected $pluginName;

	protected $reportData;

	protected $headerLevel;

	protected $reportId = 0;

	protected $queryId = 0;

	protected $savedQueries = array();

	protected $defaultColToHide;

	protected $columns;

	protected $showhideCols;

	protected $filterParamColToshow;

	protected $defaultColToShow;

	protected $sortableColumns;

	protected $showSearchResetButton;

	protected $showSummaryReport;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $type  document type
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function processData($type = 'html')
	{
		$canDo = TjreportsHelper::getActions();
		$input = Factory::getApplication()->input;
		$user  = Factory::getUser();

		$this->reportId = $input->get('reportId', 0, 'INT');
		$this->model    = $this->getModel('reports');

		$reports        = $this->model->getenableReportPlugins();
		$this->reportId = $this->reportId ? $this->reportId : (isset($reports['0']['reportId']) ? $reports['0']['reportId'] : '');

		$this->reportData = $this->model->getReportNameById($this->reportId);
		$this->pluginName = $this->reportData->plugin;
		$this->client     = $input->get('client', '', 'STRING');
		$this->queryId    = $input->get('queryId', 0, 'INT');

		JLoader::register('TjreportsHelper', JPATH_ADMINISTRATOR . '/components/com_tjreports/helpers/tjreports.php');
		JLoader::load('TjreportsHelper');
		$this->tjreportsHelper = new TjreportsHelper;

		if (!$canDo->get('core.view') || !$this->pluginName)
		{
			JError::raiseWarning(403, Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		$this->model = $this->getModel($this->pluginName);

		if ($this->model)
		{
			$this->setModel($this->model, true);
		}

		if ($this->reportId)
		{
			$allow_permission = $user->authorise('core.view', 'com_tjreports.tjreport.' . $this->reportId);

			if (!$allow_permission)
			{
				JError::raiseWarning(403, Text::_('JERROR_ALERTNOAUTHOR'));

				return false;
			}
		}
		else
		{
			JError::raiseWarning(403, Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		$user_id = $user->id;

		if ($type == 'html' && $this->queryId)
		{
			$queryData  = $this->model->getQueryData($this->queryId);

			if (!$queryData)
			{
				$this->queryId = 0;
			}
			elseif (!empty($queryData->param))
			{
				$param       = json_decode($queryData->param, true);
				$postFilters = $this->model->getValidRequestVars();

				// Check if multiple result sets are allowed in the Report
				if ($this->model->allowToCreateResultSets)
				{
					if (count($param['filters']) >= 1)
					{
						// Initiate the number of filter sets count
						Factory::getDocument()->addScriptDeclaration('
							addMorefilterCnt = ' . count($param['filters'])
						);
					}

					// Post the count of filter set to set the filters in the saved report
					$input->set('addMorefilter', count($param['filters']));
				}

				foreach ($postFilters as $postFilter => $filterType)
				{
					// If filters are set in saved queries, then by default keep the Search Tools open
					if ($postFilter == 'filters' && isset($param[$postFilter]) && !empty($param['filters']))
					{
						Factory::getDocument()->addScriptDeclaration('
							jQuery(document).ready(function (){
								tjrContentUI.report.showFilter();
							});
						'
						);
					}

					if (isset($param[$postFilter]))
					{
						$input->set($postFilter, $param[$postFilter]);
					}
				}
			}
		}

		// Get saved queries of logged in user
		$savedQueries = $this->model->getSavedQueries($user_id, $this->pluginName);

		if (!empty($savedQueries))
		{
			$qOptions   = array();
			$qOptions[] = HTMLHelper::_('select.option', '', Text::_('COM_TJREPORTS_SELONE_QUERY'));

			foreach ($savedQueries as $savedQuery)
			{
				$qOptions[] = HTMLHelper::_('select.option', $savedQuery->id, $savedQuery->title);
			}

			$this->savedQueries = $qOptions;
		}

		// Get all report plugin
		$dispatcher   = JEventDispatcher::getInstance();
		$pluginExists = PluginHelper::getPlugin('tjreports', $this->pluginName);

		if (!$pluginExists || !$this->pluginName)
		{
			JError::raiseError(404, Text::_('COM_TJREPORTS_PLUGIN_DESABLED_OR_NOT_EXISTS'));

			return false;
		}

		$this->model->loadLanguage($this->pluginName);
		$this->state      = $this->get('State');
		$this->items      = $this->model->getItems();
		$this->pagination = $this->get('pagination');

		$this->headerLevel     = $this->model->headerLevel;

		// Array_key - defaultColToHide column are present then get the key as value.
		$defaultColToHide       = (array) $this->model->getState('defaultColToHide');
		$this->defaultColToHide = array_keys($defaultColToHide);
		$this->columns          = $this->model->columns;

		/* Array_merge - here colToshow means get all true value array so want to mearg defaultColToHide column and then using
		 * array_intersect - only remove those column which is force fully added in load param in showhideCols config
		 */
		$this->showHideColumns = $this->model->showhideCols;

		/* To get the columns from loadparams*/
		$this->defaultColToshow = $this->model->filterParamColToshow;

		/* Check the columns in loadparams are available or not & show plugin level columns & custom field columns
		 * in case if load params are not available*/
		if (empty($this->defaultColToshow))
		{
			$this->defaultColToshow = $this->model->defaultColToShow;
		}

		if (!empty($this->defaultColToHide))
		{
			$this->showHideColumns = array_intersect($this->model->showhideCols, array_merge($this->defaultColToshow, $this->defaultColToHide));
		}

		$this->sortable                = $this->model->sortableColumns;
		$this->emailColumn             = $this->model->getState('emailColumn');
		$this->srButton                = $this->model->showSearchResetButton;
		$this->colToshow               = $this->model->getState('colToshow');
		$this->filterValues            = $this->model->getState('filters');
		$this->userFilters             = $this->model->displayFilters();
		$this->messages                = $this->model->getTJRMessages();
		$this->showSummaryReport       = $this->model->getState('showSummaryReport');
		$this->enableReportPlugins     = $this->model->getenableReportPlugins($this->client);
		$this->isExport                = $user->authorise('core.export', 'com_tjreports.tjreport.' . $this->reportId);

		// This will be used to create multiple result sets
		$this->allowToCreateResultSets = $this->model->allowToCreateResultSets;

		// These two are used to generate filters sets similar to addMore in subforms
		$this->addMorefilter = $input->get('addMorefilter', 1, 'INT');
		$this->removeFilter  = $input->get('removeFilter', '', 'STRING');

		return true;
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
	 *
	 * @since   3.0
	 */
	public function getModel($name = '', $prefix = '', $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $name);

		return BaseDatabaseModel::getInstance($name, 'TjreportsModel', $config);
	}
}
