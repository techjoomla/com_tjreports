<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;
require_once JPATH_COMPONENT . '/helpers/tjreports.php';
/**
 * HelloWorlds View
 *
 * @since  0.0.1
 */
class TjreportsViewTjreports extends JViewLegacy
{
	/**
	 * Display the Tjreports view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		$this->canDo = TjreportsHelper::getActions();

		if (!$this->canDo->get('core.view'))
		{
			JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		// Get data from the model
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->filterForm    = $this->get('FilterForm');

		// Initialise variables.
		$app = JFactory::getApplication('administrator');

		// Get extension name
		$client = JFactory::getApplication()->input->get('client', '', 'word');

		if ($client)
		{
			TjreportsHelper::addSubmenu('tjreports');
			$this->sidebar = JHtmlSidebar::render();
		}

		// Set the tool-bar and number of found items
		$this->addToolBar();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	protected function addToolBar()
	{
		$name = JText::_('COM_TJREPORTS');

		JToolBarHelper::title($name, 'list');

		if ($this->canDo->get('core.create'))
		{
			JToolBarHelper::addNew('tjreport.add');
		}

		if ($this->canDo->get('core.edit'))
		{
			JToolBarHelper::editList('tjreport.edit');
		}

		if ($this->canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('', 'tjreports.delete');
		}

		if ($this->canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_tjreports');
		}

		if ($this->canDo->get('core.create'))
		{
			JToolBarHelper::custom('tjreports.discover', 'refresh', 'refresh', 'JLIB_INSTALLER_DISCOVER', false);
		}
	}
}
