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
	protected function populateState($ordering = 'ordering', $direction = 'ASC')
	{
		$input = JFactory::getApplication();

		// List state information

		$input = JFactory::getApplication()->input;

		$value = $input->get('client', "", "STRING");
		$this->setState('client', $value);

		$value = $input->get('allow_permission', '', 'INT');
		$this->setState('allow_permission', $value);

		$value = $input->get('reportId', '', 'INT');
		$this->setState('reportId', $value);

		$value = $input->get('savedQuery', '0', 'INT');
		$this->setState('savedQuery', $value);

		$value = $input->get('queryId', '0', 'INT');
		$this->setState('queryId', $value);

		$value = $input->get('reportToBuild', '', 'STRING');
		$this->setState('reportToBuild', $value);

		$value = $input->get('extension', "", "STRING");
		$this->setState('extension', $value);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   ARRAY   $filters      The Filters which are used
	 * @param   ARRAY   $colNames     The columns which need to show
	 * @param   int     $rowsTofetch  Total number of rows to fetch
	 * @param   int     $limit_start  Fetch record fron nth row
	 * @param   STRING  $sortCol      The column which has to be sorted
	 * @param   STRING  $sortOrder    The order of sorting
	 * @param   STRING  $action       Which action has cal this function
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function getData($filters = array(), $colNames = array(), $rowsTofetch = 20, $limit_start = 0,  $sortCol = '', $sortOrder = '', $action = '')
	{
		$input = JFactory::getApplication()->input;
		$post  = $input->post;
		$mainframe  = JFactory::getApplication();
		$user       = JFactory::getUser();

		// This takes post value from post request of ajax call
		$allow_permission    = $post->get('allow_permission', '', 'INT');
		$reportId    = $post->get('reportId', '', 'INT');

		if (!$allow_permission)
		{
			// If allow_permission not yet set take value from function get input from view.html
			$allow_permission = $this->getState('allow_permission');
		}

		if (!$reportId)
		{
			// If reportId not yet set take value from function get input from view.html
			$reportId = $this->getState('reportId');
		}

		// If reportname is not in url or input get it from state
		$reportName = $this->getState('reportToBuild');

		if (empty($reportName))
		{
			$reportName = $mainframe->getUserState('com_tjreports' . '.reportName', '');
		}

		$isSaveQuery = $this->getState('savedQuery');

		if ($isSaveQuery == 1)
		{
			// Get saved data
			$queryId = $this->getState('queryId');

			if ($queryId != 0)
			{
				$queryData = $this->getQueryData($queryId);

				if (!empty($queryData))
				{
					$reportName = $queryData->plugin;

					$param = json_decode($queryData->param);

					$colNames = (array) $param->colToshow;
					$filters = $param->filters;
					$filters = (array) $filters;
					$sort = $param->sort;

					$sortCol = '';
					$sortOrder = '';

					if (!empty($sort))
					{
						$sortCol = $sort[0];
						$sortOrder = $sort[1];
					}
				}
			}
		}

		if (empty($colNames))
		{
			$colNames = $this->getColNames();
		}

		$this->setAllUserPreference($reportName, $sortCol, $sortOrder, $colNames, $filters);

		// Get all fields
		$colNames      = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_colNames', '');
		$filters       = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_filters', '');
		$sortCol       = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_sortCol', '');
		$sortOrder     = $mainframe->getUserState('com_tjreports' . '.' . $reportName . '_table_sortOrder', '');

		$created_by = 0;

		if (!empty($reportId))
		{
			if (!$allow_permission)
			{
				$created_by = $user->id;
			}
		}

		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('tjreports');
		$data = $dispatcher->trigger('plg' . $reportName . 'RenderPluginHTML', array
																					(
																						$filters, $colNames, $rowsTofetch, $limit_start, $sortCol, $sortOrder, $action, $created_by, ''
																					)
									);

		if (isset($data[0]) && !empty($data[0]))
		{
			return $data[0];
		}

		return false;
	}

	/**
	 * Save user preferences
	 *
	 * @param   STRING  $reportName  The name of the report
	 * @param   STRING  $sortCol     The column which has to be sorted
	 * @param   STRING  $sortOrder   The order of sorting
	 * @param   ARRAY   $colNames    The columns which need to show
	 * @param   ARRAY   $filters     The Filters which are used
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function setAllUserPreference($reportName, $sortCol, $sortOrder, $colNames, $filters)
	{
		$mainframe = JFactory::getApplication();

		$mainframe->setUserState('com_tjreports' . '.reportName', $reportName);
		$mainframe->setUserState('com_tjreports' . '.' . $reportName . '_table_colNames', $colNames);
		$mainframe->setUserState('com_tjreports' . '.' . $reportName . '_table_filters', $filters);

		if (!empty($sortCol) && !empty($sortOrder))
		{
			$mainframe->setUserState('com_tjreports' . '.' . $reportName . '_table_sortCol', $sortCol);
			$mainframe->setUserState('com_tjreports' . '.' . $reportName . '_table_sortOrder', $sortOrder);
		}
	}

	/**
	 * Get all columns names
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getColNames()
	{
		$reportName = $this->getState('reportToBuild');

		if (empty($reportName))
		{
			$mainframe  = JFactory::getApplication();
			$reportName = $mainframe->getUserState('com_tjreports' . '.reportName', '');
		}

		// Get all column name which plugin provides / superset

		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('tjreports');
		$plugcolNames = $dispatcher->trigger('plg' . $reportName . 'getColNames', array());

		// Get all column name for default report
		$configcolNames = (array) $this->getconfigColNames();

		$configcolNames['colToshow'] = isset($configcolNames['colToshow']) ? $configcolNames['colToshow'] : '';

		$confirgcols = (array) ($configcolNames['colToshow']);

		// Get all column name which plugin provides / superset
		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('tjreports');
		$plugcolNames = $dispatcher->trigger('plg' . $reportName . 'getColNames', array());

		if (isset($plugcolNames[0]))
		{
			$colNames  = array_intersect($plugcolNames[0], $confirgcols);
		}

		$denyCol = (array) $this->datadenyset();

		if (!empty($colNames))
		{
			if (!empty($denyCol))
			{
				foreach ($colNames as $key => $value)
				{
					$colName[$value] = $value;
				}

				return $colName;
			}
			else
			{
				foreach ($plugcolNames as $plgcol)
				{
					foreach ($plgcol as $key => $value)
					{
						$colName[$value] = $value;
					}
				}

				return $colName;
			}
		}
		elseif (empty($colNames))
		{
			if ($plugcolNames)
			{
				foreach ($plugcolNames as $plgcol)
				{
					foreach ($plgcol as $key => $value)
					{
						$colName[$value] = $value;
					}
				}

				return $colName;
			}
			else
			{
				return $confirgcols;
			}
		}

		return false;
	}

	/**
	 * Get configurated column names
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function getconfigColNames()
	{
		$reportName = $this->getState('reportToBuild');
		$user_id = JFactory::getUser()->id;

		if (empty($reportName))
		{
			$mainframe  = JFactory::getApplication();
			$reportName = $mainframe->getUserState('com_tjreports' . '.reportName', '');
		}

		if (!empty($reportName))
		{
			$db        = JFactory::getDBO();
			$query = $db->getQuery(true);
			$savedcols = "";

			// Check is this uses has default config for this report
			$query->select('tj.param');
			$query->from('#__tj_reports tj');
			$query->where('tj.plugin =' . $db->quote($reportName));
			$query->where('tj.userid = ' . $user_id);
			$query->where("tj.`default` = " . 1);
			$db->setQuery($query);
			$savedcols = $db->loadAssoc();

			if (empty($savedcols))
			{
				// Get parent config is default is not set

				$query = $db->getQuery(true);

				$query->select('tjr.param');
				$query->from('#__tj_reports as tjr');
				$query->where('tjr.plugin =' . $db->quote($reportName));
				$query->where("tjr.`default` = " . 1);
				$query->where("tjr.`userid` = " . 0);
				$query->where("tjr.`parent` = " . 0);
				$db->setQuery($query);
				$savedcols = $db->loadAssoc();

				$savedcols = json_decode($savedcols['param']);

				return $savedcols;
			}

			$savedcols = json_decode($savedcols['param']);

			return $savedcols;
		}

		return false;
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
		$db        = JFactory::getDBO();
		$query = $db->getQuery(true);
/*
		if (!empty($colToSelect))
		{
			$colToSelect = implode(',', $colToSelect);
			$query->select($colToSelect);
		}
		else
		{
*/
			$query->select('*');
	/*
		}
*/
		$query->from('#__tj_reports');
		$query->where('userid=' . $ol_user);
		$query->where('id=' . $queryId);

		$db->setQuery($query);
		$queryData = $db->loadObject();

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

		$input = JFactory::getApplication()->input;
		$clients = $input->get('client', "", "STRING");

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
}
