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
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * TJReports Indexer Controller Class
 *
 * @package     TJReports
 * @subpackage  com_tjreports
 *
 * @since       1.1.0
 */
class TjreportsControllerIndexer extends BaseController
{
	/**
	 * Function to get all the respective plugins for given client
	 *
	 * @return  object  object
	 */
	public function createTable()
	{
		$app = Factory::getApplication();

		if ($app->isClient("site"))
		{
			echo 'Error creating DB table - Need to run this in admin area';

			return;
		}

		$user = Factory::getUser();

		if (!$user->authorise('core.admin'))
		{
			echo 'Error creating DB table - You need to be superadmin user to exeacute this task';

			return;
		}

		$jinput  = $app->input;
		$context = $jinput->get->get('context', '', 'cmd');

		if (empty($context))
		{
			echo 'Error creating DB table - No context is passed';

			return;
		}

		$model = $this->getModel('indexer');
		$model->createTable($context);
	}
}
