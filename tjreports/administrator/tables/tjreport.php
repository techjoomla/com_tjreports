<?php
/**
 * @package     Joomla.site
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Language\Text;

/**
 * Report Table class
 *
 * @since  0.0.1
 */
class TjreportsTableTjreport extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  &$db  A database connector object
	 */
	public function __construct(&$db)
	{
		// $this->setColumnAlias('published', 'state');
		parent::__construct('#__tj_reports', 'id', $db);
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @see JTable::_getAssetName
	 *
	 * @return string The asset name
	 *
	 * @since  1.0.0
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return 'com_tjreports.tjreport.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Object  $table  Jtable
	 * @param   INT     $id     Id
	 *
	 * @return  The parent asset's id
	 *
	 * @since  1.0.0
	 */
	protected function _getAssetParentId(Table $table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent   = Table::getInstance('Asset');

		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();

		// The item has the component as asset-parent
		$assetParent->loadByName('com_tjreports');

		// Return the found asset-parent-id
		if ($assetParent->id)
		{
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

	/**
	 * Overloaded check function
	 *
	 * @return  check
	 *
	 * @since  1.0.0
	 */
	public function check()
	{
		$db = Factory::getDbo();

		$this->alias = trim($this->alias);

		if (empty($this->alias))
		{
			$this->alias = $this->title;
		}

		if ($this->alias)
		{
			if (Factory::getConfig()->get('unicodeslugs') == 1)
			{
				$this->alias = OutputFilter::stringURLUnicodeSlug($this->alias);
			}
			else
			{
				$this->alias = OutputFilter::stringURLSafe($this->alias);
			}
		}

		// Check if course with same alias is present
		$table = Table::getInstance('Tjreport', 'TjreportsTable', array('dbo', $db));

		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
		{
			$msg = Text::_('COM_TJREPORT_SAVE_ALIAS_WARNING');

			while ($table->load(array('alias' => $this->alias)))
			{
				$this->alias = JString::increment($this->alias, 'dash');
			}

			Factory::getApplication()->enqueueMessage($msg, 'warning');
		}

		$tjreport_views = array('reports');

		if (in_array($this->alias, $tjreport_views))
		{
			$this->setError(Text::_('COM_TJREPORT_VIEW_WITH_SAME_ALIAS'));

			return false;
		}

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = Factory::getDate()->format("Y-m-d-H-i-s");
		}

		return parent::check();
	}
}
