<?php
/**
 * @package     TJReports
 * @subpackage  com_tjreports
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access.
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

// Load TJReports db helper
JLoader::import('database', JPATH_SITE . '/components/com_tjreports/helpers');

/**
 * TJReports Indexer Model Class
 *
 * @package     TJReports
 * @subpackage  com_tjreports
 *
 * @since       1.1.0
 */
class TjreportsModelIndexer extends BaseDatabaseModel
{
	protected $customFieldsTable = '';

	/**
	 * Function to create indexer table for given context
	 *
	 * @param   string  $context  Context
	 *
	 * @return  boolean
	 *
	 * @since   1.1.0
	 */
	public function createTable($context)
	{
		if (empty($context))
		{
			return false;
		}

		// Set table name as #__tjreports_context eg: #__tjreports_com_users_user
		$context                 = str_replace('.', '_', trim($context));
		$this->customFieldsTable = '#__tjreports_' . $context;

		// If no table, return
		$tjreportsDbHelper = new TjreportsfieldsHelperDatabase;

		try
		{
			if ($tjreportsDbHelper->tableExists($this->customFieldsTable))
			{
				throw new Exception('Error creating DB table - Table exists already');

				return false;
			}

			// Decide primary key name

			/*
			$pKey          = 'tjr_';
			$contextArray = explode('_', $context);

			foreach ($contextArray as $v)
			{
				$pKey .= substr($v, 0, 1);
			}

			$pKey .= '_id';*/

			// Create table
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			/*$query = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($this->customFieldsTable) . ' ( ' .
				$db->quoteName($pKey) . ' int(11) NOT NULL AUTO_INCREMENT,
				`record_id` int(11) NOT NULL,
				PRIMARY KEY (' . $db->quoteName($pKey) . ')
			) ENGINE=InnoDB DEFAULT CHARSET=utf8';*/

			$query = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($this->customFieldsTable) . ' (
				`record_id` int(11) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8';

			$db->setQuery($query);

			if ($db->execute())
			{
				$msg = 'DB table - ' . $db->quoteName($this->customFieldsTable) . ' created successfully';
				echo new JResponseJson($msg);

				return true;
			}
			else
			{
				throw new Exception('Error creating DB table - ' . $db->quoteName($this->customFieldsTable));

				return false;
			}
		}
		catch (Exception $e)
		{
			echo new JResponseJson(null, $e->getMessage(), true);
		}
	}
}
