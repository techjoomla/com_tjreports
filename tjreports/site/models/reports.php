<?php
/**
 * @package     TJReports
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;

use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
Use Joomla\Registry\Registry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
Use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;



// Load TJReports db helper
JLoader::import('database', JPATH_SITE . '/components/com_tjreports/helpers');
JLoader::import('components.com_tjreports.helpers.tjreports', JPATH_ADMINISTRATOR);

/**
 * Methods supporting a list of Tjreports records.
 *
 * @since  1.0.0
 */
class TjreportsModelReports extends ListModel
{
	// Default ordering of Data
	protected $default_order = '';

	// Default ordering direction
	protected $default_order_dir = 'ASC';

	// Number of TH Rows required in the table
	public $headerLevel = 1;

	// Add custom messages
	public $messages = array();

	// Columns array contain columns data
	public $columns = array();

	// Columns array contain PII columns
	private $piiColumns = array();

	// Columns array contain email columns
	private $emailColumn = '';

	// Columns that a user can select to display
	public $showhideCols = array();

	// Columns which will be displayed by default
	public $defaultColToShow = array();

	// Columns which will be hide by default
	private $defaultColToHide = array();

	// Columns which are sortable with or without query statement
	public $sortableColumns = array();

	// Columns which are not possible to sort by SQl Order by
	public $sortableWoQuery = array();

	// Whether to display Search & Reset button or not
	public $showSearchResetButton = true;

	// Used for to limit query
	protected $canLimitQuery = false;

	public $customFieldsTable = '';

	public $customFieldsTableAlias = '';

	private $customFieldsTableExists = false;

	public $customFieldsQueryJoinOn = '';

	public $customFieldsTableColumnsForQuery = array();

	public $customFieldsTypesNotAllowedAsFilter = array ('color', 'imagelist', 'media' /*, 'repeatable'*/);

	protected $tjreportsDbHelper;

	private $piiPermission;

	private $filterShowhideCols = array();

	private $filterPiiColumns = array();

	public $filterParamColToshow = array();

	// Used to get the columns which are hide by default in load params
	public $filterDefaultColToHide = array();

	/**
	 * Show button to display option as Summary Report & Details Report
	 *
	 * @var    string
	 * @since  1.1.6
	 */
	public $showSummaryReport = 'No';

	/**
	 * Array about field type which suppport for summary report
	 *
	 * @var    string
	 * @since  1.1.6
	 */
	public $supportedFieldTypesForSummaryReport = array ('radio', 'checkbox', 'rating');

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     BaseDatabaseModel
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		// Joomla custom field (com_fields) integration
		if (!empty($this->customFieldsTable))
		{
			$this->tjreportsDbHelper = new TjreportsfieldsHelperDatabase;

			// Check if table exists
			$this->customFieldsTableExists = $this->tjreportsDbHelper->tableExists($this->customFieldsTable);

			// Set custom fields columns
			$this->setCustomFieldsColumns();
		}

		// Get email column
		$this->emailColumn = array_search(
			1,
			array_map(
				function ($ar)
				{
					if (!empty($ar['emailColumn']))
					{
						return $ar['emailColumn'];
					}
				},
				$this->columns
			)
		);

		// Check PII data permission
		$this->piiPermission = $this->canViewPiiData();

		$this->initData();

