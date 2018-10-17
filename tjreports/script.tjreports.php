<?php
/**
 * @version    SVN: <svn_id>
 * @package    TJReports
 * @copyright  Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license    GNU General Public License version 3, or later
 *
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @version    SVN: <svn_id>
 * @package    TJReports
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2018 TechJoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later.
 */

defined('_JEXEC') or die( ';)' );
jimport('joomla.installer.installer');
jimport('joomla.filesystem.file');
jimport('joomla.application.component.helper');


/**
 * Script file of TJReports component
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
