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
use Joomla\Filesystem\File;
use Joomla\CMS\Factory;

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

$tjStrapperPath = JPATH_SITE . '/media/techjoomla_strapper/tjstrapper.php';

if (File::exists($tjStrapperPath))
{
	require_once $tjStrapperPath;
	TjStrapper::loadTjAssets('com_tjreports');
}

// Include dependancies

spl_autoload_register(function ($class) {
	if (strpos($class, 'Tjreports') === 0 || strpos($class, 'tjreports') === 0) {
		$path = JPATH_COMPONENT . '/' . strtolower(substr($class, 9)) . '.php';
		if (file_exists($path)) {
			require_once $path;
		}
	}
});

$controller = BaseController::getInstance('tjreports');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
