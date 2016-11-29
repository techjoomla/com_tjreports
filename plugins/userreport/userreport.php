<?php
/**
 * @package    Shika
 * @author     TechJoomla | <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.folder');
jimport('joomla.plugin.plugin');
jimport('joomla.application.application');

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjreportsUserreport extends JPlugin
{
	/**
	 * Plugin that supports creating the tjlms dashboard
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void.
	 *
	 * @since 1.0.0
	 */

	public function PlgTjreportsUserreport(&$subject, $config)
	{
		$lang         = JFactory::getLanguage();
		$extension    = 'plg_tjreports_userreport';
		$base_dir     = JPATH_ADMINISTRATOR;
		$language_tag = 'en-GB';
		$reload       = true;
		$lang->load($extension, $base_dir, $language_tag, $reload);
		parent::__construct($subject, $config);
	}

	/**
	 * Function to get data of the whole block
	 *
	 * @param   ARRAY   $filters        The Filters which are used
	 * @param   ARRAY   $colNames       The columns which need to show
	 * @param   int     $rows_to_fetch  Total number of rows to fetch
	 * @param   int     $limit_start    Fetch record fron nth row
	 * @param   STRING  $sortCol        The column which has to be sorted
	 * @param   STRING  $sortOrder      The order of sorting
	 * @param   int     $created_by     Which user has permission to report
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function plguserreportGetData($filters, $colNames, $rows_to_fetch, $limit_start, $sortCol, $sortOrder, $created_by)
	{
		$db        = JFactory::getDBO();
		$mainframe = JFactory::getApplication('admin');

		// Get all Columns
		$allColumns = $this->plguserreportgetColNames();

		// Get only those columns which user has to show
		$colToshow  = array_intersect($allColumns, $colNames);

		// Columns which has search fields
		$showSearchToCol = $this->showSearchToCol();

		$query = $db->getQuery(true);

		// Get only selected data as per columns to show
		foreach ($colToshow as $eachCol)
		{
			switch ($eachCol)
			{
				case 'id':
					$query->select('DISTINCT(eu.user_id) as id');
					break;
				case 'name':
					$query->select('u.name');
					break;
				case 'username':
					$query->select('u.username');
					break;
				case 'email':
					$query->select('u.email');
					break;
				case 'block':
					$query->select('u.block');
					break;
			}
		}

		$query->from('`#__tjlms_enrolled_users` AS eu');

		 $query->join('INNER', '`#__tjlms_courses` as c ON c.id = eu.course_id');

		// Check for permission

		if ($created_by)
		{
			$query->where('c.created_by=' . $created_by);
		}


		$query->join('INNER', '`#__users` as u ON eu.user_id = u.id');

		// If we have filter value then add respected where conditions
		if (!empty($filters))
		{
			// Do filter related thing
			if (array_key_exists('id', $filters))
			{
				if (!empty($filters['id']) && $filters['id'] !== "''")
				{
					$query->where('u.id=' . $filters['id']);
				}
			}

			// Do filter related thing
			if (array_key_exists('name', $filters))
			{
				$searchName = $db->Quote('%' . $db->escape($filters['name'], true) . '%');
				$query->where('u.name LIKE' . $searchName);
			}

			if (array_key_exists('username', $filters))
			{
				$searchUserName = $db->Quote('%' . $db->escape($filters['username'], true) . '%');
				$query->where('u.username LIKE ' . $searchUserName);
			}

			// Do filter related thing
			if (array_key_exists('email', $filters))
			{
				$searchEmail = $db->Quote('%' . $db->escape($filters['email'], true) . '%');
				$query->where('u.email LIKE ' . $searchEmail);
			}

			if (array_key_exists('user_group', $filters))
			{
				$searchGroup = $db->Quote('%' . $db->escape($filters['user_group'], true) . '%');
				$query->join('left', '`#__user_usergroup_map` as uum ON u.id = uum.user_id');
				$query->join('left', '`#__usergroups` as ug ON uum.group_id= ug.id');
				$query->where('ug.title LIKE ' . $searchGroup);
			}
		}

		// Apply sorting is applicable
		if ((!empty($sortCol) && !empty($sortOrder)) && in_array($sortCol, $showSearchToCol) && in_array($sortCol, $colToshow))
		{
			$query->order($sortCol . ' ' . $sortOrder);
		}

		$db->setQuery($query);

		$total_rows = $db->query();

		// Get total number of rows
		$total_rows = $db->getNumRows();

		// If we want all rows don't apply limit
		if ($rows_to_fetch != 'all')
		{
			$query->setlimit($rows_to_fetch, $limit_start);
		}

		$db->setQuery($query);
		$items = $db->loadObjectList();

		if (!empty($items))
		{
			foreach ($items as $ind => $User)
			{
				if (in_array('block', $colToshow))
				{
					$items[$ind]->block = JText::_('JYES');

					if ($User->block == 1)
					{
						$items[$ind]->block = JText::_('JNO');
					}
				}

				if (in_array('user_group', $colToshow))
				{
					$items[$ind]->user_group = $this->getGroups($User->id);
				}

				// Get Enrollment Data
				$db = JFactory::getDBO();

				$query = $db->getQuery(true);

				$getEnrollerdCourses = 0;
				$getPendingCourses   = 0;

				if (in_array('enrolled_courses', $colToshow) || in_array('inCompletedCourses', $colToshow))
				{
					$getEnrollerdCourses = 1;
					$query->select('COUNT(IF(eu.state="1",1, NULL)) as enrolled_courses');
				}

				if (in_array('pending_enrollment', $colToshow))
				{
					$getPendingCourses = 1;
					$query->select('COUNT(IF(eu.state="0",1, NULL)) as pending_enrollment');
				}

				if (in_array('enrolled_courses', $colToshow) || in_array('inCompletedCourses', $colToshow) || in_array('pending_enrollment', $colToshow))
				{
					$query->from('#__tjlms_enrolled_users as eu');
					$query->join('RIGHT', '#__tjlms_courses as c ON c.id = eu.course_id');

					// Check for permission

					if ($created_by)
					{
						$query->where('c.created_by=' . $created_by);
					}

					$query->where('c.state=1');
					$query->where('eu.user_id=' . $User->id);
					$db->setQuery($query);
					$EnrollmentData = $db->loadAssoc();
				}

				if ($getEnrollerdCourses == 1)
				{
					$enrolled_courses = $EnrollmentData['enrolled_courses'];
				}

				if ($getPendingCourses == 1)
				{
					$pending_enrollment = $EnrollmentData['pending_enrollment'];
				}

				if (in_array('enrolled_courses', $colToshow))
				{
					$items[$ind]->enrolled_courses = $EnrollmentData['enrolled_courses'];
				}

				if (in_array('pending_enrollment', $colToshow))
				{
					$items[$ind]->pending_enrollment = $EnrollmentData['pending_enrollment'];
				}

				// Get count of enrolled courses for the user
				$db = JFactory::getDbo();

				// Create a new query object.
				$query = $db->getQuery(true);

				// Select all records from the user profile table where key begins with "custom.".
				// Order it by the ordering field.

				if (in_array('totalCompletedCourses', $colToshow) || in_array('inCompletedCourses', $colToshow))
				{
					$query->select('COUNT(ct.id) as totalCompletedCourses');
					$query->from($db->quoteName('#__tjlms_course_track') . ' as ct');
					$query->join('INNER', $db->quoteName('#__tjlms_courses') . ' as c ON c.id=ct.course_id');
					$query->where($db->quoteName('ct.user_id') . ' = ' . $User->id);
					$query->where($db->quoteName('ct.status') . ' = "C"');
					$query->where($db->quoteName('c.state') . ' = 1');

					// Check for permission

					if ($created_by)
					{
						$query->where('c.created_by=' . $created_by);
					}

					// Reset the query using our newly populated query object.
					$db->setQuery($query);

					// Load the results as a list of stdClass objects (see later for more options on retrieving data).
					$totalCompletedCourses = $db->loadresult();
				}

				if (in_array('totalCompletedCourses', $colToshow))
				{
					$items[$ind]->totalCompletedCourses = $totalCompletedCourses;
				}

				if (in_array('inCompletedCourses', $colToshow))
				{
					$items[$ind]->inCompletedCourses = $enrolled_courses - $totalCompletedCourses;
				}

				if (in_array('timeSpentOnLesson', $colToshow))
				{
					$tjlmsTrackingHelperObj = new ComtjlmstrackingHelper;
					$totalTimeSpent = $tjlmsTrackingHelperObj->getTotalTimeSpent($User->id);
					$items[$ind]->timeSpentOnLesson = $totalTimeSpent;

					if (empty($items[$ind]->timeSpentOnLesson) || $items[$ind]->timeSpentOnLesson == '00:00:00')
					{
						$items[$ind]->timeSpentOnLesson = '-';
					}
				}
			}
		}

		// Apply sorting.
		if ((!empty($sortCol) && !empty($sortOrder)) && !in_array($sortCol, $showSearchToCol) && in_array($sortCol, $colToshow))
		{
			$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

			if (!class_exists('ComtjlmsHelper'))
			{
				JLoader::register('ComtjlmsHelper', $path);
				JLoader::load('ComtjlmsHelper');
			}

			$ComtjlmsHelper = new ComtjlmsHelper;
			$items          = $ComtjlmsHelper->multi_d_sort($items, $sortCol, $sortOrder);
		}

		$result               = array();
		$result['total_rows'] = $total_rows;
		$result['items']      = $items;
		$result['colToshow']  = $colToshow;

		return $result;
	}

	/**
	 * Build html for report
	 *
	 * @param   ARRAY   $filters        The Filters which are used
	 * @param   ARRAY   $colNames       The columns which need to show
	 * @param   int     $rows_to_fetch  Total number of rows to fetch
	 * @param   int     $limit_start    Fetch record fron nth row
	 * @param   STRING  $sortCol        The column which has to be sorted
	 * @param   STRING  $sortOrder      The order of sorting
	 * @param   STRING  $action         Which action has cal this function
	 * @param   int     $created_by     Which user has permission to report
	 * @param   STRING  $layout         Which layout to show
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function plguserreportRenderPluginHTML($filters, $colNames, $rows_to_fetch, $limit_start, $sortCol, $sortOrder, $action, $created_by, $layout = 'default')
	{
		// Get data
		$resultData = $this->plguserreportGetData($filters, $colNames, $rows_to_fetch, $limit_start, $sortCol, $sortOrder, $created_by);

		// Get Items
		$userReportData = $resultData['items'];

		// Coulmns to show
		$colToshow       = $resultData['colToshow'];

		// Columns which are used for search fields
		$showSearchToCol = $this->showSearchToCol();

		$html            = '';

		// Load the layout & push variables
		ob_start();
		$layout = $this->buildLayoutPath($layout);
		include $layout;

		$html = ob_get_contents();
		ob_end_clean();

		$result               = array();
		$result['total_rows'] = $resultData['total_rows'];
		$result['html']       = $html;

		return $result;
	}

	/**
	 * Function to get the layout for the block
	 *
	 * @param   ARRAY  $layout  Layout to be used
	 *
	 * @return  File path
	 *
	 * @since 1.0.0
	 */
	protected function buildLayoutPath($layout)
	{
		if (empty($layout))
		{
			$layout = "default";
		}

		$app       = JFactory::getApplication();
		$core_file = dirname(__FILE__) . '/' . $this->_name . '/' . 'tmpl' . '/' . $layout . '.php';
		$override  = JPATH_BASE . '/templates/' . $app->getTemplate() . '/html/plugins/' . $this->_type . '/' . $this->_name . '/' . $layout . '.php';

		if (JFile::exists($override))
		{
			return $override;
		}
		else
		{
			return $core_file;
		}
	}

	/**
	 * To get User Groups
	 *
	 * @param   int  $user_id  The user ID
	 *
	 * @return  string  $groups_str
	 *
	 * @since  1.0.0
	 */
	public function getGroups($user_id)
	{
		$db     = JFactory::getDBO();
		$groups = array();
		$query  = "SELECT ug.title FROM #__usergroups as ug, #__user_usergroup_map as uum where uum.group_id= ug.id and user_id=" . $user_id;
		$db->setQuery($query);
		$groups     = $db->loadColumn();
		$groups_str = '';

		for ($i = 0; $i < count($groups); $i++)
		{
			$groups_str .= $groups[$i] . '<br />';
		}

		return $groups_str;
	}

	/**
	 * Get all columns names
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function plguserreportgetColNames()
	{
		$ColArray = array(
			'PLG_TJREPORTS_USERREPORT_ID' => 'id',
			'PLG_TJREPORTS_USERREPORT_NAME' => 'name',
			'PLG_TJREPORTS_USERREPORT_USERNAME' => 'username',
			'PLG_TJREPORTS_USERREPORT_EMAIL' => 'email',
			'PLG_TJREPORTS_USERREPORT_BLOCK' => 'block',
			'PLG_TJREPORTS_USERREPORT_USER_GROUP' => 'user_group'
		);

		$ColArray['PLG_TJREPORTS_USERREPORT_ENROLLED_COURSES'] = 'enrolled_courses';
		$ColArray['PLG_TJREPORTS_USERREPORT_PENDING_ENROLLMENT'] = 'pending_enrollment';
		$ColArray['PLG_TJREPORTS_USERREPORT_TOTALCOMPLETEDCOURSES'] = 'totalCompletedCourses';
		$ColArray['PLG_TJREPORTS_USERREPORT_INCOMPLETEDCOURSES'] = 'inCompletedCourses';
		$ColArray['PLG_TJREPORTS_USERREPORT_TIMESPENTONLESSON'] = 'timeSpentOnLesson';

		return $ColArray;
	}

	/**
	 * Get all columns names which should have a search filter
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function showSearchToCol()
	{
		$filterArray = array(
			'id',
			'name',
			'username',
			'email'
		);

		return $filterArray;
	}
}
