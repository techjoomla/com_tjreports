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
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
$mainframe  = JFactory::getApplication('admin');
//$menu_id=$mainframe->getMenu()->getActive()->id;

$link = 'index.php?option=com_tjreports&view=reports';
$menu = $mainframe->getMenu();
$menuItem = $menu->getItems( 'link', $link, true );
$document   = JFactory::getDocument();
$user       = JFactory::getUser();
$user_id    = $user->id;
$document->addScript(JURI::base(true).'/components/com_tjreports/assets/js/jquery.twbsPagination.js');
$document->addScript(JURI::base(true).'/components/com_tjreports/assets/js/tjreports.js');
$input = JFactory::getApplication()->input;
$queryId = $input->get('queryId', '', 'INT');
$report = $input->get('reportToBuild','','string');
$client = $input->get('client','','string');
$reportId = $input->get('reportId','','INT');

if ($reportId)
{
	$allow_permission = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);
}

// Check is there only one report present

if (count($this->options) == 1)
{
	$document->addScriptDeclaration('var is_single_report = 1');
}
else
{
	$document->addScriptDeclaration('var is_single_report = 0');
}

$currentQuery = $report . '_' . $queryId;

$document->addScriptDeclaration('var reportToBuild = "' . $report . '"');
$document->addScriptDeclaration('var site_root = "' . JUri::root() . '"');
$document->addScriptDeclaration('var current_user = "' . $user_id . '"');
$document->addScriptDeclaration('var client = "' . $client . '"');
$document->addScriptDeclaration('var reportId = "' . $reportId . '"');
$document->addScriptDeclaration('var allow_permission = "' . $allow_permission . '"');

?>

<script>
	techjoomla.jQuery(document).ready(function()
	{
		if(is_single_report)
		{
			jQuery(".dropdown-list").hide();
		}

		if(reportId == 0)
		{
			loadReport(<?php echo (int)$this->options[0]->value ?>,<?php echo  (int)$menuItem->id ?>);
		}
	});
</script>

	<?php
		jimport('joomla.application.module.helper');
		$modules = JModuleHelper::getModules('manager-menu');

		foreach ($modules as $module)
		{
			//echo JModuleHelper::renderModule($module->title);
			echo JModuleHelper::renderModule($module);
		}
	?>
<div>
<!--
	<div class="header-title">
		<h3>
			<b>
				<?php echo JText::_("COM_TJREPORTS_REPORT_VIEW_TITLE"); ?>
			</b>
		</h3>
	</div>
