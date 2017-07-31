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
JLoader::import('components.com_tjreports.helpers.tjreports', JPATH_SITE);
JLoader::import('components.com_tjreports.models.tjreports', JPATH_SITE);
JLoader::import('components.com_tjreports.helpers.tjreports', JPATH_SITE);

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

	protected $tjrData;

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
		$canDo 		= TjreportsHelpersTjreports::getActions();
		$input 		= JFactory::getApplication()->input;
		$user		= JFactory::getUser();

		$this->pluginName = $input->get('reportToBuild', '', 'STRING');
		$this->client     = $input->get('client', '', 'STRING');
		$this->queryId    = $input->get('queryId', 0, 'INT');

		$this->model = $this->getModel($this->pluginName);
		$this->setModel($this->model, true);

		if (!$canDo->get('view.reports'))
		{
			JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		$this->reportId = $input->get('reportId', 0, 'INT');

		if ($this->reportId)
		{
			$allow_permission = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $this->reportId);
			$input->set('allow_permission', $allow_permission);
		}

		$user_id = $user->id;

		if ($type == 'html' && $this->queryId)
		{
			$queryData  = $this->model->getQueryData($this->queryId);
			$param 		= json_decode($queryData->param, true);

			if (isset($param['data']))
			{
				$data = (array) $param['data'];

				$postFilters = array('colToshow', 'filters', 'limit', 'list_limit');

				foreach ($postFilters as $postFilter)
				{
					if (isset($data[$postFilter]))
					{
						$input->set($postFilter, $data[$postFilter]);
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

		//~ // Set default values if not passed in request
		//~ $list_limit = $app->get('list_limit', 0);
//~
		//~ $initializeVars = array(
			//~ 'filters' => array(), 'displayFilters' => array(), 'sortable' => array(), 'showHideColumns' => array(), 'colToshow' => array(),
			//~ 'items' => array(), 'filter_order' => '', 'filter_order_Dir' => '', 'limit' => $list_limit, 'limitstart' => 0, 'total_rows' => 0,
			//~ 'headerLevel' => 1, 'reportId' => 0, 'extension' => '',
			//~ 'messages' => array(), 'styles' => array(), 'scripts' => array(),
		//~ );
//~
		//~ foreach ($initializeVars as $key => $value)
		//~ {
			//~ if (!isset($this->data[$key]))
			//~ {
				//~ $this->data[$key] = $value;
			//~ }
		//~ }

		$this->items      = $this->model->getItems();
		$this->state      = $this->get('State');
		$this->pagination = $this->get('pagination');
		$this->tjrData    = $this->model->getReportData();

		// Get Report data
		//$dispatcher->trigger('getTJRData', array(&$this->data));

		// Pagination
		//~ $limit = $this->data['limit'];
		//~ $limitstart = $this->data['limitstart'];
		//~ $total = $this->data['total_rows'];
		//~ $this->pagination = new JPagination($total, $limitstart, $limit);



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
