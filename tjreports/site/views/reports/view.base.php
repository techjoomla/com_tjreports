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

	protected $savedQueries = array();

	protected $reportId = 0;

	protected $queryId = 0;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $type  document type
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function processData($type = 'html')
	{
		$app 		= JFactory::getApplication();
		$canDo 		= TjreportsHelper::getActions();
		$input 		= JFactory::getApplication()->input;
		$user		= JFactory::getUser();

		$this->reportId = $input->get('reportId', 0, 'INT');
		$this->model = $this->getModel('reports');

		$reports = $this->model->getenableReportPlugins();
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
			JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		$this->model = $this->getModel($this->pluginName);
		$this->setModel($this->model, true);

		if ($this->reportId)
		{
			$allow_permission = $user->authorise('core.view', 'com_tjreports.tjreport.' . $this->reportId);

			if (!$allow_permission)
			{
				JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));

				return false;
			}
		}
		else
		{
			JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));

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

				foreach ($postFilters as $postFilter => $filterType)
				{
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
			$qOptions	= array();
			$qOptions[] 	= JHTML::_('select.option', '', JText::_('COM_TJREPORTS_SELONE_QUERY'));

			foreach ($savedQueries as $savedQuery)
			{
				$qOptions[] = JHTML::_('select.option', $savedQuery->id, $savedQuery->title);
			}

			$this->savedQueries = $qOptions;
		}

		// Get all report plugin
		$dispatcher   = JEventDispatcher::getInstance();
		$pluginExists = JPluginHelper::getPlugin('tjreports', $this->pluginName);

		if (!$pluginExists || !$this->pluginName)
		{
			JError::raiseError(404, JText::_('COM_TJREPORTS_PLUGIN_DESABLED_OR_NOT_EXISTS'));

			return false;
		}

		$this->model->loadLanguage($this->pluginName);
		$this->state      = $this->get('State');
		$this->items      = $this->model->getItems();
		$this->pagination = $this->get('pagination');

		$this->headerLevel     = $this->model->headerLevel;
		$this->columns         = $this->model->columns;
		$this->showHideColumns = $this->model->showhideCols;
		$this->sortable        = $this->model->sortableColumns;
		$this->srButton        = $this->model->showSearchResetButton;

		$this->colToshow       = $this->model->getState('colToshow');
		$this->filterValues    = $this->model->getState('filters');

		$this->userFilters     = $this->model->displayFilters();
		$this->messages        = $this->model->getTJRMessages();

		$this->enableReportPlugins = $this->model->getenableReportPlugins($this->client);
		$this->isExport = $user->authorise('core.export', 'com_tjreports.tjreport.' . $this->reportId);

		return true;
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy|boolean  Model object on success; otherwise false on failure.
	 *
	 * @since   3.0
	 */
	public function getModel($name = '', $prefix = '', $config = array())
	{
		JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $name);

		return JModelLegacy::getInstance($name, 'TjreportsModel', $config);
	}
}
