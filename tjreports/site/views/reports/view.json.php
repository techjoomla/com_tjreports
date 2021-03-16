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

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

require_once __DIR__ . '/view.base.php';

/**
 * View class for a list of Tjreports.
 *
 * @since  1.0.0
 */
class TjreportsViewReports extends ReportsViewBase
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$input = Factory::getApplication()->input;
		$tpl = $input->get('tpl', 'default', 'string');
		$tpl = ($tpl == 'default' || $tpl == 'submit') ? null : $tpl;

		$result = $this->processData('json');

		if (!$result)
		{
			return false;
		}

		$output = array();
		$output['html'] = parent::loadTemplate($tpl);

		echo json_encode($output);
	}
}
