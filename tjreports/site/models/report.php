<?php
/**
 * @package    Com_Tmt
 * @copyright  Copyright (C) 2009 -2015 Techjoomla, Tekdi Web Solutions . All rights reserved.
 * @license    GNU GPLv2 <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
 * @link       http://www.techjoomla.com
 */

// No direct access.
defined('_JEXEC') or die;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * Tmt model.
 *
 * @since  1.0
 */
class  TjreportsModelReport extends AdminModel
{
	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $type    The table name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable  A JTable object
	 *
	 * @since   1.6
	 */

	public function getTable($type = 'Tjreport', $prefix = 'TjreportsTable', $config = array())
	{
		return Table::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed    A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm(
			'com_tjreports.tjreport',
			'tjreport',
			array(
				'control' => 'jform',
				'load_data' => $loadData
			)
		);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState(
			'com_tjreports.edit.tjreports.data',
			array()
		);

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	/**
	 * Method to delete the saved report
	 *
	 * @param   INT  &$pks  primary key of the element to be deleted
	 *
	 * @return  boolean  true/false.
	 *
	 * @since   1.6
	 */
	public function delete(&$pks)
	{
		$db    = Factory::getDBO();
		Table::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjreports/tables');
		$tjrTable = Table::getInstance('Tjreport', 'TjreportsTable', array('dbo', $db));
		$tjrTable->load(array('id' => &$pks));
		$data = $tjrTable;

		if ($tjrTable->userid == Factory::getUser()->id)
		{
			$tjrTable->delete($pks);

			$extension = Factory::getApplication()->input->get('option');
			PluginHelper::importPlugin('tjreports');
			Factory::getApplication()->triggerEvent('onAfterTjReportsReportDelete', array($extension, $data));

			return true;
		}

		return false;
	}
}
