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
use Joomla\CMS\HTML\HTMLHelper;

Use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

require_once __DIR__ . '/view.base.php';
require_once JPATH_LIBRARIES . '/techjoomla/tjtoolbar/button/csvexport.php';

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
		$input  = Factory::getApplication()->input;
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
		$app                  = Factory::getApplication();
		$reportId             = $app->getUserStateFromRequest('reportId', 'reportId', '');
		$user                 = Factory::getUser();
		$userAuthorisedExport = $user->authorise('core.export', 'com_tjreports.tjreport.' . $reportId);
		$bar                  = Toolbar::getInstance('toolbar');
		$canDo                = TjreportsHelper::getActions();

		if ($app->isClient("administrator"))
		{
			$title = Text::_('COM_TJREPORTS_TITLE_REPORT');

			if (isset($this->reportData->title))
			{
				$title = $title . ' - ' . $this->reportData->title;
			}

			ToolbarHelper::title($title, 'list');
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
			$message               = array();
			$message['success']    = Text::_("COM_TJREPORTS_EXPORT_FILE_SUCCESS");
			$message['error']      = Text::_("COM_TJREPORTS_EXPORT_FILE_ERROR");
			$message['inprogress'] = Text::_("COM_TJREPORTS_EXPORT_FILE_NOTICE");
			$message['text']       = Text::_("COM_TJREPORTS_CSV_EXPORT");
			$bar->appendButton('CsvExport', $message);
		}

		$button = '<span id="btn-cancel">
						<input type="text" name="queryName" autocomplete="off" placeholder="Title for the Query"  id="queryName" class="m-1" />
					</span>
					<a class="btn btn-primary  saveData ms-2" type="button" id="saveQuery"
						onclick="tjrContentUI.report.saveThisQuery();">'
						. Text::_('COM_TJREPORTS_SAVE_THIS_QUERY') . '</a>

					<button class="btn btn btn-default  cancel-btn" type="button" onclick="tjrContentUI.report.cancel();">
						Cancel
					</button>';

			JLoader::import('administrator.components.com_tjreports.helpers.tjreports', JPATH_SITE);
			TjreportsHelper::addSubmenu('reports');

			if ($app->isClient("administrator"))
			{
				$this->sidebar = '';;
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
		$app = Factory::getApplication();
		$document = Factory::getDocument();

		$com_params = ComponentHelper::getParams('com_tjreports');
		$bootstrapSetting = $com_params->get('bootstrap_setting', 1);

		if (($bootstrapSetting == 3)
			|| ( $app->isClient("administrator") && $bootstrapSetting == 1 )
			|| ( !$app->isClient("administrator") && $bootstrapSetting == 2 ) )
		{
			HTMLHelper::stylesheet(Uri::root() . '/media/techjoomla_strapper/bs3/css/bootstrap.min.css');
		}

		HTMLHelper::_('script', 'com_tjreports/tjrContentService.min.js', array("relative" => true));
		HTMLHelper::_('script', 'com_tjreports/tjrContentUI.min.js', array("relative" => true));
		HTMLHelper::_('stylesheet', 'components/com_tjreports/assets/css/tjreports.min.css');

		$document->addScriptDeclaration('tjrContentUI.base_url = "' . Uri::base() . '"');
		$document->addScriptDeclaration('tjrContentUI.root_url = "' . Uri::root() . '"');

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
	 * Get all Text for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		Text::script('JERROR_ALERTNOAUTHOR');
		Text::script('COM_TJREPORTS_DELETE_MESSAGE');
		Text::script('COM_TJREPORTS_SAVE_QUERY');
		Text::script('COM_TJREPORTS_ENTER_TITLE');
		Text::script('COM_TJREPORTS_QUERY_DELETE_SUCCESS');

		Factory::getLanguage()->load('lib_techjoomla', JPATH_SITE, null, false, true);
		Text::script('LIB_TECHJOOMLA_CSV_EXPORT_ABORT');
		Text::script('LIB_TECHJOOMLA_CSV_EXPORT_UESR_ABORTED');
		Text::script('LIB_TECHJOOMLA_CSV_EXPORT_CONFIRM_ABORT');
	}
}
