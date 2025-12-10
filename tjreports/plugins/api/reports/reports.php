<?php
/**
 * @package      Tjreports
 * @subpackage   com_api
 *
 * @author       Techjoomla <extensions@techjoomla.com>
 * @copyright    Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;

JLoader::import('components.com_tjreports.models.report', JPATH_SITE);

/**
 * Tjreports API plugin
 *
 * @since  1.0
 */
class PlgAPIReports extends ApiPlugin
{
	/**
	 * Constructor
	 *
	 * @param   STRING  &$subject  subject
	 * @param   array   $config    config
	 *
	 * @since 1.0
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config = array());

		// Set resource path
		ApiResource::addIncludePath(dirname(__FILE__) . '/reports');

		// Load language files
		$lang = Factory::getLanguage();
		$lang->load('plg_api_reports', JPATH_SITE . "/plugins/api/reports/", 'en-GB', true);
	}
}
