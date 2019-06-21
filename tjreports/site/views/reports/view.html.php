<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Tjreports
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access
defined('_JEXEC') or die;

require_once __DIR__ . '/view.base.php';
jimport('techjoomla.tjtoolbar.button.csvexport');

/**
 * View class for a list of Tjreports.
 *
 * @since  1.0.0
 */
class TjreportsViewReports extends ReportsViewBase
{
	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a Error object.
	 */
	public function display($tpl = null)
	{
		$input  = JFactory::getApplication()->input;
		$result = $this->processData();

		if (!$result)
		{
			return false;
		}

		$this->addToolbar();
		$this->addDocumentHeaderData();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  Toolbar instance
	 *
	 * @since	1.6
	 */
	protected function addToolbar()
	{
		$app                  = JFactory::getApplication();
		$reportId             = $app->getUserStateFromRequest('reportId', 'reportId', '');
		$user                 = JFactory::getUser();
		$userAuthorisedExport = $user->authorise('core.export', 'com_tjreports.tjreport.' . $reportId);
		$bar                  = JToolBar::getInstance('toolbar');
		$canDo                = TjreportsHelper::getActions();

		if ($app->isAdmin())
		{
			$title = JText::_('COM_TJREPORTS_TITLE_REPORT');

			if (isset($this->reportData->title))
			{
				$title = $title . ' - ' . $this->reportData->title;
			}

			JToolBarHelper::title($title, 'list');
		}
		else
		{
			$this->params = &$this->state->params;
			$menus = $app->getMenu();
			$title = null;

			$menu = $menus->getActive();

			if ($menu)
			{
				$title = $menu->title;
			}
			else
			{
				$title = $app->get('sitename');
			}

			if (isset($this->reportData->title))
			{
				$title = $title . ' - ' . $this->reportData->title;
			}

			$this->document->setTitle($title);
		}

		if ($canDo->get('core.export') && $userAuthorisedExport)
		{
			$message = array();
			$message['success'] = JText::_("COM_TJREPORTS_EXPORT_FILE_SUCCESS");
			$message['error'] = JText::_("COM_TJREPORTS_EXPORT_FILE_ERROR");
			$message['inprogress'] = JText::_("COM_TJREPORTS_EXPORT_FILE_NOTICE");
			$message['text'] = JText::_("COM_TJREPORTS_CSV_EXPORT");
			$bar->appendButton('CsvExport', $message);
		}

		$button = '<span id="btn-cancel">
						<input type="text" name="queryName" autocomplete="off" placeholder="Title for the Query"  id="queryName" />
					</span>
					<a class="btn btn-primary  saveData" type="button" id="saveQuery"
						onclick="tjrContentUI.report.saveThisQuery();">'
						. JText::_('COM_TJREPORTS_SAVE_THIS_QUERY') . '</a>

					<button class="btn btn btn-default  cancel-btn" type="button" onclick="tjrContentUI.report.cancel();">
						Cancel
					</button>';

			JLoader::import('administrator.components.com_tjreports.helpers.tjreports', JPATH_SITE);
			TjreportsHelper::addSubmenu('reports');

			if ($app->isAdmin())
			{
				$this->sidebar = JHtmlSidebar::render();
			}

			$bar->appendButton('Custom', $button);
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
		$app = JFactory::getApplication();
		JHtml::_('formbehavior.chosen', 'select');
		$document = JFactory::getDocument();

		$com_params	= JComponentHelper::getParams('com_tjreports');
		$bootstrapSetting = $com_params->get('bootstrap_setting', 1);

		if (($bootstrapSetting == 3)
			|| ( $app->isAdmin() && $bootstrapSetting == 1 )
			|| ( !$app->isAdmin() && $bootstrapSetting == 2 ) )
		{
			$document->addStylesheet(JURI::root(true) . '/media/techjoomla_strapper/bs3/css/bootstrap.min.css');
		}

		$document->addScript(JURI::root() . '/components/com_tjreports/assets/js/tjrContentService.js');
		$document->addScript(JURI::root() . '/components/com_tjreports/assets/js/tjrContentUI.js');
		$document->addStylesheet(JURI::root() . '/components/com_tjreports/assets/css/tjreports.css');
		$document->addScriptDeclaration('tjrContentUI.base_url = "' . Juri::base() . '"');
		$document->addScriptDeclaration('tjrContentUI.root_url = "' . Juri::root() . '"');

		if (method_exists($this->model, 'getScripts'))
		{
			$plgScripts = (array) $this->model->getScripts();

			foreach ($plgScripts as $script)
			{
				$document->addScript($script);
			}
		}

		if (method_exists($this->model, 'getStyles'))
		{
			$styles = (array) $this->model->getStyles();

			foreach ($styles as $style)
			{
				$document->addStylesheet($style);
			}
		}

		$this->getLanguageConstant();
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		JText::script('JERROR_ALERTNOAUTHOR');
		JText::script('COM_TJREPORTS_DELETE_MESSAGE');
		JText::script('COM_TJREPORTS_SAVE_QUERY');
		JText::script('COM_TJREPORTS_ENTER_TITLE');
		JText::script('COM_TJREPORTS_QUERY_DELETE_SUCCESS');

		JFactory::getLanguage()->load('lib_techjoomla', JPATH_SITE, null, false, true);
		JText::script('LIB_TECHJOOMLA_CSV_EXPORT_ABORT');
		JText::script('LIB_TECHJOOMLA_CSV_EXPORT_UESR_ABORTED');
		JText::script('LIB_TECHJOOMLA_CSV_EXPORT_CONFIRM_ABORT');
	}
}
