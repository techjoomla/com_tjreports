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

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;

/**
 * Tjreportslist Model
 *
 * @since  0.0.1
 */
class TjreportsModelTjreports extends ListModel
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
				'title',
				'plugin',
				'client',
				'savedquery',
				'id',
				'ordering'
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
		$db    = Factory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('tj.*')
			->from($db->quoteName('#__tj_reports', 'tj'));

		$subquery = $db->getQuery(true);
		$subquery->select('count(*)')
			->from($db->quoteName('#__tj_reports', 'stj'))
			->where('stj.parent = tj.id');
		$query->select('(' . $subquery . ') as savedquery');

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
		$query->where($db->quoteName('parent') . ' = 0');

		// Add the list ordering clause.
		$orderCol	= $this->state->get('list.ordering', 'ordering');
		$orderDirn 	= $this->state->get('list.direction', 'desc');
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
		$app = Factory::getApplication();
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
		// Initialise variables.
		$app = Factory::getApplication();

		// Set ordering.
		$orderCol = $app->getUserStateFromRequest($this->context . '.filter_order', 'filter_order');

		$this->setState('list.ordering', $orderCol);

		// Set ordering direction.
		$listOrder = $app->getUserStateFromRequest($this->context . 'filter_order_Dir', 'filter_order_Dir');

		if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', '')))
		{
			$listOrder = 'DESC';
		}

		$this->setState('list.direction', $listOrder);

		// List state information.
		parent::populateState($ordering, $direction);

		// Load the filter state.
		$search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');

		// Omit double (white-)spaces and set state
		$this->setState('filter.search', preg_replace('/\s+/', ' ', $search));

		$plugin = $app->getUserStateFromRequest($this->context . '.filter.plugin', 'filter_plugin', '', 'string');
		$this->setState('filter.plugin', $plugin);

		$client = $app->getUserStateFromRequest($this->context . '.filter.client', 'filter_client', '', 'string');
		$this->setState('filter.client', $client);

		// Bug fix For a list layout
		$jinput = Factory::getApplication()->input;
		$layout = $jinput->get('layout', '', 'STRING');
	}
}
