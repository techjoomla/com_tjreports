<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;
/**
 * com tjreports Controller
 *
 * @since  0.0.1
 */
class TjreportsControllerTjreports extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model.
	 *
	 * @since   1.6
	 */
	public function getModel($name = 'Tjreport', $prefix = 'TjreportsModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

/**
	* Set a URL for browser redirection.
	*
	* @param   string  $url   URL to redirect to.
	* @param   string  $msg   Message to display on redirect. Optional, defaults to value set internally by controller, if any.
	* @param   string  $type  Message type. Optional, defaults to 'message' or the type set by a previous call to setMessage.
	*
	* @return  JControllerLegacy  This object to support chaining.
	*
	* @since   12.2
	*/
	public function setRedirect($url, $msg = null,$type = null)
	{
		$extension = JFactory::getApplication()->input->get('extension', '', 'word');

		if ($extension)
		{
			$url .= '&extension=' . $extension;
		}

		parent::setRedirect($url, $msg, $type);
	}
}
