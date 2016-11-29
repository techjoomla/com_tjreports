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

class PlgTjreportsLessonreport extends JPlugin
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
	public function PlgTjreportsLessonreport(&$subject, $config)
	{
		$lang         = JFactory::getLanguage();
		$extension    = 'plg_tjreports_lessonreport';
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
	public function plglessonreportGetData($filters, $colNames, $rows_to_fetch, $limit_start, $sortCol, $sortOrder, $created_by)
	{
		$lmsparams = JComponentHelper::getParams('com_tjlms');
		$date_format_show = $lmsparams->get('date_format_show', 'Y-m-d H:i:s');

		$this->techjoomlacommon = new TechjoomlaCommon;
		$db        = JFactory::getDBO();
		$mainframe = JFactory::getApplication('admin');
		$path = JPATH_SITE . '/components/com_tjlms/helpers/main.php';

		if (!class_exists('ComtjlmsHelper'))
		{
			JLoader::register('ComtjlmsHelper', $path);
			JLoader::load('ComtjlmsHelper');
		}

		$ComtjlmsHelper = new ComtjlmsHelper;

		// Get all Columns
		$allColumns = $this->plglessonreportgetColNames();

		// Get only those columns which user has to show
		$colToshow = array_intersect($allColumns, $colNames);

		// Columns which has search fields
		$showSearchToCol = $this->showSearchToCol();

		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('l.id, lt.user_id');

		// Get only selected data as per columns to show
		foreach ($colToshow as $eachCol)
		{
			switch ($eachCol)
			{
				case 'name':
					$query->select('l.name');
					break;
				case 'courseTitle':
					$query->select('c.title as courseTitle');
					break;
				case 'username':
					$query->select('u.username');
					break;
				case 'start_date':
					$query->select('l.start_date');
					break;
				case 'end_date':
					$query->select('l.end_date');
					break;
				case 'no_of_attempts':
					$query->select('l.no_of_attempts');
					break;
				case 'attemptsDone':
					$query->select('COUNT(lt.attempt) as attemptsDone');
					break;
				case 'timeSpentOnLesson':
					$query->select('SEC_TO_TIME(SUM(TIME_TO_SEC(time_spent))) as timeSpentOnLesson');
					break;

				case 'lessonformat':
				case 'attempts_grade':
				case 'status':
				case 'score':
					$query->select('l.attempts_grade, l.format as lessonformat');
					break;
				case 'consider_marks':
					$query->select('l.consider_marks');
					break;
			}
		}

		$query->from('`#__tjlms_lesson_track` AS lt');
		$query->join('INNER', '`#__tjlms_lessons` as l ON lt.lesson_id = l.id');

		if (in_array('courseTitle', $colToshow))
		{
			$query->join('INNER', '`#__tjlms_courses` as c ON l.course_id = c.id');

			if ($created_by)
			{
				$query->where('c.created_by=' . $created_by);
			}
		}

		if (in_array('username', $colToshow))
		{
			$query->join('INNER', '`#__users` as u ON lt.user_id = u.id');
		}

		// If we have filter value then add respected where conditions
		if (!empty($filters))
		{
			// Do filter related thing
			if (array_key_exists('id', $filters))
			{
				$query->where('l.id=' . $filters['id']);
			}

			// Do filter related thing
			if (array_key_exists('name', $filters))
			{
				$searchName = $db->Quote('%' . $db->escape($filters['name'], true) . '%');
				$query->where('l.name LIKE' . $searchName);
			}

			/*if (array_key_exists('username', $filters))
			{
				$searchUserName = $db->Quote('%' . $db->escape($filters['username'], true) . '%');
				$query->where('u.username LIKE ' . $searchUserName);
			}*/

			if (array_key_exists('username', $filters))
			{
				$query->where('u.id=' . $filters['username']);
			}

			/*if (array_key_exists('courseTitle', $filters))
			{
				$courseTitle = $db->Quote('%' . $db->escape($filters['courseTitle'], true) . '%');
				$query->where('c.title LIKE ' . $courseTitle);
			}*/

			if (array_key_exists('courseTitle', $filters))
			{
				$query->where('c.id=' . $filters['courseTitle']);
			}

			if (array_key_exists('user_group', $filters))
			{
				$searchGroup = $db->Quote('%' . $db->escape($filters['user_group'], true) . '%');
				$query->join('left', '`#__user_usergroup_map` as uum ON u.id = uum.user_id');
				$query->join('left', '`#__usergroups` as ug ON uum.group_id= ug.id');
				$query->where('ug.title LIKE ' . $searchGroup);
			}
		}

		$query->group('l.id,lt.user_id');

		// If the sorting is empty get the value from user state
		if (empty($sortCol) && empty($sortOrder))
		{
			$sortCol   = $mainframe->getUserState("com_tjlms.lessonreport_table_sortCol", '');
			$sortOrder = $mainframe->getUserState("com_tjlms.lessonreport_table_sortOrder", '');
		}

		// Apply sorting if applicable
		if ((!empty($sortCol) && !empty($sortOrder)) && in_array($sortCol, $showSearchToCol) && in_array($sortCol, $colToshow))
		{
			switch ($sortCol)
			{
				case 'id':
					$sortColwithPrefic = 'l.id';
					break;
				case 'name':
					$sortColwithPrefic = 'l.name';
					break;
				case 'courseTitle':
					$sortColwithPrefic = 'c.title';
					break;
				case 'username':
					$sortColwithPrefic = 'u.username';
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

		$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		if (!class_exists('ComtjlmstrackingHelper'))
		{
			JLoader::register('ComtjlmstrackingHelper', $path);
			JLoader::load('ComtjlmstrackingHelper');
		}

		$ComtjlmstrackingHelper = new ComtjlmstrackingHelper;

		foreach ($items as $ind => $lessonDetails)
		{
			if (in_array('start_date', $colToshow))
			{
				$lessonDetails->start_date = $this->techjoomlacommon->getDateInLocal($lessonDetails->start_date, 0, $date_format_show);

				if ($lessonDetails->start_date == '0000-00-00 00:00:00')
				{
					$lessonDetails->start_date = '-';
				}
			}

			if (in_array('end_date', $colToshow))
			{
				$lessonDetails->end_date = $this->techjoomlacommon->getDateInLocal($lessonDetails->end_date, 0, $date_format_show);

				if ($lessonDetails->end_date == '0000-00-00 00:00:00')
				{
					$lessonDetails->end_date = '-';
				}
			}

			if (in_array('no_of_attempts', $colToshow))
			{
				if ($lessonDetails->no_of_attempts == 0)
				{
					$lessonDetails->no_of_attempts = JText::_('PLG_TJREPORTS_LESSONREPORT_UNLIMITED');
				}
			}

			if (in_array('consider_marks', $colToshow))
			{
				if ($lessonDetails->consider_marks == 0)
				{
					$lessonDetails->consider_marks = JText::_('JNO');
				}
				else
				{
					$lessonDetails->consider_marks = JText::_('JYES');
				}
			}

			if (in_array('status', $colToshow) || in_array('score', $colToshow))
			{
				$lesson = new stdclass;
				$lesson->id = $lessonDetails->id;
				$lesson->attempts_grade = $lessonDetails->attempts_grade;
				$lesson->format = $lessonDetails->lessonformat;

				$result = $ComtjlmstrackingHelper->getLessonattemptsGrading($lesson, $lessonDetails->user_id);
			}

			if (in_array('status', $colToshow))
			{
				$lessonDetails->status = '';

				if (isset($result->lesson_status))
				{
					$lessonDetails->status = $result->lesson_status;
				}
			}

			if (in_array('score', $colToshow))
			{
				$lessonDetails->score = '';

				if (isset($result->score))
				{
					$lessonDetails->score = floor($result->score);
				}
			}

			if (in_array('attempts_grade', $colToshow))
			{
				switch ($lessonDetails->attempts_grade)
				{
					case '0':
							$lessonDetails->attempts_grade = JText::_('COM_TJREPORTS_HIGHEST_ATTEMPT');
							break;
					case '1':
							$lessonDetails->attempts_grade = JText::_('COM_TJREPORTS_AVERAGE_ATTEMPT');
							break;
					case '2':
							$lessonDetails->attempts_grade = JText::_('COM_TJREPORTS_FIRST_ATTEMPT');
							break;
					case '3':
							$lessonDetails->attempts_grade = JText::_('COM_TJREPORTS_LAST_COMPLETED_ATTEMPT');
							break;
				}
			}

			if (in_array('timeSpentOnLesson', $colToshow))
			{
				if ($lessonDetails->timeSpentOnLesson == '00:00:00')
				{
					$lessonDetails->timeSpentOnLesson = '-';
				}
			}
		}

		// Apply sorting.
		if ((!empty($sortCol) && !empty($sortOrder)) && !in_array($sortCol, $showSearchToCol) && in_array($sortCol, $colToshow))
		{
			$items = $ComtjlmsHelper->multi_d_sort($items, $sortCol, $sortOrder);
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
	public function plglessonreportRenderPluginHTML($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $action, $created_by, $layout = 'default')
	{
		// Get data
		$resultData = $this->plglessonreportGetData($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $created_by);

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
		if (in_array('courseTitle', $colToshow))
		{
			$this->courseFilter = $TjreportsModelReports->getCourseFilter($created_by);
		}

		if (in_array('username', $colToshow))
		{
			$this->userFilter = $TjreportsModelReports->getUserFilter();
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
	public function plglessonreportgetColNames()
	{
		$ColArray = array(
			'PLG_TJREPORTS_LESSONREPORT_ID' => 'id',
			'PLG_TJREPORTS_LESSONREPORT_NAME' => 'name',
			'PLG_TJREPORTS_LESSONREPORT_COURSETITLE' => 'courseTitle',
			'PLG_TJREPORTS_LESSONREPORT_USERNAME' => 'username',
			'PLG_TJREPORTS_LESSONREPORT_START_DATE' => 'start_date',
			'PLG_TJREPORTS_LESSONREPORT_END_DATE' => 'end_date',
			'PLG_TJREPORTS_LESSONREPORT_NO_OF_ATTEMPTS' => 'no_of_attempts',
			'PLG_TJREPORTS_LESSONREPORT_ATTEMPTSDONE' => 'attemptsDone'
		);

		$ColArray['PLG_TJREPORTS_LESSONREPORT_TIMESPENTONLESSON'] = 'timeSpentOnLesson';
		$ColArray['PLG_TJREPORTS_LESSONREPORT_ATTEMPTS_GRADE'] = 'attempts_grade';
		$ColArray['PLG_TJREPORTS_LESSONREPORT_LESSONFORMAT'] = 'lessonformat';
		$ColArray['PLG_TJREPORTS_LESSONREPORT_CONSIDER_MARKS'] = 'consider_marks';
		$ColArray['PLG_TJREPORTS_LESSONREPORT_STATUS'] = 'status';
		$ColArray['PLG_TJREPORTS_LESSONREPORT_SCORE'] = 'score';

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
			'courseTitle',
			'username'
		);

		return $filterArray;
	}
}
