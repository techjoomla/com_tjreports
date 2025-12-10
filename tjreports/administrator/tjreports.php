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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_tjreports'))
{
	throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'));
}

if (!defined('DS'))
{
	define('DS', '/');
}

global $wrapperDiv;

/* define wrapper div*/
if (JVERSION < '3.0')
{
	define('COM_TJLMS_WRAPPER_DIV', 'techjoomla-bootstrap tjlms-wrapper  row-fluid');
}
else
{
	define('COM_TJLMS_WRAPPER_DIV', 'tjlms-wrapper row-fluid');
}

$document = Factory::getDocument();
$document->addStyleSheet(Uri::root(true) . '/media/com_tjreports/css/font-awesome/css/font-awesome.min.css');

// Include dependancies

spl_autoload_register(function ($class) {
	if (strpos($class, 'Tjreports') === 0) {
		$path = JPATH_COMPONENT_ADMINISTRATOR . '/' . strtolower(substr($class, 9)) . '.php';
		if (file_exists($path)) {
			require_once $path;
		}
	}
});

$controller = BaseController::getInstance('Tjreports');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
