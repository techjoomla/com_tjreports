<?php
/**
 * @package     Joomla.Administator
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;
/**
 * jticketing Model
 *
 * @since  0.0.1
 */
class TjreportsModelTjreport extends JModelAdmin
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.6
	 */
	public function getTable($type = 'Tjreport', $prefix = 'TjreportsTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_tjreports.tjreport',
			'tjreport',
			array(
				'control' => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState(
			'com_tjreports.edit.tjreports.data',
			array()
		);

		if (empty($data))
		{
			$data = $this->getItem();

			if (!empty($data->param))
			{
				$data->param = json_encode(json_decode($data->param), JSON_PRETTY_PRINT);
			}
		}

		return $data;
	}

	/**
	 * Method to get the Plugins.
	 *
	 * @param   STRING  $client     Client Name
	 * @param   INT     $currentId  Current Plugin ID
	 * @param   INT     $userId     User Id
	 *
	 * @return  mixed   Plugin details
	 *
	 * @since   1.6
	 */
	public function getClientPlugins($client, $currentId = 0, $userId = 0)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('r.*,r.id as value,r.title as text');
		$query->from('#__tj_reports as r');
		$query->where('r.client = ' . $db->quote($client));
		$query->where('r.id <> ' . (int) $currentId);
		$query->where('r.parent  = 0');
		$query->where('r.default = 1');

		// Need to confirm reason for this
		if ($userId)
		{
			$query->where('r.id not in ( select `parent` from #__tj_reports as tr where tr.userid=' . (int) $userId . ')');
		}

		$db->setQuery($query);
		$reports = $db->loadObjectList();

		return $reports;
	}

	/**
	 * Method to get the Plugins.
	 *
	 * @param   INT  $pluginId    Current Plugin ID
	 * @param   INT  $pluginName  Get default param of plugin
	 *
	 * @return  mixed   Plugin details
	 *
	 * @since   1.6
	 */
	public function getReportPluginData($pluginId, $pluginName = null)
	{
		if ($pluginId)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('r.*');
			$query->from('#__tj_reports as r');
			$query->where('r.id =' . (int) $pluginId);

			$db->setQuery($query);
			$report = $db->loadObject();
			$pluginName = $report->plugin;
		}
		elseif ($pluginName)
		{
			$report = new stdClass;
			$report->plugin = $pluginName;
		}

		if ($pluginName || (empty($report->param) && !empty($report->plugin)))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/plugins/tjreports/' . $pluginName);
			$plgModel = JModelLegacy::getInstance($pluginName, 'TjreportsModel');

			$params = array();
			$params['filter_order']     = $plgModel->getState('list.ordering');
			$params['filter_order_Dir'] = $plgModel->getState('list.direction');
			$params['limit']            = $plgModel->getState('list.limit');
			$params['colToshow']        = $plgModel->getState('colToshow');
			$params['colToshow']        = array_combine($params['colToshow'], array_fill(0, count($params['colToshow']), true));
			$params['showHideColumns']  = $plgModel->showhideCols;

			$report->param = json_encode($params);
		}

		return $report;
	}
}
