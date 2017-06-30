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

/**
 * Cp helper.
 *
 * @since  1.6
 */
class TjreportsHelper
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
		$client = JFactory::getApplication()->input->get('client', '', 'STRING');
		$full_client = $client;
		$app = JFactory::getApplication('administrator');

		// Set ordering.
		$mainframe = JFactory::getApplication();
		$full_client = explode('.', $full_client);

		// Eg com_jgive
		$component = $full_client[0];
		$eName = str_replace('com_', '', $component);
		$file = JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component . '/helpers/' . $eName . '.php');

		if (file_exists($file))
		{
			require_once $file;

			$prefix = ucfirst(str_replace('com_', '', $component));
			$cName = $prefix . 'Helper';

			if (class_exists($cName))
			{
				if (is_callable(array($cName, 'addSubmenu')))
				{
					$lang = JFactory::getLanguage();

					// Loading language file from the administrator/language directory then
					// Loading language file from the administrator/components/*extension*/language directory
					$lang->load($component, JPATH_BASE, null, false, false)
					|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), null, false, false)
					|| $lang->load($component, JPATH_BASE, $lang->getDefault(), false, false)
					|| $lang->load($component, JPath::clean(JPATH_ADMINISTRATOR . '/components/' . $component), $lang->getDefault(), false, false);

					// Call_user_func(array($cName, 'addSubmenu'), 'categories' . (isset($section) ? '.' . $section : ''));
					call_user_func(array($cName, 'addSubmenu'), $view . (isset($section) ? '.' . $section : ''));
				}
			}
		}
	}

	/**
	 * Gets a list of the actions that can be performed.
	 *
	 * @return    JObject
	 *
	 * @since    1.6
	 */
	public static function getActions()
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		$assetName = 'com_tjreports';

		$actions = array(
			'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete', 'core.viewall'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}
}
