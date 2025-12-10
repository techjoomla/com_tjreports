<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

/**
 * com tjreports Controller
 *
 * @since  0.0.1
 */
class TjreportsControllerTjreports extends AdminController
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Tjreport', $prefix = 'TjreportsModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

/**
	* Set a URL for browser redirection.
	*
	* @param   string  $url   URL to redirect to.
	* @param   string  $msg   Message to display on redirect. Optional, defaults to value set internally by controller, if any.
	* @param   string  $type  Message type. Optional, defaults to 'message' or the type set by a previous call to setMessage.
	*
	* @return  JControllerLegacy  This object to support chaining.
	*
	* @since   12.2
	*/
	public function setRedirect($url, $msg = null,$type = null)
	{
		$extension = Factory::getApplication()->input->get('extension', '', 'word');

		if ($extension)
		{
			$url .= '&extension=' . $extension;
		}

		parent::setRedirect($url, $msg, $type);
	}

	/**
	 * Discover installed plugins
	 *
	 * @return  JModel
	 *
	 * @since   1.6
	 */
	public function discover()
	{
		BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjreports/models');
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');

		$db    = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('element');
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('type') . "=" . $db->quote('plugin'));
		$query->where($db->quoteName('folder') . "=" . $db->quote('tjreports'));
		$db->setQuery($query);
		$allPlugins = $db->loadColumn();

		$query = $db->getQuery(true);
		$query->select('plugin');
		$query->from($db->quoteName('#__tj_reports'));
		$db->setQuery($query);
		$tjReportsPlugins = $db->loadColumn();

		$discoverPlugins = array_diff($allPlugins, $tjReportsPlugins);

		$count = 0;

		foreach ($discoverPlugins as $value)
		{
			$model = BaseDatabaseModel::getInstance('Reports', 'TjreportsModel');
			$pluginName = $value;
			$reportTable = Table::getInstance('Tjreport', 'TjreportsTable');
			$details = $model->getPluginInstallationDetail($pluginName);
			$reportTable->load(array('plugin' => $pluginName, 'userid' => 0));

			if (!$reportTable->id)
			{
				$data = array();
				$data['title']  = $details['title'];
				$data['plugin']  = $pluginName;
				$data['alias']  = $pluginName;
				$data['client']  = $details['client'];
				$data['parent']  = 0;
				$data['default']  = 1;

				$reportTable->save($data);
				$count++;
			}
		}

		$message = Text::_('COM_TJREPORTS_NOTHING_TO_DISCOVER_PLUGINS');

		if ($count > 0)
		{
			$message = Text::sprintf(Text::_('COM_TJREPORTS_DISCOVER_NEW_PLUGINS'), $count);
		}

		$this->setRedirect('index.php?option=com_tjreports', $message);
	}
}
