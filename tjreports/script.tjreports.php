<?php
/**
 * @version    SVN: <svn_id>
 * @package    Com_Jticketing
 * @copyright  Copyright (C) 2005 - 2017. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */
defined('_JEXEC') or die( ';)' );
jimport('joomla.installer.installer');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.helper');


/**
 * Script file of JTicketing component
 *
 * @since  1.0.0
 **/
class Com_TjreportsInstallerScript
{
/** @var array The list of extra modules and plugins to install */
	private $installation_queue = array(

		// plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
				'actionlog' => array(
					'tjreports' => 1
				),
				'privacy' => array(
					'tjreports' => 1
				)
			)
		);

	/**
	 * This method is called after a component is installed.
	 *
	 * @param   \stdClass  $parent  Parent object calling this method.
	 *
	 * @return void
	 */
	public function install($parent)
	{
	}

	/**
	 * This method is called after a component is uninstalled.
	 *
	 * @param   \stdClass  $parent  Parent object calling this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
	}

	/**
	 * This method is called after a component is updated.
	 *
	 * @param   \stdClass  $parent  Parent object calling object.
	 *
	 * @return void
	 */
	public function update($parent)
	{
	}

	/**
	 * Runs just before any installation action is preformed on the component.
	 * Verifications and pre-requisites should run in this function.
	 *
	 * @param   string     $type    Type of PreFlight action. Possible values are:
	 *                              - * install
	 *                              - * update
	 *                              - * discover_install
	 * @param   \stdClass  $parent  Parent object calling object.
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * Runs right after any installation action is preformed on the component.
	 *
	 * @param   string     $type    Type of PostFlight action. Possible values are:
	 *                             - * install
	 *                             - * update
	 *                             - * discover_install
	 * @param   \stdClass  $parent  Parent object calling object.
	 *
	 * @return void
	 */
	public function postflight($type, $parent)
	{
		$src = $parent->getParent()->getPath('source');

		$db = JFactory::getDbo();

		$status = new JObject;
		$status->plugins = array();

		// Plugins installation
		if (count($this->installation_queue['plugins']))
		{
			foreach ($this->installation_queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$path = "$src/plugins/$folder/$plugin";

						if (!is_dir($path))
						{
							$path = "$src/plugins/$folder/plg_$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/$plugin";
						}

						if (!is_dir($path))
						{
							$path = "$src/plugins/plg_$plugin";
						}

						if (!is_dir($path))
						{
							continue;
						}

						// Was the plugin already installed?
						$query = $db->getQuery(true)
							->select('COUNT(*)')
							->from($db->qn('#__extensions'))
							->where($db->qn('element') . ' = ' . $db->q($plugin))
							->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($query);
						$count = $db->loadResult();

						$installer = new JInstaller;
						$result = $installer->install($path);

						$status->plugins[] = array('name' => 'plg_' . $plugin, 'group' => $folder, 'result' => $result);

						if ($published && !$count)
						{
							$query = $db->getQuery(true)
								->update($db->qn('#__extensions'))
								->set($db->qn('enabled') . ' = ' . $db->q('1'))
								->where($db->qn('element') . ' = ' . $db->q($plugin))
								->where($db->qn('folder') . ' = ' . $db->q($folder));
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}
	}
}
