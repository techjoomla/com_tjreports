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
/**
 * com tjreports Controller
 *
 * @since  0.0.1
 */
class TjreportsControllerTjreports extends JControllerAdmin
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
		$extension = JFactory::getApplication()->input->get('extension', '', 'word');

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
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('element');
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('type') . "=" . $db->quote('plugin'));
		$query->where($db->quoteName('folder') . "=" . $db->quote('tjreports'));
		$db->setQuery($query);
		$allPlugins = $db->loadObjectList();

		$query = $db->getQuery(true);
		$query->select('plugin', 'element');
		$query->from($db->quoteName('#__tj_reports'));
		$db->setQuery($query);
		$tjReportsPlugins = $db->loadObjectList();

		$json  = json_encode($allPlugins);
		$array = json_decode($json, true);

		$json1  = json_encode($tjReportsPlugins);
		$array1 = json_decode($json1, true);

		$intalledPlugins  = array_column($array, 'element');
		$tjreportsPlugings = array_column($array1, 'plugin');

		$diff  = array_diff($intalledPlugins, $tjreportsPlugings);
		$count = 0;

		foreach ($diff as $value)
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tjreports/models');
			$model = JModelLegacy::getInstance('Reports', 'TjreportsModel');
			$pluginName = $value;
			JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');
			$reportTable = JTable::getInstance('Tjreport', 'TjreportsTable');
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

		$this->setRedirect('index.php?option=com_tjreports');
	}
}
