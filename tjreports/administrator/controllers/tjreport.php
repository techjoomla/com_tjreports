<?php
/**
 * @package     Joomla.site
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');
/**
 * tjreport Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_tjreports
 * @since       0.0.1
 */
class TjreportsControllerTjreport extends JControllerForm
{
	/**
	 * Contructor
	 */

	public function __construct()
	{
		$this->view_list = 'tjreports';
		parent::__construct();
	}

	/**
	 * Function to get all the respective plugins for given client
	 *
	 * @return  object  object
	 */
	public function getplugins()
	{
		$model = $this->getModel('tjreport');
		$result = $model->getplugins();
	}

	/**
	 * Function to get all the respective plugins for given client
	 *
	 * @return  object  object
	 */

	public function getparams()
	{
		$model = $this->getModel('tjreport');
		$result = $model->getparams();
	}
}
