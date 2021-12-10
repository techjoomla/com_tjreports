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
use Joomla\CMS\Factory;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Table\Table;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Script file of TJReports component
 *
 * @since  1.0.0
 **/
class Com_TjreportsInstallerScript
{
	private $removeFilesAndFolders = array(
		'files' => array(
			// Since v1.1.7
			'components/com_tjreports/assets/js/tjrContentService.js',
			'components/com_tjreports/assets/js/tjrContentService.min.js',
			'components/com_tjreports/assets/js/tjrContentUI.js',
			'components/com_tjreports/assets/js/tjrContentUI.min.js',
		),
		'folders' => array()
	);

/** @var array The list of extra modules and plugins to install */
	private $queue = array(

		// @plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
				'actionlog' => array(
					'tjreports' => 1
				),
				'content' => array(
					'tjreportsfields' => 0
				),
				'privacy' => array(
					'tjreports' => 1
				),
				'user' => array(
					'tjreportsindexer' => 0
				),
				'api' => array(
					'reports' => 1
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

		$db = Factory::getDBO();

		$status          = new CMSObject;
		$status->plugins = array();

		$src = $parent->getParent()->getPath('source');

		// Plugins uninstallation
		if (count($this->queue['plugins']))
		{
			foreach ($this->queue['plugins'] as $folder => $plugins)
			{
				if (count($plugins))
				{
					foreach ($plugins as $plugin => $published)
					{
						$sql = $db->getQuery(true)->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('type') . ' = ' . $db->q('plugin'))
						->where($db->qn('element') . ' = ' . $db->q($plugin))
						->where($db->qn('folder') . ' = ' . $db->q($folder));
						$db->setQuery($sql);

						$id = $db->loadResult();

						if ($id)
						{
							$installer         = new Installer;
							$result            = $installer->uninstall('plugin', $id);
							$status->plugins[] = array(
								'name' => 'plg_' . $plugin,
								'group' => $folder,
								'result' => $result
							);
						}
					}
				}
			}
		}

		return $status;
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

		$db = Factory::getDbo();

		$status = new CMSObject;
		$status->plugins = array();

		// Plugins installation
		if (count($this->queue['plugins']))
		{
			foreach ($this->queue['plugins'] as $folder => $plugins)
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

						$installer = new Installer;
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

		// Remove obsolete files and folders
		$this->removeObsoleteFilesAndFolders($this->removeFilesAndFolders);

		$this->migrateReportsOrdering();
	}

	/**
	 * Migrate report ordering
	 *
	 * @return  void
	 *
	 * @since    1.0.6
	 */
	public function migrateReportsOrdering()
	{
		JLoader::import('components.com_tjreports.models.tjreports', JPATH_ADMINISTRATOR);
		$tjreportsModel = BaseDatabaseModel::getInstance('Tjreports', 'TjreportsModel');
		$tjreportsModel->setState('list.ordering', 'id');
		$reportList = $tjreportsModel->getItems();

		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');
		$reportTable = Table::getInstance('Tjreport', 'TjreportsTable');

		foreach ($reportList as $key => $report)
		{
			$data = (array) $report;
			$data['ordering'] = ++$key;

			$reportTable->save($data);
		}
	}

	/**
	 * Removes obsolete files and folders
	 *
	 * @param array $removeFilesAndFolders
	 */
	private function removeObsoleteFilesAndFolders($removeFilesAndFolders)
	{
		// Remove files
		if(!empty($removeFilesAndFolders['files']))
		{
			foreach($removeFilesAndFolders['files'] as $file)
			{
				$f = JPATH_ROOT.'/'.$file;
				if(!JFile::exists($f)) continue;
				JFile::delete($f);
			}
		}

		// Remove folders
		if(!empty($removeFilesAndFolders['folders']))
		{
			foreach($removeFilesAndFolders['folders'] as $folder)
			{
				$f = JPATH_ROOT.'/'.$folder;
				if(!file_exists($f)) continue;
				JFolder::delete($f);
			}
		}
	}
}
