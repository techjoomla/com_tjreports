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
		$app     = JFactory::getApplication();
		$jinput  = $app->input;
		$jform   = $jinput->post->get('jform', array(), 'ARRAY');
		$client = $jform['client'];
		$userid = $jform['userid'];
		$id     = $jform['id'];

		$model = $this->getModel('tjreport');
		$reports = $model->getClientPlugins($client, $id, $userid);

		echo json_encode($reports);

		jexit();
	}

	/**
	 * Function to get all the respective plugins for given client
	 *
	 * @return  object  object
	 */

	public function getparams()
	{
		$app     = JFactory::getApplication();
		$jinput  = $app->input;
		$jform   = $jinput->post->get('jform', array(), 'ARRAY');
		$plugin = $parent = null;
		$default = $jinput->get('default', 0, 'INT');

		if ($default && !empty($jform['plugin']))
		{
			$plugin = $jform['plugin'];
		}
		else
		{
			$parent  = isset($jform['parent']) ? $jform['parent'] : $jform['id'];
		}

		$model   = $this->getModel('tjreport');
		$report  = $model->getReportPluginData($parent, $plugin);

		echo json_encode($report);

		jexit();
	}

/**
	*Set a URL for browser redirection.
	*
	* @param   string  $url   URL to redirect to.
	* @param   string  $msg   Message to display on redirect. Optional, defaults to value set internally by controller, if any.
	* @param   string  $type  Message type. Optional, defaults to 'message' or the type set by a previous call to setMessage.
	*
	* @return  JControllerLegacy  This object to support chaining.
	*/

	public function setRedirect($url, $msg = null,$type = null)
	{
		$extension = JFactory::getApplication()->input->get('extension', '', 'word');

		if ($extension)
		{
			$url .= '&extension=' . $extension;
		}

		parent::setRedirect($url, $msg, $type);
	}
}
