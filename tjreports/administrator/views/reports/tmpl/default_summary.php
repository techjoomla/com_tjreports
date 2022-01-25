<?php
/**
 * @package     TJRports
 * @subpackage  com_tjreports
 *
 * @author      Techjoomla <extensions@techjoomla.com>
 * @copyright   Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

// Load the layout & push variables
$path = $this->tjreportsHelper->getViewpath('com_tjreports', 'reports', 'default_summary', 'SITE', 'SITE');
ob_start();
include $path;
$html = ob_get_contents();
ob_end_clean();
echo $html;
