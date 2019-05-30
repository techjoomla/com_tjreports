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

/**
 * Class for Tjreportsindexer User Plugin
 *
 * @since  1.0.0
 */
class PlgUserTjreportsindexer extends JPlugin
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
		/*
		  [com_fields] => Array
		(
			[job-position] => Developer
		)*/

		if (empty($user['com_fields']))
		{
			return;
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
	 */
	protected function addIndexerEntry($user)
	{
		$db = JFactory::getDbo();

		// Get column name, type for custom fields index table
		$columnsDetails = $db->getTableColumns($this->customFieldsTable);

		// Extract column names from $columnDetails
		$columnNames = array();

		foreach ($columnsDetails as $key => $val)
		{
			$columnNames[] = $key;
		}

		// Get field names
		$fieldsPosted = array_keys($user['com_fields']);

		// For all fields get type, fieldparams
		$query = $db->getQuery(true);

		// Prepare the insert query.
		$query
			->select(array($db->quoteName('name'), $db->quoteName('type'), $db->quoteName('fieldparams')))
			->from($db->quoteName('#__fields'))
			->where($db->quoteName('name') . ' IN (' . implode(',', $db->quote($fieldsPosted)) . ')');
		$db->setQuery($query);

		$fieldsDetails = $db->loadAssocList('name');

		// Add new user-data entry
		$query = $db->getQuery(true);

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

			// For below types save text instead of values
			// Checkboxes / list / radio / user / usergrouplist
			if (in_array($fieldsDetails[$key]['type'], $this->listTypeFields))
			{
				$fieldParams = new JRegistry($fieldsDetails[$key]['fieldparams']);
				$data = array();

				foreach ($fieldParams->get('options', array()) as $option)
				{
					$op               = (object) $option;
					$data[$op->value] = $op->name;
				}

				// Checkboxes, select with multiple
				if (is_array($val) && count($val))
				{
					$tempVal = array();

					foreach ($val as $v)
					{
						$tempVal[] = $data[$v];
					}

					$val = $tempVal;
				}
				else
				{
					$val = $data[$val];
				}
			}
			elseif (in_array($fieldsDetails[$key]['type'], $this->listTypeCoreFields))
			{
				if (is_array($val) && count($val))
				{
					$tempVal = array();

					foreach ($val as $v)
					{
						if ($fieldsDetails[$key]['type'] == 'user')
						{
							$userTable = JTable::getInstance('User');
							$userTable->load((int) $val);

							// @echo '<br/> username is <br/>' . $tempVal[] = $userTable->name;
						}
						elseif ($fieldsDetails[$key]['type'] == 'usergrouplist')
						{
							$userGroupTable = JTable::getInstance('Usergroup');
							$userGroupTable->load(array('id' => (int) $v));

							// @echo '<br/> group title is <br/>' . $tempVal[] = $userGroupTable->title;
						}
					}

					$val = $tempVal;
				}
				else
				{
					if ($fieldsDetails[$key]['type'] == 'user')
					{
						$userTable = JTable::getInstance('User');
						$userTable->load((int) $val);

						$val = $userTable->name;
					}
					elseif ($fieldsDetails[$key]['type'] == 'usergrouplist')
					{
						$userGroupTable = JTable::getInstance('Usergroup');
						$userGroupTable->load(array('id' => (int) $val));

						$val = $userGroupTable->title;
					}
				}
			}

			// Checkboxes, select with multiple
			if (is_array($val) && count($val))
			{
				$val = implode(',', $val);
			}

			$columns[] = $db->quoteName($key);
			$values[]  = $db->quote($val);
		}

		// Prepare the insert query.
		$query
			->insert($db->quoteName($this->customFieldsTable))
			->columns($columns)
			->values(implode(',', $values));

		$db->setQuery($query);

		// @echo $query->dump(); die;

		$db->execute();
	}

	/**
	 * Delete Indexer Entry
	 *
	 * @param   int  $userId  userid
	 *
	 * @return  boolean
	 */
	protected function deleteIndexerEntry($userId)
	{
		$db = JFactory::getDbo();

		// Here record_id = user_id
		$query = $db->getQuery(true)
			->delete($db->quoteName($this->customFieldsTable))
			->where($db->quoteName('record_id') . ' = ' . (int) $userId);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (JDatabaseExceptionExecuting $e)
		{
			return false;
		}

		return true;
	}
}
