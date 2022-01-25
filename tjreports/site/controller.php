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
use Joomla\CMS\MVC\Controller\BaseController;


/**
 * Class TjreportsController
 *
 * @since  1.6
 */
class TjreportsController extends BaseController
{
	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since   1.0
	 */
	public function display($cachable = false, $urlparams = false)
	{
		parent::display();
	}
}
