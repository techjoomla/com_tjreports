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
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

/**
 * Script file of TJReports component
 *
 * @since  1.0.0
 **/
class Com_TjreportsInstallerScript
{
	/**
	 * Database driver
	 *
	 * @var DatabaseInterface
	 */
	private $db;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
	}

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
		$status          = new \stdClass();
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
						$sql = $this->db->getQuery(true)->select($this->db->quoteName('extension_id'))
						->from($this->db->quoteName('#__extensions'))
						->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
						->where($this->db->quoteName('element') . ' = ' . $this->db->quote($plugin))
						->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($folder));
						$this->db->setQuery($sql);

						$id = $this->db->loadResult();

						if ($id)
						{
							$installer = new Installer();
							$installer->setDatabase($this->db);
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

		$status = new \stdClass();
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
						$query = $this->db->getQuery(true)
							->select('COUNT(*)')
							->from($this->db->quoteName('#__extensions'))
							->where($this->db->quoteName('element') . ' = ' . $this->db->quote($plugin))
							->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($folder));
						$this->db->setQuery($query);
						$count = $this->db->loadResult();

						$installer = new Installer();
						$installer->setDatabase($this->db);
						$result = $installer->install($path);

						$status->plugins[] = array('name' => 'plg_' . $plugin, 'group' => $folder, 'result' => $result);

						if ($published && !$count)
						{
							$query = $this->db->getQuery(true)
								->update($this->db->quoteName('#__extensions'))
								->set($this->db->quoteName('enabled') . ' = ' . $this->db->quote('1'))
								->where($this->db->quoteName('element') . ' = ' . $this->db->quote($plugin))
								->where($this->db->quoteName('folder') . ' = ' . $this->db->quote($folder));
							$this->db->setQuery($query);
							$this->db->execute();
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
		try
		{
			// Check if component tables exist (might not during uninstall)
			$tables = $this->db->getTableList();
			$prefix = $this->db->getPrefix();
			$reportTable = $prefix . 'tjreports';
			
			if (!in_array($reportTable, $tables))
			{
				return;
			}

			// Get reports directly from database instead of using model
			$query = $this->db->getQuery(true)
				->select('*')
				->from($this->db->quoteName('#__tjreports'))
				->order($this->db->quoteName('id') . ' ASC');
			$this->db->setQuery($query);
			$reportList = $this->db->loadObjectList();

			if (empty($reportList))
			{
				return;
			}

			// Update ordering for each report
			foreach ($reportList as $key => $report)
			{
				$ordering = $key + 1;
				$updateQuery = $this->db->getQuery(true)
					->update($this->db->quoteName('#__tjreports'))
					->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote($ordering))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($report->id));
				$this->db->setQuery($updateQuery);
				$this->db->execute();
			}
		}
		catch (Exception $e)
		{
			// Silently fail during install/uninstall - not critical
			return;
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
		if (!empty($removeFilesAndFolders['files']))
		{
			foreach ($removeFilesAndFolders['files'] as $file)
			{
				$f = JPATH_ROOT . '/' . $file;
				if (!File::exists($f))
				{
					continue;
				}
				File::delete($f);
			}
		}

		// Remove folders
		if (!empty($removeFilesAndFolders['folders']))
		{
			foreach ($removeFilesAndFolders['folders'] as $folder)
			{
				$f = JPATH_ROOT . '/' . $folder;
				if (!Folder::exists($f))
				{
					continue;
				}
				Folder::delete($f);
			}
		}
	}
}
