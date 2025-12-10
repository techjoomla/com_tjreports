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

use Joomla\CMS\Response\JsonResponse;
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

	private $whiteListedContexts = array ('com_users.user');

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

		try
		{
			if (!in_array($context, $this->whiteListedContexts))
			{
				throw new Exception('Context not allowed for creating DB table');
			}

			// Set table name as #__tjreports_context eg: #__tjreports_com_users_user
			$context                 = str_replace('.', '_', trim($context));
			$this->customFieldsTable = '#__tjreports_' . $context;

			// If no table, return
			$tjreportsDbHelper = new TjreportsfieldsHelperDatabase;

			if ($tjreportsDbHelper->tableExists($this->customFieldsTable))
			{
				throw new Exception('Error creating DB table - Table exists already');
			}

			// Create table
			$db    = Factory::getDbo();
			$query = 'CREATE TABLE IF NOT EXISTS ' . $db->quoteName($this->customFieldsTable) . ' (
				`record_id` int(11) NOT NULL, KEY `record_id` (`record_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8';

			$db->setQuery($query);

			if ($db->execute())
			{
				$msg = 'DB table - ' . $db->quoteName($this->customFieldsTable) . ' created successfully';
				echo new JsonResponse($msg);

				return true;
			}
			else
			{
				throw new Exception('Error creating DB table - ' . $db->quoteName($this->customFieldsTable));
			}
		}
		catch (Exception $e)
		{
			echo new JsonResponse(null, $e->getMessage(), true);
		}
	}
}
