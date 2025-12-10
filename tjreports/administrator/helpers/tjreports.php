<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjreports
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2016 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Factory;
use Joomla\Filesystem\Path;
use Joomla\Filesystem\File;

/**
 * Cp helper.
 *
 * @since  1.6
 */
class TjreportsHelper extends ContentHelper
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $view  string
	 *
	 * @return void
	 */
	public static function addSubmenu($view='')
	{
		$client = Factory::getApplication()->input->get('client', '', 'STRING');
		$full_client = $client;

		// Set ordering.
		$mainframe = Factory::getApplication();
		$full_client = explode('.', $full_client);

		// Eg com_jgive
		$component = $full_client[0];
		$eName = str_replace('com_', '', $component);
		$file = Path::clean(JPATH_ADMINISTRATOR . '/components/' . $component . '/helpers/' . $eName . '.php');

		if (file_exists($file))
		{
			require_once $file;

			$prefix = ucfirst(str_replace('com_', '', $component));
			$cName = $prefix . 'Helper';

			if (class_exists($cName))
			{
				if (is_callable(array($cName, 'addSubmenu')))
				{
					$lang = Factory::getLanguage();

					// Loading language file from the administrator/language directory then
					// Loading language file from the administrator/components/*extension*/language directory
					$lang->load($component, JPATH_BASE, null, false, false)
					|| $lang->load($component, Path::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, false)
					|| $lang->load($component, JPATH_BASE, $lang->getDefault(), false, false)
					|| $lang->load($component, Path::clean(JPATH_ADMINISTRATOR . '/components/' . $component), $lang->getDefault(), false, false);

					// Call_user_func(array($cName, 'addSubmenu'), 'categories' . (isset($section) ? '.' . $section : ''));
					call_user_func(array($cName, 'addSubmenu'), $view . (isset($section) ? '.' . $section : ''));
				}
			}
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @param   string   $component  The component name.
	 * @param   string   $section    The access section name.
	 * @param   integer  $id         The item ID.
	 *
	 * @return  JObject
	 *
	 * @since   3.2
	 */
	public static function getActions($component = 'com_tjreports', $section = '', $id = 0)
	{
		$result = parent::getActions($component, $section, $id);

		return $result;
	}

	/**
	 * This function get the view path
	 *
	 * @param   STRING  $component      Component name
	 * @param   STRING  $viewname       View name
	 * @param   STRING  $layout         Layout
	 * @param   STRING  $searchTmpPath  Site
	 * @param   STRING  $useViewpath    Site
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function getViewpath($component, $viewname, $layout = 'default', $searchTmpPath = 'SITE', $useViewpath = 'SITE')
	{
		$app = Factory::getApplication();

		$searchTmpPath = ($searchTmpPath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;
		$useViewpath   = ($useViewpath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;

		$layoutname = $layout . '.php';

		$override = $searchTmpPath . '/templates/' . $app->getTemplate() . '/html/' . $component . '/' . $viewname . '/' . $layoutname;

		if (File::exists($override))
		{
			return $view = $override;
		}
		else
		{
			return $view = $useViewpath . '/components/' . $component . '/views/' . $viewname . '/tmpl/' . $layoutname;
		}
	}
}
