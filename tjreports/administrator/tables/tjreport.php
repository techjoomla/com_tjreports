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
/**
 * Hello Table class
 *
 * @since  0.0.1
 */
class TjreportsTableTjreport extends JTable
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
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent   = JTable::getInstance('Asset');

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
		$db = JFactory::getDbo();

		$this->alias = trim($this->alias);

		if (empty($this->alias))
		{
			$this->alias = $this->title;
		}

		if ($this->alias)
		{
			if (JFactory::getConfig()->get('unicodeslugs') == 1)
			{
				$this->alias = JFilterOutput::stringURLUnicodeSlug($this->alias);
			}
			else
			{
				$this->alias = JFilterOutput::stringURLSafe($this->alias);
			}
		}

		// Check if course with same alias is present
		$table = JTable::getInstance('Tjreport', 'TjreportsTable', array('dbo', $db));

		if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
		{
			$msg = JText::_('COM_TJLMS_SAVE_ALIAS_WARNING');

			while ($table->load(array('alias' => $this->alias)))
			{
				$this->alias = JString::increment($this->alias, 'dash');
			}

			JFactory::getApplication()->enqueueMessage($msg, 'warning');
		}

		if (in_array($this->alias, $tjlms_views))
		{
			$this->setError(JText::_('COM_TJLMS_VIEW_WITH_SAME_ALIAS'));

			return false;
		}

		if (trim(str_replace('-', '', $this->alias)) == '')
		{
			$this->alias = JFactory::getDate()->format("Y-m-d-H-i-s");
		}

		return parent::check();
	}

}
