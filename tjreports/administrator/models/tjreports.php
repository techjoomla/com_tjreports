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
 * Tjreportslist Model
 *
 * @since  0.0.1
 */
class TjreportsModelTjreports extends JModelList
{
	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JController
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'plugin',
				'client'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return      string  An SQL query
	 */
	protected function getListQuery()
	{
		// Initialize variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('*')
			->from($db->quoteName('#__tj_reports'));

		// Filter by Plugin
		$plugin = $this->getState('filter.plugin');
		$client = $this->getState('filter.client');

		if ($plugin)
		{
			$plugin = $db->quote($plugin);
			$query->where('plugin = ' . $plugin);
		}

		if ($client)
		{
			$client = $db->quote($client);
			$query->where('client = ' . $client);
		}

		// Filter: like / search
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			$like = $db->quote('%' . $search . '%');
			$query->where('title LIKE ' . $like . 'or plugin LIKE ' . $like . 'or client LIKE' . $like);
		}

		// $query->where("id not in(select `parent` from `#__tj_reports` where `default`=1)");
		$query->where('`default` = 1');

		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering', 'title');
		$orderDirn 	= $this->state->get('list.direction', 'asc');
		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

		return $query;
	}

	/**
	 * Method to get a list of users.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6.1
	 */
	public function getItems()
	{
		$db = $this->getDbo();
		$app = JFactory::getApplication();
		$input = $app->input;
		$items = parent::getItems();

		return $items;
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('a.id', 'asc');

		// Initialise variables.
		$app = JFactory::getApplication();
		$client = JFactory::getApplication()->input->get('extension', '', 'word');

		$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'uint');
		$this->setState('list.limit', $limit);

		$limitstart = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $limitstart);

		$limitstart = $app->input->get('limitstart', 0, 'uint');

		$this->setState('list.start', $limitstart);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');

		// Omit double (white-)spaces and set state
		$this->setState('filter.search', preg_replace('/\s+/', ' ', $search));

		$plugin = $app->getUserStateFromRequest($this->context . '.filter.plugin', 'filter_plugin', '', 'string');
		$this->setState('filter.plugin', $plugin);

		$client = $app->getUserStateFromRequest($this->context . '.filter.client', 'filter_client', '', 'string');
		$this->setState('filter.client', $client);

		// Ordering by name
		$ordering = $app->getUserStateFromRequest($this->context . '.list.ordering', 'filter_order');
		$direction = $app->getUserStateFromRequest($this->context . '.list.direction', 'filter_order_Dir');

		// Bug fix For a list layout
		$jinput = JFactory::getApplication()->input;
		$layout = $jinput->get('layout', '', 'STRING');

		if ((empty($ordering)) || ((!empty($ordering)) && ($jinput->get('profile', '', 'INT') == 2) && ($jinput->get('layout', '', 'STRING') == 'alist')))
		{
			$ordering  = "title";
		}

		if (empty($direction))
		{
			$direction = "asc";
		}

		$this->setState('list.ordering', preg_replace('/\s+/', ' ', $ordering));
		$this->setState('list.direction', preg_replace('/\s+/', ' ', $direction));
	}
}
