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
		$mainframe  = JFactory::getApplication();
		$user       = JFactory::getUser();

		$reportName = $input->get('reportToBuild', '', 'STRING');

		if (empty($reportName))
		{
			$reportName = $mainframe->getUserState('com_tjreports' . '.reportName', '');
		}

		$isSaveQuery = $input->get('savedQuery', '0', 'INT');

		if ($isSaveQuery == 1)
		{
			// Get saved data
			$queryId = $input->get('queryId', '0', 'INT');

			if ($queryId != 0)
			{
				$queryData = $this->getQueryData($queryId);

				if (!empty($queryData))
				{
					$reportName = $queryData->plugin;

					$param = json_decode($queryData->param);

					$colNames = $param->colToshow;
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

		$reportId = $input->get('reportId', '', 'INT');

		if (!$reportId)
		{
			$session = JFactory::getSession();
			$reportId = $session->get('reportId', '');
		}

		$created_by = 0;

		if (!empty($reportId))
		{
			$permission_viewall = $this->checkpermissions($reportId);

			if (!$permission_viewall)
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
		$input = JFactory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');

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

		$confirgcols = (array) ($configcolNames['colToshow']);

		$colNames  = array_intersect($plugcolNames[0], $confirgcols);

		if (!empty($colNames))
		{
			return $colNames;
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
		$input = JFactory::getApplication()->input;
		$reportName = $input->get('reportToBuild', '', 'STRING');
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

		$query = "SELECT extension_id as id,name,element,enabled as published FROM #__extensions WHERE folder in (" . $condtionatype . ") AND enabled=1";

		$db->setQuery($query);
		$reportPlugins = $db->loadobjectList();

		return $reportPlugins;
	}

	/**
	 * Function to get the course filter
	 *
	 * @param   INT  $created_by  created_by
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getCourseFilter($created_by)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('DISTINCT(id) as id,title');
		$query->from('#__tjlms_courses');

		if ($created_by)
		{
			$query->where('created_by=' . $created_by);
		}

		$db->setQuery($query);
		$courses = $db->loadObjectList();

		$courseFilter[] = JHTML::_('select.option', '', JText::_('COM_TJREPORTS_FILTER_SELECT_COURSE'));

		if (!empty($courses))
		{
			foreach ($courses as $eachCourse)
			{
				$courseFilter[] = JHTML::_('select.option', $eachCourse->id, $eachCourse->title);
			}
		}

		return $courseFilter;
	}

	/**
	 * Function to get the lesson filter
	 *
	 * @param   INT  $created_by  created_by
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getLessonFilter($created_by)
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('DISTINCT(l.id) as id,l.name');
		$query->from('#__tjlms_lessons as l');
		$query->join('INNER', '`#__tjlms_courses` as c ON c.id = l.course_id');

		// Check for permission

		if ($created_by)
		{
			$query->where('c.created_by=' . $created_by);
		}

		$db->setQuery($query);
		$lessons = $db->loadObjectList();

		$lessonFilter[] = JHTML::_('select.option', '', JText::_('COM_TJREPORTS_FILTER_SELECT_LESSON'));

		if (!empty($lessons))
		{
			foreach ($lessons as $eachLessons)
			{
				$lessonFilter[] = JHTML::_('select.option', $eachLessons->id, $eachLessons->name);
			}
		}

		return $lessonFilter;
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
	 * Function to get the category filter
	 *
	 * @return  object
	 *
	 * @since 1.0.0
	 */
	public function getCatFilter()
	{
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('id,title');
		$query->from('#__categories');
		$query->where('extension="com_tjlms"');
		$query->where('published=1');

		$db->setQuery($query);
		$cats = $db->loadObjectList();

		if (!empty($cats))
		{
			$catFilter[] = JHTML::_('select.option', '', JText::_('COM_TJREPORTS_FILTER_SELECT_COURSE_CATEGORY'));

			foreach ($cats as $eachCat)
			{
				$catFilter[] = JHTML::_('select.option', $eachCat->id, $eachCat->title);
			}
		}

		return $catFilter;
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

		// Need alternative code for this
		$clients = explode(",", $clients);
		$clients = "'" . implode("','", $clients) . "'";

		$query->select('r.title as text, r.id as value');
		$query->from('#__tj_reports as r');
		$query->where('(r.parent = 0  or r.userid = ' . $user_id . ')');
		$query->where('r.id not in ( select `parent` from #__tj_reports as tr where tr.userid=' . $user_id . ' and tr.`default`=1)');

		if (!empty($clients))
		{
			$query->where('r.client in (' . $clients . ')');
		}

		$query->where('r.`default` = 1');

		$db->setQuery($query);
		$reports = $db->loadObjectList();

		$options[] = JHTML::_('select.option', '', JText::_('COM_TJREPORTS_SELONE_REPORTS'));

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
}