		parent::__construct($config);
	}

	/**
	 * Set custom fields columns
	 *
	 * @return  void
	 *
	 * @since   1.1.0
	 */
	protected function setCustomFieldsColumns()
	{
		// If no custom fields index table for current plugin, return
		if (empty($this->customFieldsTable))
		{
			return;
		}

		// If table is not reated yet, return
		if (!$this->customFieldsTableExists)
		{
			return;
		}

		// Get column name, type for custom fields index table
		$db             = Factory::getDbo();
		$columnsDetails = $db->getTableColumns($this->customFieldsTable);

		// If no columns, return
		if (!count($columnsDetails))
		{
			return;
		}

		// Extract column names from $columnDetails
		$columnNames = array_keys($columnsDetails);

		// Get column labels from #__fields table for all indexed custom fields from this table
		$query = $db->getQuery(true);
		$query->select(array($db->quoteName('name'), $db->quoteName('label')));
		$query->from($db->quoteName('#__fields'));
		$query->where($db->quoteName('name') . ' IN (' . implode(',', $db->quote($columnNames)) . ')');
		$query->where($db->quoteName('state') . ' = 1');
		$db->setQuery($query);
		$columnLabels = $db->loadAssocList('name', 'label');

		// Set columns from custom fields table into tjreports plugin's column list
		foreach ($columnNames as $columnName)
		{
			$customField = array (
				'title'         => isset($columnLabels[$columnName]) ? $columnLabels[$columnName] : $columnName,
				'table_column'  => $this->customFieldsTableAlias . '.' . $columnName,
				'not_show_hide' => false
			);

			// Eg. tuf.dob
			$this->columns[$this->customFieldsTableAlias . '.' . $columnName] = $customField;
		}

		// @print_r($this->columns);
	}

	/**
	 * Set Custom Fields Display Filters
	 *
	 * @param   array  &$displayFilters  Array of display filters
	 *
	 * @return  void
	 *
	 * @since   1.1.0
	 */
	protected function setCustomFieldsDisplayFilters(&$displayFilters)
	{
		// If no custom fields index table for current plugin, return
		if (empty($this->customFieldsTable))
		{
			return;
		}

		if (!$this->customFieldsTableExists)
		{
			return;
		}

		// Get column name, type for custom fields index table
		$db             = Factory::getDbo();
		$columnsDetails = $db->getTableColumns($this->customFieldsTable);

		// If no columns, return
		if (!count($columnsDetails))
		{
			return;
		}

		// Extract column names from $columnDetails
		$columnNames   = array();
		$colToshow     = $this->getState('colToshow');
		$colToShowKeys = array_keys($colToshow);

		foreach ($columnsDetails as $key => $val)
		{
			$columnNameWithTableAlias = $this->customFieldsTableAlias . '.' . $key;

			// Skip columns which are not set in model state
			if (in_array($columnNameWithTableAlias, $colToShowKeys))
			{
				$columnNames[] = $key;
			}
		}

		if (empty($columnNames))
		{
			return;
		}

		// Set this,this would be used by plugins to query fields
		$this->customFieldsTableColumnsForQuery = $columnNames;

		// Get column labels from #__fields table for all indexed custom fields from this table
		$query = $db->getQuery(true);
		$query->select(array($db->quoteName('name'), $db->quoteName('type')));
		$query->from($db->quoteName('#__fields'));
		$query->where($db->quoteName('name') . ' IN (' . implode(',', $db->quote($columnNames)) . ')');
		$query->where($db->quoteName('state') . ' = 1');
		$db->setQuery($query);
		$fields = $db->loadAssocList('name', 'type');

		// Extract column names from $columnDetails
		// key = field name, $val = field type
		foreach ($fields as $key => $val)
		{
			// Skip column types not allowerd as filter
			if (in_array($val, $this->customFieldsTypesNotAllowedAsFilter))
			{
				continue;
			}

			switch ($val)
			{
				case 'calendar':
					$displayFilters[0][$this->customFieldsTableAlias . '.' . $key] = array(
						'search_type'    => 'date.range',
						'searchin'       => $this->customFieldsTableAlias . '.' . $key,
						$this->customFieldsTableAlias . '.' . $key . '_from' => array (
							'attrib' => array (
								'placeholder' => 'YYYY-MM-DD',
								'onChange'    => 'tjrContentUI.report.attachCalSubmit(this);'
							)
						),
						$this->customFieldsTableAlias . '.' . $key . '_to' => array (
							'attrib' => array (
								'placeholder' => 'YYYY-MM-DD',
								'onChange'    => 'tjrContentUI.report.attachCalSubmit(this);'
							)
						)
					);
				break;

				case 'checkboxes':
				case 'integer':
				case 'list':
				case 'radio':
				case 'sql':
				case 'user':
				case 'usergrouplist':
					$displayFilters[0][$this->customFieldsTableAlias . '.' . $key] = array(
						'search_type'    => 'select',
						'type'           => 'equal',
						'searchin'       => $this->customFieldsTableAlias . '.' . $key,
						'select_options' => $this->getCustomFieldsDisplayFilterOptions($key)
					);
				break;

				case 'editor':
				case 'text':
				case 'url':
					$displayFilters[0][$this->customFieldsTableAlias . '.' . $key] = array(
						'search_type' => 'text',
						'type'        => 'equal',
						'searchin'    => $this->customFieldsTableAlias . '.' . $key
					);
				break;

				default:
			}
		}
	}

	/**
	 * Set Custom Fields Display Filters
	 *
	 * @param   string  $column  Column name
	 *
	 * @return  object
	 *
	 * @since   1.1.0
	 */
	protected function getCustomFieldsDisplayFilterOptions($column)
	{
		$objArray   = array();
		$obj        = new stdClass;
		$obj->text  = Text::_('- Select ' . $column . ' -');
		$obj->value = '';
		$objArray[] = $obj;

		// Get column name, type for custom fields index table
		$db = Factory::getDbo();

		// Get column labels from #__fields table for all indexed custom fields from this table
		$query = $db->getQuery(true);
		$query->select('DISTINCT(' . $db->quoteName($column) . ') AS text, ' . $db->quoteName($column) . ' AS value');
		$query->from($db->quoteName($this->customFieldsTable));
		$db->setQuery($query);

		$options = $db->loadObjectList();

		$options = (object) array_merge((array) $objArray, (array) $options);

		return $options;
	}

	/**
	 * Get table header columns name
	 *
	 * @return ARRAY Keys of data
	 *
	 * @since   2.0
	 * */
	private function initData()
	{
		$columns                = $this->columns;
		$columnsKeys            = array_keys($columns);
		$this->sortableWoQuery  = array();

		$this->defaultColToShow = $this->sortableColumns = $this->showhideCols = array_combine($columnsKeys, $columnsKeys);

		foreach ($columns as $key => $column)
		{
			if ((isset($column['not_show_hide']) && $column['not_show_hide'] === true)
				|| (strpos($key, '::') !== false && !isset($column['not_show_hide'])))
			{
				// If column not_show_hide = true then this column hide from show/hide list as well as on the report
				unset($this->showhideCols[$key]);
				unset($this->defaultColToShow[$key]);
			}

			if ((isset($column['disable_sorting']) && $column['disable_sorting'])
				|| (strpos($key, '::') !== false && !isset($column['disable_sorting'])))
			{
				unset($this->sortableColumns[$key]);
			}

			if (isset($column['isPiiColumn']) && $column['isPiiColumn'] === true)
			{
				array_push($this->piiColumns, $key);
			}

			if (!isset($column['disable_sorting']) && (!isset($column['table_column']) || !in_array($key, $this->sortableColumns)))
			{
				array_push($this->sortableWoQuery, $key);
			}

			if (!isset($column['title']) || (strpos($key, '::') !== false)
				|| (isset($column['not_show_hide']) && $column['not_show_hide'] === false))
			{
				/* If column not_show_hide = false then column does not show on the report
				 * but shows on Hide / show column list dropdown with unchecking the checkbox.*/
				array_push($this->defaultColToHide, $key);
				unset($this->defaultColToShow[$key]);
			}
		}

		$this->piiColumns       = array_values($this->piiColumns);
		$this->showhideCols     = array_values($this->showhideCols);
		$this->sortableColumns  = array_values($this->sortableColumns);
		$this->sortableWoQuery  = array_values($this->sortableWoQuery);
		$this->defaultColToShow = array_values($this->defaultColToShow);
		$this->defaultColToHide = array_values($this->defaultColToHide);
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
	 * Get variables can be set in db for report queries
	 *
	 * @return  ARRAY fields array
	 *
	 * @since   2.0
	 * */
	public function getValidRequestVars()
	{
		$validVars = array(
			'colToshow'        => 'ARRAY',
			'filters'          => 'ARRAY',
			'limit'            => 'INT',
			'limitstart'       => 'INT',
			'filter_order'     => 'STRING',
			'filter_order_Dir' => 'STRING'
		);

		return $validVars;
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
	public function displayFilters()
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
		if ($this->canLimitQuery)
		{
			$this->getDbo()->setQuery($query, $limitstart, $limit);
		}
		else
		{
			$this->getDbo()->setQuery($query, 0, 0);
		}

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
		$app   = Factory::getApplication();
		$input = $app->input;

		if (!($reportId = $input->get('reportId', 0, 'uint')))
		{
			if ($reportName = $input->get('report', 0, 'string'))
			{
				$reportId = $this->getDefaultReport($reportName);
			}
		}

		$this->setState('reportId', $reportId);

		$colToshow = $input->get('colToshow', array(), 'ARRAY');

		if (empty($colToshow))
		{
			$reportParams = $this->getReportParams($reportId);
			$colToshow    = (array) $reportParams->get("colToshow");
			$piiColumns   = (array) $reportParams->get("piiColumns");
			$piiColumns   = array_flip($piiColumns);

			if (!empty($piiColumns))
			{
				$colToshow = (object) array_diff_key($colToshow, $piiColumns);
			}
		}

		$this->filterReportColumns($reportId, $colToshow);

		if (empty($colToshow))
		{
			$colToshow = $this->defaultColToShow;
		}

		$this->setState('colToshow', $colToshow);

		$filters = $input->get('filters', array(), 'ARRAY');
		$this->setState('filters', $filters);

		// List state information
		$value = $input->get('limit', $app->get('list_limit', 0), 'int');
		$this->setState('list.limit', $value);

		$value = $input->get('limitstart', 0, 'int');
		$this->setState('list.start', $value);

		// Set show summary report configuration in params
		if (!empty($this->showSummaryReport))
		{
			$this->setState('showSummaryReport', $this->showSummaryReport);
		}

		if (!empty($this->piiColumns))
		{
			$this->setState('piiColumns', $this->piiColumns);
		}

		if ($this->emailColumn)
		{
			$this->setState('emailColumn', $this->emailColumn);
		}

		if ($this->defaultColToHide)
		{
			// Array set hide column in param as false value
			$defaultColToHide = array_combine($this->defaultColToHide, array_fill(0, count($this->defaultColToHide), false));
			$this->setState('defaultColToHide', $defaultColToHide);
		}

		// Ordering
		$this->default_order = $input->get('filter_order', $this->default_order, 'STRING');

		// If last sorted by column is hidden sort by first visible column
		if (!in_array($this->default_order, $colToshow))
		{
			$visibleSortable = array_intersect($colToshow, $this->sortableColumns);
			$this->default_order = reset($visibleSortable);
		}

		$this->setState('list.ordering', $this->default_order);

		// Ordering Direction
		$this->default_order_dir = $input->get('filter_order_Dir', $this->default_order_dir);

		if (!in_array(strtoupper($this->default_order_dir), array('ASC', 'DESC', '')))
		{
			$this->default_order_dir = 'ASC';
		}

		$this->setState('list.direction', $this->default_order_dir);

		parent::populateState($this->default_order, $this->default_order_dir);
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object to retrieve the data set.
	 *
	 * @since   1.6
	 */
	protected function getListQuery()
	{
		$input 			= Factory::getApplication()->input;
		$db  			= Factory::getDBO();
		$query 			= $db->getQuery(true);
		$filters 		= (array) $this->getState('filters');
		$displayFilters = (array) $this->displayFilters();
		$colToshow		= (array) $this->getState('colToshow');
		$filterLevel    = count($displayFilters);

		if ($filterLevel == 2)
		{
			$topLevelFilters = array_keys($displayFilters[1]);
			$colToshow       = array_merge($colToshow, $topLevelFilters);
		}

		// Select columns which are directly linked to table columns
		foreach ($colToshow as $columnName)
		{
			if (!is_array($columnName) && !empty($this->columns[$columnName]['table_column']))
			{
				if (isset($this->columns[$columnName]['not_quote_column']))
				{
					$query->select($this->columns[$columnName]['table_column']);
				}
				else
				{
					$query->select($db->quoteName($this->columns[$columnName]['table_column'], $columnName));
				}
			}
		}

		// Loop through different levels of filters
		foreach ($displayFilters as $key => $displayFilter)
		{
			foreach ($displayFilter as $key => $dispFilter)
			{
				// Check if any of the filter is set
				if (((isset($filters[$key]) && $filters[$key] != '') || substr($dispFilter['search_type'], -6) === '.range') && in_array($key, $colToshow))
				{
					if (!isset($dispFilter['searchin']))
					{
						continue;
					}

					$columnName = $dispFilter['searchin'];

					if (substr($dispFilter['search_type'], -6) === '.range')
					{
						$fromCol = $columnName . '_from';
						$toCol   = $columnName . '_to';

						if (!empty($filters[$fromCol]))
						{
							$fromTime = $filters[$fromCol] . ' 00:00:00';
							$fromTime = new Date($fromTime, 'UTC');
							$query->where($dispFilter['searchin'] . ' >= ' . $db->quote($fromTime));
						}

						if (!empty($filters[$toCol]))
						{
							$toTime = $filters[$toCol] . ' 23:59:59';
							$toTime = new Date($toTime, 'UTC');
							$query->where($dispFilter['searchin'] . ' <= ' . $db->quote($toTime));
						}
					}
					elseif (isset($dispFilter['type']))
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

		// Add the list ordering clause.
		$sortKey  = $this->getState('list.ordering', $this->default_order);
		$orderDir = $this->getState('list.direction', $this->default_order_dir);

		if (!empty($sortKey) && in_array($sortKey, $this->sortableColumns))
		{
			$query->order($db->quoteName($sortKey) . ' ' . $orderDir);
			$this->canLimitQuery = true;
		}

		// Remove limit while showing summary report
		$tpl = $input->get('tpl', 'default', 'string');
		$tpl = ($tpl == 'default' || $tpl == 'submit') ? null : $tpl;

		if ($tpl != null)
		{
			$this->canLimitQuery = false;
		}

		// Joomla fields integration - Get custom fields data
		// Proceed if table exists, and at least one custom field is seleced for showing
		if ($this->customFieldsTableExists && !empty($this->customFieldsTableColumnsForQuery) && !empty($this->customFieldsQueryJoinOn))
		{
			// Since in actual query columns are used as tablealias.columnname,
			// Lets build such array
			$customFieldColumns = array();

			foreach ($this->customFieldsTableColumnsForQuery as $cfc)
			{
				$customFieldColumns[] = $this->customFieldsTableAlias . '.' . $cfc;
			}

			// If at least one custom field is seleced for showing, select record_id column as well
			if (!empty($customFieldColumns))
			{
				$customFieldColumns[] = $this->customFieldsTableAlias . '.record_id';

				$query->select($db->quoteName($customFieldColumns));
				$query->join(
					'LEFT', $db->quoteName($this->customFieldsTable, $this->customFieldsTableAlias) .
					' ON ' . $db->quoteName($this->customFieldsTableAlias . '.record_id') . ' = ' . $db->quoteName($this->customFieldsQueryJoinOn)
				);
			}
		}

		return $query;
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
		$limitstart = $this->getState('list.start', 0);

		// Apply sorting and Limit if sorted column is not table
		if (!empty($items) && !empty($sortKey)
			&& in_array($sortKey, $this->sortableColumns) && $limit)
		{
			$orderDir = $this->getState('list.direction', $this->default_order_dir);
			$this->multi_d_sort($items, $sortKey, $orderDir);
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
	 * @return    Mixed
	 *
	 * @since    1.0
	 */
	public function getSavedQueries($user_id, $reportToBuild)
	{
		if (!empty($reportToBuild) &&  !empty($user_id))
		{
			$db    = Factory::getDBO();
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
		$ol_user = Factory::getUser()->id;
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
	 * @return    Array
	 *
	 * @since    1.0
	 */
	public function getenableReportPlugins()
	{
		$user   = Factory::getUser();
		$input  = Factory::getApplication()->input;
		$client = $input->get('client', '', 'STRING');

		// Get all report plugin
		$plugins      = PluginHelper::getPlugin('tjreports');
		$pluginExists = json_decode(json_encode($plugins), true);
		$pluginNames  = array_column($pluginExists, 'name');

		$db    = Factory::getDBO();
		$query = $db->getQuery(true);

		$query->select(array('id as reportId, title, plugin, ordering'));
		$query->from($db->quoteName('#__tj_reports'));
		$query->where($db->quoteName('plugin') . ' IN (' . implode(',', $db->quote($pluginNames)) . ')');
		$query->where($db->quoteName('userid') . ' = ' . $db->quote(0));

		if (!empty($client))
		{
			$query->where($db->quoteName('client') . ' = ' . $db->quote($client));
		}

		$query->order('ordering ASC');

		$db->setQuery($query);
		$reports = $db->loadAssocList();

		foreach ($reports as $key => $report)
		{
			$allow = $user->authorise('core.view', 'com_tjreports.tjreport.' . $report['reportId']);

			if (!$allow)
			{
				unset($reports[$key]);
			}
		}

		// In view layouts - reports[0] is used, and since array indexes are unset above,
		// Let's re-arrange index accordingly
		if (is_array($reports))
		{
			$reports = array_values($reports);
		}

		return $reports;
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
		$db = Factory::getDbo();

		$query = $db->getQuery(true);
		$query->select('u.id,u.username');
		$query->from('#__users as u');

		// $query->join('LEFT', '#__users as u ON lt.user_id=u.id');
		$db->setQuery($query);
		$users = $db->loadObjectList();

		$userFilter[] = HTMLHelper::_('select.option', '', Text::_('COM_TJREPORTS_FILTER_SELECT_USER'));

		if (!empty($users))
		{
			foreach ($users as $eachUser)
			{
				$userFilter[] = HTMLHelper::_('select.option', $eachUser->id, $eachUser->username);
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
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true);
		$user_id = Factory::getUser()->id;

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

		$options[] = HTMLHelper::_('select.option', 0, Text::_('COM_TJREPORTS_SELONE_REPORTS'));

		foreach ($reports as $repo)
		{
			$options[] = HTMLHelper::_('select.option', $repo->value, $repo->text);
		}

		return $options;
	}

	/**
	 * Check for permissions
	 *
	 * @return    boolean
	 *
	 * @since    1.0
	 */

	public function canViewPiiData()
	{
		$canDo = TjreportsHelper::getActions();

		return $canDo->get('core.view.piidata');
	}

	/**
	 * Check for permissions
	 *
	 * @param   INT  $reportId  report id
	 *
	 * @return  Mixed
	 *
	 * @since   1.0
	 */

	public function checkpermissions($reportId)
	{
		$user = Factory::getUser();

		if ($reportId)
		{
			$allow = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);

			return $allow;
		}
	}

	/**
	 * Displays a list of user groups.
	 *
	 * @param   boolean  $includeSuperAdmin  true to include super admin groups, false to exclude them
	 *
	 * @return  array  An array containing a list of user groups.
	 *
	 * @since   2.5
	 */
	public function getUserGroupFilter($includeSuperAdmin = true)
	{
		$groups = HTMLHelper::_('user.groups', $includeSuperAdmin);
		array_unshift($groups, HTMLHelper::_('select.option', '', Text::_('JGLOBAL_FILTER_GROUPS_LABEL')));

		return $groups;
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
		$input    = Factory::getApplication()->input;
		$reportId = $input->get('reportId', '0', 'int');

		if ($reportId)
		{
			$this->model = $this->getModel('reports');
			$reportData  = $this->model->getReportNameById($reportId);
			$reportName  = $reportData->title;
		}
		else
		{
			return false;
		}

		$user_id = Factory::getUser()->id;

		if ($reportName && $user_id && $reportId)
		{
			$db    = Factory::getDBO();
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
		$lang      = Factory::getLanguage();

		// If language already loaded, don't load it again.
		if ($lang->getPaths($extension))
		{
			return true;
		}

		return $lang->load($extension, $basePath, null, false, true)
			|| $lang->load($extension, JPATH_PLUGINS . '/' . $type . '/' . $name, null, false, true);
	}

	/**
	 * Method to Process parent Report columns
	 *
	 * @param   INT    $queryId        Query Id
	 * @param   ARRAY  &$selColToshow  Columns to show
	 *
	 * @return  Void
	 *
	 * @since   3.0
	 */
	private function filterReportColumns($queryId, &$selColToshow)
	{
		if (!$queryId)
		{
			return;
		}

		$query = $this->_db->getQuery(true);
		$this->filterDefaultColToHide = $this->filterShowhideCols = $this->filterPiiColumns = $this->filterParamColToshow = array();

		// $this->filterSelColToshow = $selColToshow;

		// Process plugin params
		$parentId = $this->processSavedReportColumns($queryId, $selColToshow);

		// Process if user has saved query is for a plugin
		if (!empty($parentId))
		{
			$this->processSavedReportColumns($parentId, $selColToshow);
		}

		// If plugin has save any column assign that otherwise default plugin param will be applied
		if ($this->filterParamColToshow)
		{
			// If show hide column changes, check if there is any must to hide column
			if ($selColToshow)
			{
				$selColToshow = array_intersect($selColToshow, $this->filterParamColToshow);
				$selColToshow = $selColToshow ? $selColToshow : $this->filterParamColToshow;
			}
			else
			{
				$selColToshow = $this->filterParamColToshow;
			}
		}

		// Used to get the columns which are hide by default in load params
		if (!empty($this->filterDefaultColToHide))
		{
			$this->defaultColToHide = $this->filterDefaultColToHide;
		}

		if (!empty($this->filterShowhideCols))
		{
			$this->showhideCols = $this->filterShowhideCols;
		}

		if (!empty($emailColumn))
		{
			$this->emailColumn = $emailColumn;
		}

		if (!empty($this->filterPiiColumns))
		{
			$this->piiColumns = $this->filterPiiColumns;
		}
	}

	/**
	 * Method to Process parent Report columns
	 *
	 * @param   INT    $queryId        Query Id
	 * @param   ARRAY  &$selColToshow  Selected Cols
	 *
	 * @return  INTEGER
	 *
	 * @since   3.0
	 */
	private function processSavedReportColumns($queryId, &$selColToshow)
	{
		$query = $this->_db->getQuery(true);
		$query->select(array('param', 'parent'))
				->from('#__tj_reports')
				->where('id=' . (int) $queryId);
		$this->_db->setQuery($query);
		$queryData = $this->_db->loadObject();
		$i         = $parent = 0;

		if (!empty($queryData->param))
		{
			$param = json_decode($queryData->param, true);

			if (isset($param['showHideColumns']))
			{
				if (empty($this->filterShowhideCols))
				{
					$this->filterShowhideCols = (array) $param['showHideColumns'];
				}
				else
				{
					$this->filterShowhideCols = array_intersect($this->filterShowhideCols, (array) $param['showHideColumns']);
				}
			}

			// Set the value of show summary report from param to variable
			if (isset($param['showSummaryReport']))
			{
				$this->showSummaryReport = $param['showSummaryReport'];
			}

			if (isset($param['piiColumns']))
			{
				if (empty($this->filterPiiColumns))
				{
					$this->filterPiiColumns = (array) $param['piiColumns'];
				}
				else
				{
					$this->filterPiiColumns = array_intersect($this->filterPiiColumns, (array) $param['piiColumns']);
				}
			}

			if (isset($param['colToshow']))
			{
				foreach ((array) $param['colToshow'] as $cols => $show)
				{
					if ($show !== false || in_array($cols, $selColToshow))
					{
						$this->filterParamColToshow[$cols] = $cols;
					}
					else
					{
						// Used to get the columns which are hide (false) by default in load params
						$this->filterDefaultColToHide[$cols] = $cols;
					}

					if (!empty($param['showHideColumns']) && !in_array($cols, $param['showHideColumns']) && !empty($selColToshow))
					{
						array_splice($selColToshow, $i, 0, $cols);
						$i++;
						$this->filterParamColToshow[$cols] = $cols;
					}
				}
			}

			// Check PII permission
			if (!empty($param['piiColumns']) && !$this->piiPermission)
			{
				/* Checked the columns which are hide(false) in load params & if set them as piiColumns
				* then it only returns the columns which are not available in piiColumns. */
				$this->filterDefaultColToHide = array_diff($this->filterDefaultColToHide, $param['piiColumns']);
				$this->filterParamColToshow   = array_diff($this->filterParamColToshow, $param['piiColumns']);
				$this->filterShowhideCols     = array_diff($this->filterShowhideCols, $param['piiColumns']);
			}

			$parent = $queryData->parent;
		}
		else
		{
			// Check PII permission if load params not set
			if (!empty($this->piiColumns) && !$this->piiPermission)
			{
				$this->defaultColToShow = array_diff($this->defaultColToShow, $this->piiColumns);
				$this->showhideCols     = array_diff($this->showhideCols, $this->piiColumns);
			}
		}

		return $parent;
	}

	/**
	 * Method to get report name by report id
	 *
	 * @param   INT  $reportId  Report Id
	 *
	 * @return  Mixed
	 *
	 * @since   3.0
	 */
	public function getReportNameById($reportId)
	{
		$db          = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');
		$reportTable = Table::getInstance('Tjreport', 'TjreportsTable', array('dbo', $db));
		$reportTable->load(array('id' => $reportId));

		return $reportTable;
	}

	/**
	 * Method to get report link for inter linking
	 *
	 * @param   STRING  $reportToLink  Report Name
	 * @param   STRING  $filters       filter to set
	 *
	 * @return  Object
	 *
	 * @since   3.0
	 */
	public function getReportLink($reportToLink, $filters)
	{
		$user       = Factory::getUser();
		$reports    = $this->getPluginReport($reportToLink);
		$filterLink = '';

		foreach ($filters as $key => $value)
		{
			$filterLink .= "&filters[" . $key . "]=" . $value;
		}

		foreach ($reports as $key => $report)
		{
			$allow = $user->authorise('core.view', 'com_tjreports.tjreport.' . $report['reportId']);

			if ($allow)
			{
				$link = 'index.php?option=com_tjreports&view=reports&client=' . $report['client'] . '&reportId=' . $report['reportId'] . $filterLink;

				return $link;
			}
		}
	}

	/**
	 * Method to get id of the report having default set as 1
	 *
	 * @param   STRING  $reportId  Report Id
	 *
	 * @return  Object
	 *
	 * @since   1.1.0
	 */
	public function getReportParams($reportId)
	{
		$db          = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');
		$reportTable = Table::getInstance('Tjreport', 'TjreportsTable', array('dbo', $db));
		$reportTable->load($reportId);

		return new Registry($reportTable->param);
	}

	/**
	 * Method to get id of the report having default set as 1
	 *
	 * @param   STRING  $pluginName  Plugin Name
	 *
	 * @return  Integer
	 *
	 * @since   1.1.0
	 */
	public function getDefaultReport($pluginName)
	{
		$db          = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');
		$reportTable = Table::getInstance('Tjreport', 'TjreportsTable', array('dbo', $db));
		$reportTable->load(array('plugin' => $pluginName, 'default' => 1));

		return $reportTable->id;
	}

	/**
	 * Method to get report plugin of particular type for inter linking
	 *
	 * @param   STRING  $pluginName  Plugin Name
	 *
	 * @return  Object
	 *
	 * @since   3.0
	 */
	public function getPluginReport($pluginName)
	{
		static $reports = array();

		if (!isset($reports[$pluginName]))
		{
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select(array('id as reportId', 'client'));
			$query->from($db->quoteName('#__tj_reports'));
			$query->where($db->quoteName('plugin') . ' = ' . $db->quote($pluginName));
			$query->order('id ASC');
			$db->setQuery($query);
			$reports[$pluginName] = $db->loadAssocList();
		}

		return $reports[$pluginName];
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
	 *
	 * @since   3.0
	 */
	public function getPluginModel($name = '', $prefix = '', $config = array())
	{
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $name);
		$this->loadLanguage($name, 'tjreports');

		return BaseDatabaseModel::getInstance($name, 'TjreportsModel', $config);
	}

	/**
	 * Get client of the plugin, Must be overidden by plugin if has any client
	 *
	 * @return STRING Client
	 *
	 * @since   2.0
	 * */
	public function getPluginDetail()
	{
		$detail = array('client' => '', 'title' => '');

		return $detail;
	}

	/**
	 * Method to get report plugin of particular type for inter linking
	 *
	 * @param   STRING  $pluginName  Plugin Name
	 *
	 * @return  Object
	 *
	 * @since   3.0
	 */
	public function getPluginInstallationDetail($pluginName)
	{
		static $clients = array();

		if ($pluginName && !isset($clients[$pluginName]))
		{
			$clients[$pluginName] = '';
			$model                = $this->getPluginModel($pluginName);

			if ($model)
			{
				$clients[$pluginName] = $model->getPluginDetail();
			}
		}

		return $clients[$pluginName];
	}

	/**
	 * Execute the tj reports plugin queries
	 *
	 * @return  1
	 */
	public function addTjReportsPlugins()
	{
		$db = Factory::getDbo();
		$query = $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'folder',
						'element',
						'params',
						'extension_id'
					),
					array(
						'type',
						'name',
						'params',
						'id'
					)
				)
			)
			->from('#__extensions')
			->where('enabled = 1')
			->where('type = ' . $db->quote('plugin'))
			->where('folder = ' . $db->quote('tjreports'))
			->where('state IN (0,1)')
			->order('ordering');
		$db->setQuery($query);

		$plugins = $db->loadObjectList();

		$count = 0;

		foreach ($plugins as $plugin)
		{
			$pluginName  = $plugin->name;
			Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');
			$reportTable = Table::getInstance('Tjreport', 'TjreportsTable');
			$details     = $this->getPluginInstallationDetail($pluginName);
			$reportTable->load(array('plugin' => $pluginName, 'userid' => 0));

			if (!$reportTable->id)
			{
				$data            = array();
				$data['title']   = $details['title'];
				$data['plugin']  = $pluginName;
				$data['alias']   = $pluginName;
				$data['client']  = $details['client'];
				$data['parent']  = 0;
				$data['default'] = 1;

				$reportTable->save($data);
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Method to get report plugin of particular type for inter linking
	 *
	 * @param   Int  $userId  User Id
	 *
	 * @return  Array
	 *
	 * @since   1.1.6
	 */
	protected function getUserGroups($userId)
	{
		if (!$userId)
		{
			return array();
		}

		$db = Factory::getDbo();
		$query = $db->getQuery(true);

		// Get the titles for the user groups.
		$query = $db->getQuery(true)
		->select($db->quoteName('ug.id'))
		->select($db->quoteName('ug.title'))
		->from($db->quoteName('#__usergroups', 'ug'))
		->join('INNER', $db->qn('#__user_usergroup_map', 'ugm') . ' ON (' .
			$db->qn('ugm.group_id') . ' = ' . $db->qn('ug.id') . ')')
		->where($db->quoteName('ugm.user_id') . ' = ' . (int) $userId);

		$db->setQuery($query);

		// Set the titles for the user groups.
		return $db->loadAssocList('id', 'title');
	}
}
