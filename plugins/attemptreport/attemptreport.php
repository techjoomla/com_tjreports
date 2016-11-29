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

class PlgTjreportsAttemptreport extends JPlugin
{
	/**
	 * Plugin that supports creating the tjreports dashboard
	 *
	 * @param   string   &$subject  The context of the content being passed to the plugin.
	 * @param   integer  $config    Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void.
	 *
	 * @since 1.0.0
	 */
	public function PlgTjreportsAttemptreport(&$subject, $config)
	{
		$lang         = JFactory::getLanguage();
		$extension    = 'plg_tjreports_attemptreport';
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
	public function plgattemptreportGetData($filters, $colNames, $rows_to_fetch, $limit_start, $sortCol, $sortOrder, $created_by)
	{
		$db        = JFactory::getDBO();
		$mainframe = JFactory::getApplication('admin');

		// Get all Columns
		$allColumns = $this->plgattemptreportgetColNames();

		// Get only those columns which user has to show
		$colToshow = array_intersect($allColumns, $colNames);

		// Columns which has search fields
		$showSearchToCol = $this->showSearchToCol();

		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select('lt.attempt');

		// Get only selected data as per columns to show
		foreach ($colToshow as $eachCol)
		{
			switch ($eachCol)
			{
				case 'name':
					$query->select('l.name');
					break;
				case 'username':
					$query->select('u.username');
					break;
				case 'time_spent':
					$query->select('lt.time_spent');
					break;
				case 'lesson_status':
					$query->select('lt.lesson_status');
					break;
				case 'score':
					$query->select('lt.score');
					break;
				case 'last_accessed_on':
					$query->select('lt.last_accessed_on');
					break;
			}
		}

		$query->from('`#__tjlms_lesson_track` AS lt');
		$query->join('INNER', '`#__tjlms_lessons` as l ON lt.lesson_id = l.id');
		$query->join('INNER', '`#__tjlms_courses` as c ON c.id = l.course_id');

		// Check for permission

		if ($created_by)
		{
			$query->where('c.created_by=' . $created_by);
		}

		if (in_array('username', $colToshow))
		{
			$query->join('INNER', '`#__users` as u ON lt.user_id = u.id');
		}

		// If we have filter value then add respected where conditions
		if (!empty($filters))
		{
			// Do filter related thing
			if (array_key_exists('attempt', $filters))
			{
				$query->where('lt.attempt=' . $filters['attempt']);
			}

			/*if (array_key_exists('name', $filters))
			{
				$searchName = $db->Quote('%' . $db->escape($filters['name'], true) . '%');
				$query->where('l.name LIKE' . $searchName);
			}*/

			if (array_key_exists('name', $filters))
			{
				$query->where('l.id=' . $filters['name']);
			}

			if (array_key_exists('username', $filters))
			{
				$query->where('u.id=' . $filters['username']);
			}

			// Do filter related thing
			if (array_key_exists('lesson_status', $filters))
			{
				$lesson_status = $db->Quote('%' . $db->escape($filters['lesson_status'], true) . '%');
				$query->where('lt.lesson_status LIKE ' . $lesson_status);
			}

			// Do filter related thing
			if (array_key_exists('fromDate', $filters))
			{
				$attemptStarts = $filters['fromDate'];
				$query->where('DATE(lt.last_accessed_on)>=' . "'" . $attemptStarts . "'");
			}

			// Do filter related thing
			if (array_key_exists('toDate', $filters))
			{
				$attemptEnds = $filters['toDate'];
				$query->where('DATE(lt.last_accessed_on)<=' . "'" . $attemptEnds . "'");
			}
		}

		// If the sorting is empty get the value from user state
		if (empty($sortCol) && empty($sortOrder))
		{
			$sortCol   = $mainframe->getUserState("com_tjreports.attemptreport_table_sortCol", '');
			$sortOrder = $mainframe->getUserState("com_tjreports.attemptreport_table_sortOrder", '');
		}

		// Apply sorting is applicable
		if ((!empty($sortCol) && !empty($sortOrder)) && in_array($sortCol, $showSearchToCol) && in_array($sortCol, $colToshow))
		{
			if ($sortCol == 'name')
			{
				$sortColwithPrefic = 'l.name';
			}
			elseif ($sortCol == 'username')
			{
				$sortColwithPrefic = 'u.username';
			}
			else
			{
				$sortColwithPrefic = 'lt.' . $sortCol;
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

		foreach ($items as $ind => $attempt)
		{
			if (in_array('time_spent', $colToshow))
			{
				if ($attempt->time_spent == '00:00:00')
				{
					$attempt->time_spent = '-';
				}
			}
		}

		$path = JPATH_SITE . '/components/com_tjlms/helpers/tracking.php';

		if (!class_exists('ComtjlmstrackingHelper'))
		{
			JLoader::register('ComtjlmstrackingHelper', $path);
			JLoader::load('ComtjlmstrackingHelper');
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
	 * @param   int     $created_by   Which user has permission to report
	 * @param   STRING  $layout       Which layout to show
	 *
	 * @return    JDatabaseQuery
	 *
	 * @since    1.0
	 */
	public function plgattemptreportRenderPluginHTML($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $action, $created_by, $layout = 'default')
	{
		// Get data
		$resultData = $this->plgattemptreportGetData($filters, $colNames, $rowsToFetch, $limit_start, $sortCol, $sortOrder, $created_by);

		// Get Items
		$reportData = $resultData['items'];

		// Coulmns to show
		$colToshow = $resultData['colToshow'];

		// Columns which are used for search fields
		$showSearchToCol = $this->showSearchToCol();

		$path = JPATH_SITE . '/com_tjreports/components/models/reports.php';

		if (!class_exists('TjreportsModelReports'))
		{
			// Require_once $path;
			JLoader::register('TjreportsModelReports', $path);
			JLoader::load('TjreportsModelReports');
		}

		$TjreportsModelReports = new TjreportsModelReports;

		// Get lesson filter
		if (in_array('name', $colToshow))
		{
			$this->lessonFilter = $TjreportsModelReports->getLessonFilter($created_by);
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
	public function plgattemptreportgetColNames()
	{
		$ColArray = array(
			'PLG_TJREPORTS_ATTEMPTREPORT_ATTEMPT' => 'attempt',
			'PLG_TJREPORTS_ATTEMPTREPORT_NAME' => 'name',
			'PLG_TJREPORTS_ATTEMPTREPORT_USERNAME' => 'username',
			'PLG_TJREPORTS_ATTEMPTREPORT_TIME_SPENT' => 'time_spent',
			'PLG_TJREPORTS_ATTEMPTREPORT_LESSON_STATUS' => 'lesson_status',
			'PLG_TJREPORTS_ATTEMPTREPORT_SCORE' => 'score',
			'PLG_TJREPORTS_ATTEMPTREPORT_LAST_ACCESSED_ON' => 'last_accessed_on'
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
			'attempt',
			'name',
			'username',
			'lesson_status'
		);

		return $filterArray;
	}
}
