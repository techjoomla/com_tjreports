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
		// Get data from the model
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->state		= $this->get('State');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');

		// Check for errors.

	/*
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
	*/

		// TjreportsHelper::addSubmenu('tjreports');

		// Set the tool-bar and number of found items
		$this->addToolBar();
		$this->sidebar = JHtmlSidebar::render();

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
		$state = $this->get('State');
		$canDo = TjreportsHelper::getActions();

		$name = JText::_('COM_TJREPORTS');

		JToolBarHelper::title($name, 'tjreport');
		JToolBarHelper::deleteList('', 'tjreports.delete');
		JToolBarHelper::addNew('tjreport.add');
		JToolBarHelper::preferences('com_tjreports');
	}
}
