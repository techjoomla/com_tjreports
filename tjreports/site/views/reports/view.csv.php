<?php

/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjreports
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

jimport('techjoomla.view.csv');
require_once JPATH_ROOT . '/components/com_tjreports/models/reports.php';

/**
 * Create your class that extends TjExportCsv class
 *
 * @since  1.0.0
 */
class TjreportsViewReports extends TjExportCsv
{
/**
 * Default display function
 *
 * @param   STRING  $tpl  $tpl
 *
 * @return  null
 */
	public function display($tpl = null)
	{
		parent::display();
	}
}
