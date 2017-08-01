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

jimport('joomla.application.component.modellist');

/**
 * Methods supporting a list of Tjreports records.
 *
 * @since  1.0.0
 */
class TjreportsModelReports extends JModelList
{
	// Default ordering of Data
	protected $default_order = '';

	// Default ordering direction
	protected $default_order_dir = 'ASC';

	// Number of TH Rows required in the table
	protected $headerLevel = 1;

	// Add custom messages
	protected $messages = array();

	// Columns which are not possible to sort by SQl Order by
	protected $sortableWoQuery = array();

	/**
	 * Methods to get all data after processing
	 *
	 * @return ARRAY Data of report
	 *
	 * @since   2.0
	 */
	public function getReportData()
	{
		$data = array();
		$data['headerLevel']      = $this->getHeaderLevel();
		$data['displayFilters']   = $this->displayFilters();
		$data['headerColumns']    = $this->getHeaderColumns();
		$data['showHideColumns']  = $this->getShowHideColumns();
		$data['colToshow']        = $this->getState('colToshow');
		$data['messages']         = $this->getTJRMessages();
		$data['sortable']         = $this->getSortableColumns();

		return $data;
	}

	/**
	 * Get table header columns name, Must be overriden by child class
	 *
	 * @return ARRAY Keys of data
	 *
	 * @since   2.0
	 * */
	public function getHeaderColumns()
	{
		return array();
	}

	/**
	 * Get number of Table header level
	 *
	 * @return INT Number of TH levels
	 *
	 * @since   2.0
	 * */
	public function getHeaderLevel()
	{
		return $this->headerLevel;
	}

	/**
	 * Get Columns name that a User can switch to hide & show
	 *
	 * @return ARRAY Column array
	 *
	 * @since   2.0
	 * */
	public function getShowHideColumns()
	{
		return $this->headerColumns();
	}

	/**
	 * Get Columns name that a User can switch to hide & show
	 *
	 * @return ARRAY Column array
	 *
	 * @since   2.0
	 * */
	public function getColumnsToShow()
	{
		return $this->headerColumns();
	}

	/**
	 * Get Columns name that a User can sort
	 *
	 * @return ARRAY Column array
	 *
	 * @since   2.0
	 * */
	public function getSortableColumns()
	{
		return $this->headerColumns();
	}

	/**
	 * Get Custom messages for the report
	 *
	 * @return  ARRAY Column array
	 *
	 * @since   2.0
	 * */
	public function getTJRMessages()
	{
		return $this->messages;
	}

	/**
	 * Sets Custom messages for the report.
	 *
	 * @param   string  $message  Message.
	 *
	 * @return  Void
	 *
	 * @since   2.0
	 * @throws  RuntimeException
	 */
	public function setTJRMessages($message)
	{
		array_push($this->messages, $message);
	}

	/**
	 * Get filters of Report, Child class should override to add their filter
	 *
	 * @return   MIX  Should return array of array
	 *
	 * @since    2.0
	 */
	protected function displayFilters()
	{
		/*
		return array(
			array(
				'column_key' => array(
					'html' => '<input type="text" name="filter[column_key]" ' .
						'onkeydown="tjrContentUI.report.submitOnEnter(event);" value="' .
						( isset($filters['column_key']) ? $filters['column_key'] : '')
						. '" />',
					'type' => 'custom',
					'searchin' => 'DATE(lt.last_accessed_on)<= %s'
				)
			)
		);
		 */
		return array();
	}

	/**
	 * Gets an array of Assoc from the results of database query.
	 *
	 * @param   string   $query       The query.
	 * @param   integer  $limitstart  Offset.
	 * @param   integer  $limit       The number of records.
	 *
	 * @return  object[]  An array of results.
	 *
	 * @since   3.0
	 * @throws  RuntimeException
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		$this->getDbo()->setQuery($query, $limitstart, $limit);

		return $this->getDbo()->loadAssocList();
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   12.2
	 */
	protected function populateState($ordering = '', $direction = 'ASC')
	{
		// List state information
		$app = JFactory::getApplication();
		$input = JFactory::getApplication()->input;

		$colToshow = $input->get('colToshow', array());

		if (empty($colToshow))
		{
			$colToshow = (array) $this->headerColumns();
		}

		$this->setState('colToshow', $colToshow);

		$filters    = $input->get('filters', array(), 'ARRAY');
		$this->setState('filters', $filters);

		// List state information
		$value = $input->get('limit', $app->get('list_limit', 0), 'uint');
		$this->setState('list.limit', $value);

		$value = $input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);

		// Ordering
		$this->default_order = $input->get('filter_order', $this->default_order);

		// If last sorted by column is hidden sort by first visible column
		if (!in_array($this->default_order, $colToshow))
		{
			$this->default_order = reset($colToshow);
		}

		$this->setState('list.ordering', $this->default_order);

		// Ordering Direction
		$this->default_order_dir = $input->get('filter_order_Dir', $this->default_order_dir);

		if (!in_array(strtoupper($this->default_order_dir), array('ASC', 'DESC', '')))
		{
			$this->default_order_dir = 'ASC';
		}

