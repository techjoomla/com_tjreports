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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

require_once JPATH_COMPONENT . '/helpers/tjreports.php';

/**
 * HelloWorlds View
 *
 * @since  0.0.1
 */
class TjreportsViewTjreports extends HtmlView
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
			throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);

			return false;
		}

		// Get data from the model
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->filterForm    = $this->get('FilterForm');

		// Initialise variables.
		$app = Factory::getApplication('administrator');

		// Get extension name
		$client = Factory::getApplication()->input->get('client', '', 'word');

		if ($client)
		{
			TjreportsHelper::addSubmenu('tjreports');
			$this->sidebar = '';;
		}

		// Set the tool-bar and number of found items
		$this->addToolbar();

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
	protected function addToolbar()
	{
		$name = Text::_('COM_TJREPORTS');

		ToolbarHelper::title($name, 'list');

		if ($this->canDo->get('core.create'))
		{
			ToolbarHelper::addNew('tjreport.add');
		}

		if ($this->canDo->get('core.edit'))
		{
			ToolbarHelper::editList('tjreport.edit');
		}

		if ($this->canDo->get('core.delete'))
		{
			ToolbarHelper::deleteList('', 'tjreports.delete');
		}

		if ($this->canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_tjreports');
		}

		if ($this->canDo->get('core.create'))
		{
			ToolbarHelper::custom('tjreports.discover', 'refresh', 'refresh', 'JLIB_INSTALLER_DISCOVER', false);
		}
	}
}
