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

/**
 * Databse helper class for com_tjreports
 *
 * @package     TJReports
 * @subpackage  com_tjreports
 *
 * @since       __DEPLOY_VERSION__
 */
class TjreportsfieldsHelperDatabase
{
	/**
	 * Function to check if table exists
	 *
	 * @param   string  $tableName  Table name
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function tableExists($tableName)
	{
		$db        = JFactory::getDbo();
		$dbPrefix  = $db->getPrefix();
		$allTables = $db->getTableList();

		$tableName = str_replace('#__', '', $tableName);

		if (in_array($dbPrefix . $tableName, $allTables))
		{
			return true;
		}

		return false;
	}
}
