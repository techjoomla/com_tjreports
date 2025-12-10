<?php
/**
 * @package     TJReports
 * @subpackage  com_tjreports.tjreportsfields
 *
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Data\DataObject;
use Joomla\Database\Exception\ExecutionFailureException;

/**
 * Class for Tjreportsindexer User Plugin
 *
 * @since  1.1.0
 */
class PlgUserTjreportsindexer extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  3.2.11
	 */
	protected $autoloadLanguage  = true;

	protected $customFieldsTable = '#__tjreports_com_users_user';

	protected $listTypeFields     = array ('checkboxes', 'list', 'radio');

	protected $listTypeCoreFields = array ('user', 'usergrouplist');

	/**
	 * Utility method to act on a user after it has been saved.
	 *
	 * This method sends a registration email to new users created in the backend.
	 *
	 * @param   array    $user     Holds the new user data.
	 * @param   boolean  $isNew    True if a new user is stored.
	 * @param   boolean  $success  True if user was successfully stored in the database.
	 * @param   string   $msg      Message.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onUserAfterSave($user, $isNew, $success, $msg)
	{
		// If com_fields data is not in user data it should return.
		if (!array_key_exists('com_fields', $user))
		{
			return false;
		}

		// Delete existing user-data entry
		// Here record_id = user_id
		$this->deleteIndexerEntry($user['id']);

		// Add new entry
		$this->addIndexerEntry($user);
	}

	/**
	 * Remove all sessions for the user name
	 *
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was successfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	public function onUserAfterDelete($user, $success, $msg)
	{
		if (!$success)
		{
			return false;
		}

		$this->deleteIndexerEntry($user['id']);
	}

	/**
	 * Add Indexer Entry
	 *
	 * @param   array  $user  Holds the user data
	 *
	 * @return  void
	 *
	 * @since  1.1.0
	 */
	protected function addIndexerEntry($user)
	{
		$db = Factory::getDbo();

		// Get column name, type for custom fields index table
		$columnsDetails = $db->getTableColumns($this->customFieldsTable);

		// Extract column names from $columnsDetails
		$columnNames = array_keys($columnsDetails);

		// For all fields get type, fieldparams
		// Register FieldsHelper, Get fields data from current entry
		JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
		$userTableObj = Table::getInstance('User');
		$userTableObj->load((int) $user['id']);
		$fields = FieldsHelper::getFields('com_users.user', $userTableObj, true);

		$fieldsDetails = array();

		// We need array of fields as $fields[$fieldName] format, lets get that
		foreach ($fields as $field)
		{
			$fieldsDetails[$field->name] = $field;
		}

		// Generate new user-data entry
		$columns = array();
		$values  = array();

		// Here record_id = user_id
		$columns[] = $db->quoteName('record_id');
		$values[]  = $db->quote($user['id']);

		foreach ($user['com_fields'] as $key => $val)
		{
			// Only update those fields, which actually exist in our indexer table
			if (!in_array($key, $columnNames))
			{
				continue;
			}

			// If select multiple values save as comma-separated.
			if (is_array($fieldsDetails[$key]->rawvalue))
			{
				$value = implode(', ', $fieldsDetails[$key]->rawvalue);
			}
			else
			{
				$value = $fieldsDetails[$key]->rawvalue;
			}

			$columns[] = $db->quoteName($key);
			$values[]  = $db->quote($value);
		}

		// Add username & email hash values
		array_push($columns, $db->quoteName('username_hash'), $db->quoteName('email_hash'));
		array_push($values, "'" . md5($user['username']) . "'", "'" . md5($user['email']) . "'");

		// Prepare the insert query
		$query = $db->getQuery(true);
		$query
			->insert($db->quoteName($this->customFieldsTable))
			->columns($columns)
			->values(implode(',', $values));

		$db->setQuery($query);

		$db->execute();
	}

	/**
	 * Delete Indexer Entry
	 *
	 * @param   int  $userId  userid
	 *
	 * @return  boolean
	 *
	 * @since  1.1.0
	 */
	protected function deleteIndexerEntry($userId)
	{
		$db = Factory::getDbo();

		// Here record_id = user_id
		$query = $db->getQuery(true)
			->delete($db->quoteName($this->customFieldsTable))
			->where($db->quoteName('record_id') . ' = ' . (int) $userId);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (ExecutionFailureException $e)
		{
			return false;
		}

		return true;
	}
}
