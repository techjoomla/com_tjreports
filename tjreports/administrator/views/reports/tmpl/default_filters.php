<?php
/**
 * @version     1.0.0
 * @package     com_tjreports
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;

// Load the layout & push variables
$path = $this->tjreportsHelper->getViewpath('com_tjreports', 'reports', 'default_filters', 'SITE', 'SITE');
ob_start();
include($path);
$html = ob_get_contents();
ob_end_clean();
echo $html;