-->
	<?php


	/*
		ob_start();
		include JPATH_BASE . '/components/com_tjreports/layouts/header.sidebar.php';
		$layoutOutput = ob_get_contents();
		ob_end_clean();
		echo $layoutOutput;
	*/
	?>
	<!--// JHtmlsidebar for menu ends-->

 
	<form action="<?php echo JRoute::_('index.php?option=com_tjreports&view=reports'); ?>" method="post" name="adminForm" id="adminForm">
		<div>
			<div class="row-fluid">
				<div class="span5 dropdown-list">
								<?php
									if (!empty($this->options)): ?>
										<?php echo JHtml::_('select.genericlist', $this->options, "filter_selectplugin", 'class="" size="1" onchange="loadReport(this.value,' . $menuItem->id . ');" name="filter_selectplugin"', "value", "text",  $reportId);
											?>
								<?php endif; ?>
				</div>
				<div class="span3">
						<div class="show-hide-cols ">
							<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="getColNames(); return false;" value="<?php echo JText::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>">
							<ul id="ul-columns-name" class="ColVis_collection" style="display:none">

								<?php if (!empty($this->colToshow)):	?>
									<?php  $this->colToshow = $this->colToshow; ?>
								<?php endif; ?>

								<?php foreach ($this->colNames as $constant => $colName): ?>
									<li class="span5 offset1">
										<label>
											<?php $disabled = ''; ?>
											<?php if ($colName == 'id'): ?>
												<?php $disabled = 'disabled'; ?>
											<?php endif; ?>

											<?php $checked = 'checked="checked"'; ?>
											<?php if (!empty($this->colToshow)):	?>
												<?php if (!in_array($colName, $this->colToshow)): ?>
													<?php $checked = ''; ?>
												<?php endif; ?>
											<?php endif; ?>

											<input type="checkbox" <?php echo $checked; ?> name="<?php echo $colName;?>" <?php echo $disabled; ?> id="<?php echo $colName;	?>">
												<span><?php echo JText::_($constant);	?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				<div class="span2">
					<?php
						$report_path=JRoute::_('index.php?option=com_tjreports&task=reports.csvexport');

						if(!empty($reportId)):
						echo "<a class='btn' class='button'
						type='submit'  href='$report_path'><span title='Export'
						class='icon-download'></span>" . JText::_('COM_TJREPORTS_CSV_EXPORT') . "</a>";
						endif
					?>
				</div>
				<div class="span2">
					<div id="reportPagination" class="pull-right ">
						<select id="list_limit" name="list[limit]" class="input-mini chzn-done" onchange="getFilterdata(0, '','paginationLimit')">
							<option value="5" >5</option>
							<option value="10">10</option>
							<option value="15">15</option>
							<option value="20" selected="selected">20</option>
							<option value="25">25</option>
							<option value="30">30</option>
							<option value="50">50</option>
							<option value="100">100</option>
							<option value="0">All</option>
						</select>
					</div>
				</div>
			</div>
			<br>
			<div>
				<div class="report-top-bar row">
					<?php if (empty($this->items)):	?>
						<div class="col-md-12 col-lg-12">
							<div class="alert alert-warning">
								<?php echo JText::_('COM_TJREPORTS_NO_REPORT'); ?>
								</div>
							</div>
						</div>
					<?php else: ?>
					</div>

					<div>
					<div class="col-sm-8 col-xs-12">
						<?php
						if (!empty($this->saveQueriesList)): ?>
							<div>
									<?php echo JHtml::_('select.genericlist', $this->saveQueriesList, "filter_saveQuery", 'class="" size="1" onchange="getQueryResult(this.value,'. $menuItem->id .');" name="filter_saveQuery"', "value", "text", $currentQuery);
									?>
							
							</div><br>
						<?php endif; ?>
					</div>
					<div class="row-fluid">
							<button class="btn" type="button" title="<?php echo "Clear"; ?>" onClick="window.location.reload();">Clear</button>


					<?php if($queryId)
					{ ?>
						<div class="span2">
<button class= "btn btn-danger" onClick="deleteQuery(<?php echo $queryId; ?>);return false;">Delete</button>
						</div>
					<?php } 
					?>
					</div>
					<br/>
					<div class="col-md-3 col-sm-3 col-xs-12">
						<div class="input-append">
							<input type="text" name="queryName" placeholder="Title for the Query"  style="display:none !important" id="queryName" />
							<input type="button" class="btn btn-primary" id="saveQuery" onclick="saveThisQuery();" style="display:none !important" value="<?php echo JText::_('COM_TJREPORTS_SAVE_THIS_QUERY'); ?>" />
						</div>
					</div>
					<br>
					<div class="col-md-5 col-sm-4 col-xs-12">
						<?php if ($report == 'attemptreport'): ?>
							<!--
								<hr class="hr hr-condensed" />
							-->
							<div>
								<?php $tableFilters = $mainframe->getUserState("com_tjreports." . $report ."_table_filters", '');	?>
								<?php $fromdate = isset($tableFilters['fromDate']) ? $tableFilters['fromDate'] : ''; ?>
								<?php $toDate = isset($tableFilters['toDate']) ? $tableFilters['toDate'] : ''; ?>
								<div class="filter-search btn-group ">
									<?php echo JHtml::_('calendar', $fromdate, 'attempt_begin', 'attempt_begin', '%Y-%m-%d', array('value'=>date("Y-m-d") ,'class'=>'dash-calendar validate-ymd-date required', 'size' => 10,'placeholder'=>"From (YYYY-MM-DD)")); ?>
								</div>
								<div class="filter-search btn-group ">
									<?php echo JHtml::_('calendar', $toDate, 'attempt_end', 'attempt_end', '%Y-%m-%d', array('class'=>'dash-calendar required validate-ymd-date','size' => 10,'placeholder'=>"To (YYYY-MM-DD)")); ?>
								</div>

								<div class="btn-group filter-btn-block input-append">
									<button class="btn hasTooltip" onclick="getFilterdata('-1','','datesearch'); return false;" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
									<button class="btn hasTooltip"  type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="cleardate(); return false;"><i class="icon-remove"></i></button>
								</div>
							</div>

							<!--
								<div style="clear:both"></div>
							<hr class="hr hr-condensed" />
							-->
						<?php endif; ?>
					</div>
				</div>

				<div id="report-containing-div" class="tjlms-tbl margint20" style="overflow-x: auto">
					<?php echo $this->items['html']; ?>
				</div>

				<div class="center">
					<div class="pagination">
						<ul id="pagination-demo" class="pagination-sm ">
						</ul>
					</div>
				</div>

				<input type="hidden" id="allow_permission" name="allow_permission" value="<?php echo  $allow_permission; ?>" />
				<input type="hidden" id="reportId" name="reportId" value="<?php echo  $reportId; ?>" />
				<input type="hidden" id="task" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="totalRows" id="totalRows" value="<?php echo $this->items['total_rows']; ?>" />
				<?php echo JHtml::_('form.token'); ?>
				<?php endif; ?>
			</div>
		</div>
	</form>
</div>

