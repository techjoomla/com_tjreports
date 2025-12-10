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

Use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

require_once JPATH_COMPONENT . '/helpers/tjreports.php';

/**
 * tjreport View
 *
 * @since  0.0.1
 */
class TjreportsViewTjreport extends HtmlView
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
		$this->canDo = TjreportsHelper::getActions();
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode('<br />', $errors), 500);

			return false;
		}

		$input = Factory::getApplication()->input;
		$extension = $input->get('extension', '', 'STRING');

		$this->addToolbar();
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
	protected function addToolbar()
	{
		$input = Factory::getApplication()->input;

		// Hide Joomla Administrator Main menu
		$input->set('hidemainmenu', true);
		$isNew = ($this->item->id == 0);

		if ($isNew)
		{
			$title = Text::_('COM_TJREPORTS_NEW');
		}
		else
		{
			$title = Text::_('COM_TJREPORTS_EDIT');
		}

		ToolbarHelper::title($title, 'tjreport');
		ToolbarHelper::apply('tjreport.apply');
		ToolbarHelper::save('tjreport.save');
		ToolbarHelper::cancel(
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
		HTMLHelper::_('jquery.framework');
		Text::script('COM_TJREPORTS_FORM_DEFAULT_OPTION');
		Text::script('COM_TJREPORTS_INVALID_JSON_VALUE');

		$document = Factory::getDocument();

		HTMLHelper::_('script', 'com_tjreports/tjrContentService.min.js', array("relative" => true));
		HTMLHelper::_('script', 'com_tjreports/tjrContentUI.min.js', array("relative" => true));
		HTMLHelper::_('stylesheet', 'components/com_tjreports/assets/css/tjreports.min.css');

		$document->addScriptDeclaration('tjrContentUI.base_url = "' . Uri::base() . '"');
		$document->addScriptDeclaration('tjrContentUI.root_url = "' . Uri::root() . '"');
	}
}
