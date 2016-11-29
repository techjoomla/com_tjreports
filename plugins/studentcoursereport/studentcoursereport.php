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
jimport('techjoomla.common');

/**
 * Vimeo plugin from techjoomla
 *
 * @since  1.0.0
 */

class PlgTjreportsStudentcoursereport extends JPlugin
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
	public function PlgTjreportsStudentcoursereport(&$subject, $config)
	{
		$lang         = JFactory::getLanguage();
		$extension    = 'plg_tjreports_studentcoursereport';
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
	 * @param   STRING  $created_by     Which user has permission to report
	 *
	 * @return  data.
	 *
	 * @since 1.0.0
	 */
	public function plgstudentcoursereportGetData($filters, $colNames, $rows_to_fetch, $limit_start, $sortCol, $sortOrder,$created_by)
	{
		$lmsparams = JComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		$this->techjoomlacommon = new TechjoomlaCommon;
		$db                     = JFactory::getDBO();
		$mainframe              = JFactory::getApplication('admin');
		$input = JFactory::getApplication()->input;

		// Get all Columns
		$allColumns = $this->plgstudentcoursereportgetColNames();

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
					$query->select('c.title');
					break;
				case 'cat_title':
					$query->select('cat.title as cat_title');
					break;
				case 'access_level_title':
					$query->select('c.access');
					break;
				case 'user_id':
				case 'completion':
				case 'totaltimespent':
					$query->select('eu.user_id');
					break;
				case 'username':
					$query->select('u.username');
					break;
				case 'certificate_term':
					$query->select('c.certificate_term');
					break;
				case 'enrolled_on_time':
					$query->select('eu.enrolled_on_time');
					break;
				case 'end_time':
					$query->select('eu.end_time');
					break;
			}
		}

		$query->from('`#__tjlms_courses` as c');
		$query->join('INNER', '#__tjlms_enrolled_users as eu ON eu.course_id = c.id');
		$query->where('eu.state=1');

		// If user has permission
		if ($created_by)
		{
			$query->where('c.created_by=' . $created_by);
		}

		if (in_array('username', $colToshow))
		{
			$query->join('INNER', '`#__users` as u ON u.id = eu.user_id');
		}

		if (in_array('cat_title', $colToshow))
		{
			$query->join('LEFT', '#__categories AS cat ON c.cat_id = cat.id');
		}

		// If we have filter value then add respected where conditions
		if (!empty($filters))
		{
			// Do filter related thing
			if (array_key_exists('id', $filters))
			{
				$query->where('c.id=' . $filters['id']);
			}

			/*if (array_key_exists('title', $filters))
			{
				$searchName = $db->Quote('%' . $db->escape($filters['title'], true) . '%');
				$query->where('c.title LIKE' . $searchName);
			}*/

			if (array_key_exists('title', $filters))
			{
				$query->where('c.id=' . $filters['title']);
			}

			/*if (array_key_exists('cat_title', $filters))
			{
				$searchCatTitle = $db->Quote('%' . $db->escape($filters['cat_title'], true) . '%');
				$query->where('cat.title LIKE ' . $searchCatTitle);
			}*/

			if (array_key_exists('cat_title', $filters))
			{
				$searchCatTitle = $db->Quote('%' . $db->escape($filters['cat_title'], true) . '%');
				$query->where('cat.id=' . $filters['cat_title']);
			}

			/*if (array_key_exists('username', $filters))
			{
				$searchUserName = $db->Quote('%' . $db->escape($filters['username'], true) . '%');
				$query->where('u.username LIKE ' . $searchUserName);
			}*/

			if (array_key_exists('username', $filters))
			{
				$searchUserName = $db->Quote('%' . $db->escape($filters['username'], true) . '%');
				$query->where('u.id=' . $filters['username']);
			}

			if (array_key_exists('user_id', $filters))
			{
				$query->where('eu.user_id=' . $filters['user_id']);
			}
		}

		// If the sorting is empty get the value from user state
		if (empty($sortCol) && empty($sortOrder))
		{
			$sortCol   = $mainframe->getUserState("com_tjreports.studentcoursereport_table_sortCol", '');
			$sortOrder = $mainframe->getUserState("com_tjreports.studentcoursereport_table_sortOrder", '');
		}

		// Apply sorting is applicable
		if ((!empty($sortCol) && !empty($sortOrder)) && in_array($sortCol, $showSearchToCol) && in_array($sortCol, $colToshow))
		{
			switch ($sortCol)
			{
				case 'id':
					$sortColwithPrefic = 'c.id';
					break;
				case 'title':
					$sortColwithPrefic = 'c.title';
					break;
				case 'cat_title':
					$sortColwithPrefic = 'cat.title';
					break;
				case 'username':
					$sortColwithPrefic = 'u.username';
					break;
				case 'user_id':
					$sortColwithPrefic = 'eu.user_id';
					break;
			}

			$query->order($sortColwithPrefic . ' ' . $sortOrder);
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

		foreach ($items as $ind => $course)
		{
			if (in_array('enrolled_on_time', $colToshow))
			{
				$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

				if (!class_exists('ComtjlmsHelper'))
				{
					JLoader::register('ComtjlmsHelper', $path);
					JLoader::load('ComtjlmsHelper');
				}

				$ComtjlmsHelper = new ComtjlmsHelper;

				$course->enrolled_on_time = $this->techjoomlacommon->getDateInLocal($course->enrolled_on_time, 0, $date_format_show);
			}

			if (in_array('end_time', $colToshow))
			{
				$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

				if (!class_exists('ComtjlmsHelper'))
				{
					JLoader::register('ComtjlmsHelper', $path);
					JLoader::load('ComtjlmsHelper');
				}

				$ComtjlmsHelper = new ComtjlmsHelper;

				$course->end_time = $this->techjoomlacommon->getDateInLocal($course->end_time, 0, $date_format_show);

				if ($course->end_time == '0000-00-00 00:00:00')
				{
					$course->end_time = '-';
				}
			}

			if (in_array('certificate_term', $colToshow))
			{
				$cer_term = JText::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_NOCERTI");

				if ($course->certificate_term == "1")
				{
					$cer_term = JText::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_COMPALL");
				}
				elseif ($course->certificate_term == "2")
				{
					$cer_term = JText::_("COM_TJREPORTS_FORM_OPT_COURSE_CERTIFICATE_TERM_PASSALL");
				}

				$items[$ind]->certificate_term = $cer_term;
			}

			if (in_array('access_level_title', $colToshow))
			{
				// Get access level titles.
				$query = $db->getQuery(true);
				$query->select('title');
				$query->from('#__viewlevels');
				$query->where("id='" . $course->access . "'");
				$db->setQuery($query);
				$items[$ind]->access_level_title = $db->loadResult();
			}

			if (in_array('completion', $colToshow))
			{
				$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

				if (!class_exists('ComtjlmstrackingHelper'))
				{
					JLoader::register('ComtjlmstrackingHelper', $path);
					JLoader::load('ComtjlmstrackingHelper');
				}

				$ComtjlmstrackingHelper = new ComtjlmstrackingHelper;

				// Get %completion
				$progress = $ComtjlmstrackingHelper->getCourseTrackEntry($course->id, $course->user_id);
				$items[$ind]->completion = floor($progress['complitionPercent']);
			}

			if (in_array('totaltimespent', $colToshow))
			{
				// Get total time spent
				$query = $db->getQuery(true);
				$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as totalTimeSpent');
				$query->from('`#__tjlms_lesson_track` AS lt');
				$query->join('INNER', '`#__tjlms_lessons` as l ON lt.lesson_id = l.id');
				$query->where('lt.user_id = ' . $course->user_id);
				$query->where('l.course_id = ' . $course->id);
				$db->setQuery($query);
				$totaltimespent = $db->loadResult();

				$items[$ind]->totaltimespent = '-';

				if (!empty($totaltimespent) && $totaltimespent != '00:00:00')
				{
					$items[$ind]->totaltimespent = $totaltimespent;
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
	public function plgstudentcoursereportRenderPluginHTML($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $action, $created_by, $layout)
	{
		if (!isset($layout) && empty($layout))
		{
			$layout = 'default';
		}

		// Get data
		$resultData = $this->plgstudentcoursereportGetData($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $created_by);

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

		// Get course filter
		if (in_array('cat_title', $colToshow))
		{
			$this->catFilter = $TjreportsModelReports->getCatFilter();
		}

		if (in_array('username', $colToshow))
		{
			$this->userFilter = $TjreportsModelReports->getUserFilter();
		}

		// Get course filter
		if (in_array('title', $colToshow))
		{
			$this->courseFilter = $TjreportsModelReports->getCourseFilter($created_by);
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
	public function plgstudentcoursereportgetColNames()
	{
		$ColArray = array(
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_ID' => 'id',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_TITLE' => 'title',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_CAT_TITLE' => 'cat_title',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_USER_ID' => 'user_id',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_USERNAME' => 'username',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_CERTIFICATE_TERM' => 'certificate_term',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_ENROLLED_ON_TIME' => 'enrolled_on_time',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_END_TIME' => 'end_time',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_ACCESS_LEVEL_TITLE' => 'access_level_title',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_COMPLETION' => 'completion',
			'PLG_TJREPORTS_STUDENTCOURSEREPORT_TOTALTIMESPENT' => 'totaltimespent'
		);

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
			'cat_title',
			'user_id',
			'username'
		);

		return $filterArray;
	}
}
