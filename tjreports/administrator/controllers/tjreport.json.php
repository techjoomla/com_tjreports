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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;

/**
 * tjreport Controller
 *
 * @package     Joomla.Administrator
 * @subpackage  com_tjreports
 * @since       0.0.1
 */
class TjreportsControllerTjreport extends FormController
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
		try
		{
			$app     = Factory::getApplication();
			$jinput  = $app->input;
			$jform   = $jinput->post->get('jform', array(), 'ARRAY');
			$client = $jform['client'];
			$userid = isset($jform['userid']) ? $jform['userid'] : 0;
			$id     = $jform['id'];

			$model = $this->getModel('tjreport');
			$reports = $model->getClientPlugins($client, $id, $userid);

			echo new JsonResponse($reports);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}

	/**
	 * Function to get all the respective plugins for given client
	 *
	 * @return  object  object
	 */

	public function getparams()
	{
		try
		{
			$app     = Factory::getApplication();
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

			echo new JsonResponse($report);
		}
		catch (Exception $e)
		{
			echo new JsonResponse($e);
		}
	}
}
