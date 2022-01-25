<?php
/**
 * @package     TJReports
 * @subpackage  com_tjreports.tjreportsfields
 *
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;

// Load TJReports db helper
JLoader::import('database', JPATH_SITE . '/components/com_tjreports/helpers');

/**
 * Class for Tjreportsfields Content Plugin
 *
 * @since  1.1.0
 */
class PlgContentTjreportsfields extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.1.0
	 */
	protected $autoloadLanguage = true;

	protected $customFieldsTable;

	protected $customFieldBeingEdited;

	protected $unSupportedFields = array ('imagelist', 'media', 'repeatable');

	protected $fieldTypeToColumnTypeMapping = array (
		'calendar'      => 'datetime',
		'checkboxes'    => 'varchar(255)',
		'color'         => 'varchar(7)',
		'editor'        => 'text',
		'integer'       => 'int(11)',
		'list'          => 'varchar(255)',
		'radio'         => 'varchar(255)',
		'sql'           => 'text',
		'text'          => 'text',
		'textarea'      => 'text',
		'url'           => 'varchar(250)',
		'user'          => 'varchar(400)',
		'usergrouplist' => 'varchar(100)'
	);

	protected $tjreportsDbHelper;

	/**
	 * Constructor
	 *
	 * @param   string  &$subject  subject
	 *
	 * @param   string  $config    config
	 */
	public function __construct(&$subject, $config)
	{
		$this->tjreportsDbHelper = new TjreportsfieldsHelperDatabase;

		parent::__construct($subject, $config);
	}

	/**
	 * Smart Search before content save method.
	 * This event is fired before the data is actually saved.
	 *
	 * @param   string   $context  The context of the content passed to the plugin.
	 * @param   JTable   $row      A JTable object.
	 * @param   boolean  $isNew    If the content is just about to be created.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.1.0
	 * @throws  Exception on database error.
	 */
	public function onContentBeforeSave($context, $row, $isNew)
	{
		// Run this plugin only for com_fields.field form
		if ($context !== 'com_fields.field')
		{
			return;
		}

		// $context = com_fields.field

		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/tables');
		$fieldsTable = Table::getInstance('Field', 'FieldsTable');
		$fieldsTable->load(array('id' => $row->id));

		// Set an array with field id, field name, so it can be used in onContentAfterSave trigger
		// Array ( [3] => dob )
		$this->customFieldBeingEdited       = new stdclass;
		$this->customFieldBeingEdited->id   = $row->id;
		$this->customFieldBeingEdited->name = $fieldsTable->name;
		$this->customFieldBeingEdited->type = $fieldsTable->type;

		return true;
	}

	/**
	 * Smart Search after save content method.
	 * Content is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved.
	 *
	 * @param   string  $context  The context of the content passed to the plugin (added in 1.6)
	 * @param   object  $field    A JTableField object
	 * @param   bool    $isNew    If the content has just been created
	 *
	 * @return  void
	 *
	 * @since   1.1.0
	 */
	public function onContentAfterSave($context, $field, $isNew)
	{
		// Run this plugin only for com_fields.field form
		if ($context !== 'com_fields.field')
		{
			return;
		}

		/*
		[id] => 2
		[context] => com_users.user
		[group_id] => 1
		[title] => Professional Details - Job Start Year
		[name] => job-start-year
		[label] => Professional Details - Job Start Year
		[default_value] =>*/

		// Skip unsupported field
		if (in_array($field->type, $this->unSupportedFields))
		{
			return;
		}

		// Call set custom field table name
		$this->setCustomFieldsTableName($field->context);

		// If no table, return
		if (!$this->tjreportsDbHelper->tableExists($this->customFieldsTable))
		{
			return;
		}

		// Get column name, type for custom fields index table
		$db             = Factory::getDbo();
		$columnsDetails = $db->getTableColumns($this->customFieldsTable);

		// Extract column names from $columnDetails
		$columnNames = array_keys($columnsDetails);

		$oldColumnName = $this->customFieldBeingEdited->name;
		$newColumnName = $field->name;

		// If current being edited already exits in indexed DB
		if (in_array($oldColumnName, $columnNames))
		{
			// If current field name is changed, update column name in DB table
			if ($newColumnName !== $oldColumnName)
			{
				$this->updateColumn($oldColumnName, $newColumnName);
			}
		}
		// Add new column
		else
		{
			$this->addColumn($newColumnName);
		}
	}

	/**
	 * After delete content logging method
	 * This method adds a record to #__action_logs contains (message, date, context, user)
	 * Method is called right after the content is deleted
	 *
	 * @param   string  $context  The context of the content passed to the plugin
	 * @param   object  $field    A JTableField object
	 *
	 * @return  void
	 *
	 * @since   1.1.0
	 */
	public function onContentAfterDelete($context, $field)
	{
		// Run this plugin only for com_fields.field form
		if ($context !== 'com_fields.field')
		{
			return;
		}

		// Call set custom field table name
		$this->setCustomFieldsTableName($field->context);

		// If no table, return
		if (!$this->tjreportsDbHelper->tableExists($this->customFieldsTable))
		{
			return;
		}

		if (!in_array($field->type, $this->unSupportedFields))
		{
			$this->deleteColumn($field->name);
		}
	}

	/**
	 * Function to set custom fields table name
	 *
	 * @param   string  $context  Context name eg. com_users.user
	 *
	 * @return  void
	 *
	 * @since   1.1.0
	 */
	protected function setCustomFieldsTableName($context)
	{
		// Set table name as #__tjreports_context eg: #__tjreports_com_users_user
		$this->customFieldsTable = '#__tjreports_' . str_replace('.', '_', trim($context));
	}

	/**
	 * Add Column
	 *
	 * @param   string  $newColumn  Column name to be addd
	 *
	 * @return  void
	 *
	 * @since  1.1.0
	 */
	protected function addColumn($newColumn)
	{
		$db = Factory::getDbo();

		// ALTER TABLE tableName ADD newColumn dataType
		// eg. ALTER TABLE `#__tjreports_com_users_user` ADD `dob` datetime
		$query = 'ALTER TABLE ' . $db->quoteName($this->customFieldsTable) . '
		 ADD ' . $db->quoteName($newColumn) . ' ' . $this->fieldTypeToColumnTypeMapping[$this->customFieldBeingEdited->type];

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Update Column
	 *
	 * @param   string  $oldColumn  Old column name
	 * @param   string  $newColumn  New column name
	 *
	 * @return  void
	 *
	 * @since  1.1.0
	 */
	protected function updateColumn($oldColumn, $newColumn)
	{
		$db = Factory::getDbo();

		// ALTER TABLE tableName CHANGE oldColumn newColumn dataType
		$query = 'ALTER TABLE ' . $db->quoteName($this->customFieldsTable) . '
		 CHANGE ' . $db->quoteName($oldColumn) . '
		 ' . $db->quoteName($newColumn) . ' ' . $this->fieldTypeToColumnTypeMapping[$this->customFieldBeingEdited->type];

		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Delete Column
	 *
	 * @param   string  $column  Column name to be deleted
	 *
	 * @return  void
	 *
	 * @since  1.1.0
	 */
	protected function deleteColumn($column)
	{
		$db = Factory::getDbo();

		// ALTER TABLE tableName CHANGE oldColumn newColumn dataType
		$query = 'ALTER TABLE ' . $db->quoteName($this->customFieldsTable) . '
		 DROP ' . $db->quoteName($column);

		$db->setQuery($query);
		$db->execute();
	}
}