		$this->setState('list.direction', $this->default_order_dir);
	}

	/**
	 * Add all classes that are used
	 *
	 * @param   MIX  &$query  Query object
	 *
	 * @return  object
	 *
	 * @since    1.0
	 */
	protected function addAdditionalWhere(&$query)
	{
		$db  			= JFactory::getDBO();
		$filters 		= (array) $this->getState('filters');
		$displayFilters = (array) $this->displayFilters();
		$colToshow		= (array) $this->getState('colToshow');
		$filterLevel    = count($displayFilters);

		if ($filterLevel == 2)
		{
			$topLevelFilters = array_keys($displayFilters[1]);
			$colToshow       = array_merge($colToshow, $topLevelFilters);
		}

		// Loop through different levels of filters
		foreach ($displayFilters as $key => $displayFilter)
		{
			foreach ($displayFilter as $key => $dispFilter)
			{
				// Check if any of the filter is set
				if (!empty($filters[$key]) && in_array($key, $colToshow))
				{
					if (!isset($dispFilter['searchin']))
					{
						continue;
					}

					$columnName = $dispFilter['searchin'];

					if (isset($dispFilter['type']))
					{
						if ($dispFilter['type'] == 'custom')
						{
							$query->where(sprintf($dispFilter['searchin'], $db->quote($filters[$key])));
						}
						else
						{
							$query->where($db->quoteName($columnName) . '=' . $db->quote($filters[$key]));
						}
					}
					else
					{
						$search = $db->Quote('%' . $db->escape($filters[$key], true) . '%');
						$query->where($db->quoteName($columnName) . ' LIKE (' . $search . ')');
					}
				}
			}
		}
	}

	/**
	 * Sort Columns which are not possible by Sql Order by
	 *
	 * @param   MIX  $items  Items want to sort
	 *
	 * @return  MIX  Sorted columns
	 *
	 * @since    2.0
	 */
	protected function sortCustomColumns($items)
	{
		$totalRows = $this->getTotal();

		// Add the list ordering clause.
		$sortKey    = $this->getState('list.ordering', $this->default_order);
		$limit      = $this->getState('list.limit', 0);
		$limitstart = $this->getState('list.limitstart', 0);

		// Apply sorting and Limit if sorted column is not table
		if (!empty($items) && !empty($sortKey)
			&& in_array($sortKey, $this->sortableWoQuery) && $limit)
		{
			$orderDir   = $this->getState('list.direction', $this->default_order_dir);
			$this->multi_d_sort($items, $sortKey, $orderDir);

			$limitstart = isset($limitstart) ? (int) $limitstart : 0;
			$limitstart = (($limitstart * $limit) < $totalRows) ? $limitstart : 0;

			$this->setState('list.limitstart', $limitstart);

			$items = array_splice($items, $limitstart, $limit);
		}

		return $items;
	}

	/**
	 * Converts second to H:M:S format
	 *
	 * @param   STRING  $seconds    Total numbers of seconds
	 * @param   STRING  $separator  Seperator
	 *
	 * @return  STRING
	 *
	 * @since    1.0
	 */
	protected function formatTime($seconds, $separator=':')
	{
		return sprintf("%02d%s%02d%s%02d", floor($seconds / 3600), $separator, ($seconds / 60) % 60, $separator, $seconds % 60);
	}

	/**
	 * SOrt given array with the provided column and provided order
	 *
	 * @param   ARRAY   &$array  array of data
	 * @param   STRING  $column  column name
	 * @param   STRING  $order   order in which array has to be sort
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0
	 */
	private function multi_d_sort(&$array, $column, $order)
	{
		$order = ($order == 'desc') ? SORT_DESC : SORT_ASC;
		array_multisort(array_column($array, $column), $order, $array);
	}

	/**
	 * Get all saved queries
	 *
	 * @param   INT  $user_id        user id
	 *
	 * @param   INT  $reportToBuild  report to build
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getSavedQueries($user_id, $reportToBuild)
	{
		if (!empty($reportToBuild) &&  !empty($user_id))
		{
			$db        = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from('#__tj_reports');
			$query->where('plugin =' . $db->quote($reportToBuild));
			$query->where('userid = ' . $user_id);
			$query->where("`default` = " . 0);

			$db->setQuery($query);
			$savedQueries = $db->loadObjectList();

			return $savedQueries;
		}
	}

	/**
	 * Get all columns names
	 *
	 * @param   INT  $queryId  Query ID
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getQueryData($queryId)
	{
		$ol_user = JFactory::getUser()->id;
		$query   = $this->_db->getQuery(true);
		$query->select('*');
		$query->from('#__tj_reports');
		$query->where('userid=' . (int) $ol_user);
		$query->where('id=' . (int) $queryId);

		$this->_db->setQuery($query);
		$queryData = $this->_db->loadObject();

		return $queryData;
	}

	/**
	 * Get all plugins names
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getenableReportPlugins()
	{
		$db = JFactory::getDBO();
		$condtion = array(0 => '\'tjreports\'');
		$condtionatype = join(',', $condtion);
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('extension_id','name','element','enabled'), array('id',null,null,'published')));
		$query->from($db->quoteName('#__extensions'));
		$query->where("folder in (" . $condtionatype . ") AND enabled=1");
		$db->setQuery($query);
		$reportPlugins = $db->loadobjectList();

		return $reportPlugins;
	}

	/**
	 * Function to get the user filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getUserFilter()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('u.id,u.username');
		$query->from('#__users as u');

		// $query->join('LEFT', '#__users as u ON lt.user_id=u.id');
		$db->setQuery($query);
		$users = $db->loadObjectList();

		$userFilter[] = JHTML::_('select.option', '', JText::_('COM_TJREPORTS_FILTER_SELECT_USER'));

		if (!empty($users))
		{
			foreach ($users as $eachUser)
			{
				$userFilter[] = JHTML::_('select.option', $eachUser->id, $eachUser->username);
			}
		}

		return $userFilter;
	}

	/**
	 * Function to get all reports from tjreport
	 *
	 * @return  objectList
	 *
	 * @since 1.0.0
	 */
	public function getreportoptions()
	{
		// Initialize variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$user_id = JFactory::getUser()->id;

		$clients = $this->getState('client');

		// Check for the client
		if ($clients)
		{
			$clients = explode(",", $clients);
			$clients = "'" . implode("','", $clients) . "'";
		}

		$query->select('r.title as text, r.id as value');
		$query->from('#__tj_reports as r');
		$query->join('INNER', '`#__extensions` as ex ON ex.element = r.plugin');
		$query->where('ex.type=' . $db->quote("plugin"));
		$query->where('ex.folder=' . $db->quote("tjreports"));
		$query->where('ex.enabled= 1');
		$query->where('(r.parent = 0  or r.userid = ' . $user_id . ')');
		$query->where('r.id not in ( select `parent` from #__tj_reports as tr where tr.userid=' . $user_id . ' and tr.`default`=1)');

		if (!empty($clients))
		{
			$query->where('r.client in (' . $clients . ')');
		}

		$query->where('r.`default` = 1');

		$db->setQuery($query);
		$reports = $db->loadObjectList();

		$options[] = JHTML::_('select.option', 0, JText::_('COM_TJREPORTS_SELONE_REPORTS'));

		foreach ($reports as $repo)
		{
			$options[] = JHtml::_('select.option', $repo->value, $repo->text);
		}

		return $options;
	}

	/**
	 * Check for permissions
	 *
	 * @param   INT  $reportId  report id
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */

	public function checkpermissions($reportId)
	{
		$user       = JFactory::getUser();

		if ($reportId)
		{
			$allow = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);

			return $allow;
		}
	}

	/**
	 * Function to get all usergroups
	 *
	 * @return  objectList
	 *
	 * @since 1.0.0
	 */
	public function getUserGroupFilter()
	{
		// Initialize variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('r.title as text, r.id as value');
		$query->from('#__usergroups as r');
		$db->setQuery($query);
		$reports = $db->loadObjectList();

		$options[] = JHTML::_('select.option', '', JText::_('COM_TJREPORTS_FILTER_SELECT_USERGROUP'));

		foreach ($reports as $repo)
		{
			$options[] = JHtml::_('select.option', $repo->value, $repo->text);
		}

		return $options;
	}

	/**
	 * Get datadenyset result
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function datadenyset()
	{
		$input = JFactory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');
		$user_id = JFactory::getUser()->id;
		$reportId = $input->get('reportId', '', 'int');

		if ($reportName && $user_id && $reportId)
		{
			$db        = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select(array('param'));
			$query->from($db->quoteName('#__tj_reports'));
			$query->where('plugin =' . $db->quote($reportName));
			$query->where($db->quoteName('id') . ' = ' . $db->quote($reportId));
			$query->where($db->quoteName('userid') . ' = ' . $db->quote($user_id));
			$query->where($db->quoteName('datadenyset') . ' = ' . $db->quote('1'));

			$db->setQuery($query);
			$denyDataSet = $db->loadAssoc();

			$savedcols = json_decode($denyDataSet['param']);

			return $savedcols;
		}
	}

	/**
	 * Method to load language of TjReport Plugin
	 *
	 * @param   string  $name       Plugin Name
	 * @param   string  $type       Plugin Type
	 * @param   string  $extension  Extension Name
	 * @param   string  $basePath   The basepath to use
	 *
	 * @return  Void
	 *
	 * @since   3.0
	 */
	public function loadLanguage($name, $type = 'tjreports', $extension = '', $basePath = JPATH_ADMINISTRATOR)
	{
		if (empty($extension))
		{
			$extension = 'Plg_' . $type . '_' . $name;
		}

		$extension = strtolower($extension);
		$lang      = JFactory::getLanguage();

		// If language already loaded, don't load it again.
		if ($lang->getPaths($extension))
		{
			return true;
		}

		return $lang->load($extension, $basePath, null, false, true)
			|| $lang->load($extension, JPATH_PLUGINS . '/' . $type . '/' . $name, null, false, true);
	}
}
