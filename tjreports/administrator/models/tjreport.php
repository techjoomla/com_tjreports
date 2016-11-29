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
		}

		return $data;
	}

/**
 * Function hit
 *
 * @param   int  $id  id
 *
 * @return  null
 */
	public function hit($id)
	{
		$this->getTable()->hit($id);
	}

/**
 * Function to get all the respective plugins for given client.
 *
 * @return  json  json
 */

	public function getplugins()
	{
		$app = JFactory::getApplication();
		$jinput  = $app->input;
		$client = $jinput->post->get('client', '', 'string');
		$user_id = $jinput->post->get('user_id', '', 'int');

		if (!empty($user_id) && !empty($client))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('r.*,r.id as value,r.title as text');
			$query->from('#__tj_reports as r');
			$query->where('(r.parent = 0  or r.userid = ' . $user_id . ')');
			$query->where('r.id not in ( select `parent` from #__tj_reports as tr where tr.userid=' . $user_id . ')');
			$query->where('r.client like "' . $client . '"');

			$db->setQuery($query);
			$reports = $db->loadObjectList();

			echo json_encode($reports);

			die();
			jexit();
		}
	}

	/**
	 * Function to plugin params
	 *
	 * @return  json json
	 */

	public function getparams()
	{
		$app = JFactory::getApplication();
		$jinput  = $app->input;
		$plugin_id = $jinput->post->get('plugin_id', '', 'int');

		if (!empty($plugin_id))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('r.*');
			$query->from('#__tj_reports as r');
			$query->where('r.id =' . $plugin_id);

			$db->setQuery($query);
			$report = $db->loadObject();

			echo json_encode($report);

			die();
			jexit();
		}
	}
}
