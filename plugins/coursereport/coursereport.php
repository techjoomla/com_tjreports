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

class PlgTjreportsCoursereport extends JPlugin
{
	/**
	 * Plugin that supports creating the tj dashboard
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void.
	 *
	 * @since 1.0.0
	 */
	public function PlgTjreportsCoursereport(&$subject, $config)
	{
		$lang         = JFactory::getLanguage();
		$extension    = 'plg_tjreports_coursereport';
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
	public function plgcoursereportGetData($filters, $colNames, $rows_to_fetch, $limit_start, $sortCol, $sortOrder, $created_by)
	{
		$db        = JFactory::getDBO();
		$mainframe = JFactory::getApplication('admin');
		$input = JFactory::getApplication()->input;

		// Get all Columns
		$allColumns = $this->plgcoursereportgetColNames();

		// Get only those columns which user has to show
		$colToshow = array_intersect($allColumns, $colNames);

		// Columns which has search fields
		$showSearchToCol = $this->showSearchToCol();

		$query = $db->getQuery(true);

		// Get only selected data as per columns to show
		foreach ($colToshow as $eachCol)
		{
			switch ($eachCol)
			{
				case 'id':
					$query->select('c.id');
					break;
				case 'title':
					$query->select(' c.title');
					break;
				case 'type':
					$query->select('c.type');
					break;
				case 'access':
					$query->select('c.access');
					break;
				case 'cat_title':
					$query->select('cat.title as cat_title');
					break;
			}
		}

		// Join over the user field 'created_by'
		$query->from('`#__tjlms_courses` AS c');
		$query->join('LEFT', '#__categories AS cat ON c.cat_id = cat.id');

		// Check for permission

		if ($created_by)
		{
			$query->where('c.created_by=' . $created_by);
		}

		// If we have filter value then add respected where conditions
		if (!empty($filters))
		{
			// Do filter related thing
			if (array_key_exists('id', $filters))
			{
				$query->where('c.id=' . $filters['id']);
			}

			// Do filter related thing
			if (array_key_exists('title', $filters))
			{
				$searchName = $db->Quote('%' . $db->escape($filters['title'], true) . '%');
				$query->where('c.title LIKE' . $searchName);
			}

			/*if (array_key_exists('cat_title', $filters))
			{
				$cat_title = $db->Quote('%' . $db->escape($filters['cat_title'], true) . '%');
				$query->where('cat.title LIKE ' . $cat_title);
			}*/

			if (array_key_exists('cat_title', $filters))
			{
				// $cat_title = $db->Quote('%' . $db->escape($filters['cat_title'], true) . '%');
				$query->where('cat.id=' . $filters['cat_title']);
			}
		}

		// If the sorting is empty get the value from user state
		if (empty($sortCol) && empty($sortOrder))
		{
			$sortCol   = $mainframe->getUserState("COM_TJREPORTS.coursereport_table_sortCol", '');
			$sortOrder = $mainframe->getUserState("COM_TJREPORTS.coursereport_table_sortOrder", '');
		}

		// Apply sorting is applicable
		if ((!empty($sortCol) && !empty($sortOrder)) && in_array($sortCol, $showSearchToCol) && in_array($sortCol, $colToshow))
		{
			if ($sortCol == 'cat_title')
			{
				$sortColwithPrefic = 'cat.title';
			}
			else
			{
				$sortColwithPrefic = 'c.' . $sortCol;
			}

			$query->order($sortColwithPrefic . ' ' . $sortOrder);
		}

		$query->group($db->quoteName('c.id'));
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

		foreach ($items as $ind => $course)
		{
			if (in_array('lessons_cnt', $colToshow))
			{
				$query = $db->getQuery(true);
				$query->select('COUNT(l.id) as lessons_cnt');
				$query->from('#__tjlms_lessons as l');
				$query->where('l.state=1');
				$query->where('l.format<>""');
				$query->where('l.course_id=' . $course->id);
				$db->setQuery($query);
				$items[$ind]->lessons_cnt = $db->loadResult();
			}

			if (in_array('type', $colToshow))
			{
				$type = JText::_("COM_TJREPORTS_COURSES_TYPE_FILTER_FREE");

				if ($course->type == "1")
				{
					$type = JText::_("COM_TJREPORTS_COURSES_TYPE_FILTER_PAID");
				}

				$items[$ind]->type = $type;
			}

			if (in_array('access', $colToshow))
			{
				// Get access level titles.
				$query = $db->getQuery(true);
				$query->select('title');
				$query->from('#__viewlevels');
				$query->where("id='" . $course->access . "'");
				$db->setQuery($query);
				$items[$ind]->access = $db->loadResult();
			}

			$getEnrollerdCourses = 0;
			$getPendingCourses   = 0;
			$query               = $db->getQuery(true);

			if (in_array('enrolled_users', $colToshow))
			{
				$getEnrollerdCourses = 1;
				$query->select('COUNT(IF(eu.state="1",1, NULL)) as enrolled_users');
			}

			if (in_array('pending_enrollment', $colToshow))
			{
				$getPendingCourses = 1;
				$query->select('COUNT(IF(eu.state="0",1, NULL)) as pending_enrollment');
			}

			// Get Enroled student Data
			if (in_array('enrolled_users', $colToshow) || in_array('pending_enrollment', $colToshow))
			{
				$query->from('#__tjlms_enrolled_users as eu');
				$query->where('eu.course_id=' . $course->id);

				$db->setQuery($query);
				$enrollmentData = $db->loadObject();
			}

			if ($getEnrollerdCourses == 1)
			{
				$items[$ind]->enrolled_users = $enrollmentData->enrolled_users;
			}

			if ($getPendingCourses == 1)
			{
				$items[$ind]->pending_enrollment = $enrollmentData->pending_enrollment;
			}

			// Get number of users who has completed the course
			if (in_array('totalCompletedUsers', $colToshow))
			{
				$query = $db->getQuery(true);
				$query->select('COUNT(ct.id)');
				$query->from($db->quoteName('#__tjlms_course_track') . ' as ct');

				$query->where($db->quoteName('ct.status') . " = 'C'");
				$query->where('ct.course_id=' . $course->id);

				$db->setQuery($query);
				$items[$ind]->totalCompletedUsers = $db->loadresult();
			}

			// Get number of users who has liked/Disliked the course
			$query = $db->getQuery(true);

			if (in_array('likeCnt', $colToshow))
			{
				$query->select('jc.like_cnt');
			}

			if (in_array('dislikeCnt', $colToshow))
			{
				$query->select('jc.dislike_cnt');
			}

			if (in_array('dislikeCnt', $colToshow) || in_array('likeCnt', $colToshow))
			{
				$query->from($db->quoteName('#__jlike_content') . ' as jc');
				$query->join('LEFT', '#__jlike_annotations AS ja ON jc.id = ja.content_id');
				$query->where($db->quoteName('jc.element') . " = 'COM_TJREPORTS.course'");
				$query->where('jc.element_id=' . $course->id);

				$db->setQuery($query);
				$jlikeData = $db->loadObject();
			}

			if (in_array('likeCnt', $colToshow))
			{
				$items[$ind]->likeCnt = 0;

				if (!empty($jlikeData->like_cnt))
				{
					$items[$ind]->likeCnt = $jlikeData->like_cnt;
				}
			}

			if (in_array('dislikeCnt', $colToshow))
			{
				$items[$ind]->dislikeCnt = 0;

				if (!empty($jlikeData->dislike_cnt))
				{
					$items[$ind]->dislikeCnt = $jlikeData->dislike_cnt;
				}
			}

			if (in_array('commnetsCnt', $colToshow))
			{
				$query = $db->getQuery(true);
				$query->select('COUNT(ja.id) as comments_cnt');
				$query->from($db->quoteName('#__jlike_content') . ' as jc');
				$query->join('LEFT', '#__jlike_annotations AS ja ON jc.id = ja.content_id');
				$query->where($db->quoteName('jc.element') . " = 'COM_TJREPORTS.course'");
				$query->where('jc.element_id=' . $course->id);
				$query->where('ja.note=0');

				$db->setQuery($query);
				$commnetsCnt              = $db->loadresult();
				$items[$ind]->commnetsCnt = 0;

				if (!empty($commnetsCnt))
				{
					$items[$ind]->commnetsCnt = $commnetsCnt;
				}
			}

			// Get number of users to whom this course is recommneded or asssigned
			$query        = $db->getQuery(true);
			$getjlikeData = 0;

			if (in_array('recommendCnt', $colToshow))
			{
				$getjlikeData = 1;
				$query->select('COUNT(IF(jt.type="reco",1, NULL)) as recommend_cnt');
			}

			if (in_array('assignCnt', $colToshow))
			{
				$getjlikeData = 1;
				$query->select('COUNT(IF(jt.type="assign",1, NULL)) assign_cnt');
			}

			if ($getjlikeData == 1)
			{
				$query->from('#__jlike_todos as jt');

				$query->join('LEFT', '#__jlike_content AS jc ON jc.id = jt.content_id');
				$query->where('jc.element_id=' . $course->id);
				$query->where('jc.element=' . '"COM_TJREPORTS.course"');

				$db->setQuery($query);
				$jlikeData = $db->loadObject();
			}

			if (in_array('recommendCnt', $colToshow))
			{
				$items[$ind]->recommendCnt = 0;

				if (!empty($jlikeData->recommendCnt))
				{
					$items[$ind]->recommendCnt = $jlikeData->recommend_cnt;
				}
			}

			if (in_array('assignCnt', $colToshow))
			{
				$items[$ind]->assignCnt = 0;

				if (!empty($jlikeData->assign_cnt))
				{
					$items[$ind]->assignCnt = $jlikeData->assign_cnt;
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
	 * @param   ARRAY   $filters      The Filters which are used
	 * @param   ARRAY   $colNames     The columns which need to show
	 * @param   int     $rowsToFetch  Total number of rows to fetch
	 * @param   int     $limit_start  Fetch record fron nth row
	 * @param   STRING  $sortCol      The column which has to be sorted
	 * @param   STRING  $sortOrder    The order of sorting
	 * @param   STRING  $action       Which action has cal this function
	 * @param   STRING  $created_by   Which user has permission to report
	 * @param   STRING  $layout       Which layout to show
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function plgcoursereportRenderPluginHTML($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $action, $created_by, $layout = 'default')
	{
		// Get data
		$resultData = $this->plgcoursereportGetData($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $created_by);

		// Get Items
		$reportData = $resultData['items'];

		// Coulmns to show
		$colToshow = $resultData['colToshow'];

		// Columns which are used for search fields
		$showSearchToCol = $this->showSearchToCol();

		$path = JPATH_COMPONENT . '/models/' . 'reports.php';

		if (!class_exists('TjreportsModelReports'))
		{
			// Require_once $path;
			JLoader::register('TjreportsModelReports', $path);
			JLoader::load('TjreportsModelReports');
		}

		$TjreportsModelReports = new TjreportsModelReports;

		// Get filter
		if (in_array('cat_title', $colToshow))
		{
			$this->catFilter = $TjreportsModelReports->getCatFilter();
		}

		$html = '';

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
	 * Get all columns names
	 *
	 * @return    object
	 *
	 * @since    1.0
	 */
	public function plgcoursereportgetColNames()
	{
		$ColArray = array(
			'PLG_TJREPORTS_COURSEREPORT_ID' => 'id',
			'PLG_TJREPORTS_COURSEREPORT_TITLE' => 'title',
			'PLG_TJREPORTS_COURSEREPORT_TYPE' => 'type',
			'PLG_TJREPORTS_COURSEREPORT_ACCESS' => 'access',
			'PLG_TJREPORTS_COURSEREPORT_CAT_TITLE' => 'cat_title',
			'PLG_TJREPORTS_COURSEREPORT_LESSONS_CNT' => 'lessons_cnt',
			'PLG_TJREPORTS_COURSEREPORT_ENROLLED_USERS' => 'enrolled_users'
		);

		$ColArray['PLG_TJREPORTS_COURSEREPORT_PENDING_ENROLLMENT'] = 'pending_enrollment';
		$ColArray['PLG_TJREPORTS_COURSEREPORT_TOTALCOMPLETEDUSERS'] = 'totalCompletedUsers';
		$ColArray['PLG_TJREPORTS_COURSEREPORT_LIKECNT'] = 'likeCnt';
		$ColArray['PLG_TJREPORTS_COURSEREPORT_DISLIKECNT'] = 'dislikeCnt';
		$ColArray['PLG_TJREPORTS_COURSEREPORT_COMMNETSCNT'] = 'commnetsCnt';
		$ColArray['PLG_TJREPORTS_COURSEREPORT_RECOMMENDCNT'] = 'recommendCnt';
		$ColArray['PLG_TJREPORTS_COURSEREPORT_ASSIGNCNT'] = 'assignCnt';

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
			'title',
			'cat_title'
		);

		return $filterArray;
	}
}
