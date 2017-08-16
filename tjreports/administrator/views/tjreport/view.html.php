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
 * tjreport View
 *
 * @since  0.0.1
 */
class TjreportsViewTjreport extends JViewLegacy
{
	/**
	 * View form
	 *
	 * @var         form
	 */
	protected $form = null;
	/**
	 * Display the tjreport  view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		// Get the Data
		$form = $this->get('Form');

		$item = $this->get('Item');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));

			return false;
		}

		// Assign the Data
		$this->form = $form;
		$this->item = $item;

		$input = JFactory::getApplication()->input;
		$extension = $input->get('extension', '', 'STRING');

		$this->addToolBar();
		$this->addDocumentHeaderData();

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
		$input = JFactory::getApplication()->input;

		// Hide Joomla Administrator Main menu
		$input->set('hidemainmenu', true);
		$isNew = ($this->item->id == 0);

		if ($isNew)
		{
			$title = JText::_('COM_TJREPORTS_NEW');
		}
		else
		{
			$title = JText::_('COM_TJREPORTS_EDIT');
		}

		JToolBarHelper::title($title, 'tjreport');
		JToolBarHelper::apply('tjreport.apply');
		JToolBarHelper::save('tjreport.save');
		JToolBarHelper::cancel(
			'tjreport.cancel',
			$isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE'
		);
	}

	/**
	 * Add the script and Style.
	 *
	 * @return  Void
	 *
	 * @since	1.6
	 */
	protected function addDocumentHeaderData()
	{
		JText::script('COM_TJREPORTS_FORM_DEFAULT_OPTION');
		JText::script('COM_TJREPORTS_INVALID_JSON_VALUE');
		$document = JFactory::getDocument();
		$document->addScript(JURI::root() . '/components/com_tjreports/assets/js/tjrContentService.js');
		$document->addScript(JURI::root() . '/components/com_tjreports/assets/js/tjrContentUI.js');
		$document->addStylesheet(JURI::root() . '/components/com_tjreports/assets/css/tjreports.css');
		$document->addScriptDeclaration('tjrContentUI.base_url = "' . Juri::base() . '"');
		$document->addScriptDeclaration('tjrContentUI.root_url = "' . Juri::root() . '"');
	}
}
