<?php
// Import CSV library view
jimport('techjoomla.view.csv');
require_once JPATH_ROOT . '/components/com_tjreports/models/reports.php';

// Create your class that extends TjExportCsv class
class TjreportsViewReports extends TjExportCsv
{
	public function display($tpl = null)
	{
		parent::display();
	}
}
