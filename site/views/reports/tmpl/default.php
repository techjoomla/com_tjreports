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

$document = JFactory::getDocument();
$user_id = JFactory::getUser()->id;
$document->addScript(JURI::base(true).'/components/com_tjreports/assets/js/jquery.twbsPagination.js');
$document->addScript(JURI::base(true).'/components/com_tjreports/assets/js/tjreports.js');
$input = JFactory::getApplication()->input;
$queryId = $input->get('queryId', '', 'INT');
$report = $input->get('reportToBuild','','string');
$client = $input->get('client','','string');
$reportId = $input->get('reportId','','INT');

// Filters will get values from session
$session = JFactory::getSession();
$session->set('reportId', $reportId);

if(empty($report))
{
	$this->items ="";
}

$currentQuery = $report . '_' . $queryId;

$document->addScriptDeclaration('var reportToBuild = "' . $report . '"');
$document->addScriptDeclaration('var site_root = "' . JUri::root() . '"');
$document->addScriptDeclaration('var current_user = "' . $user_id . '"');
$document->addScriptDeclaration('var client = "' . $client . '"');
$document->addScriptDeclaration('var reportId = "' . $reportId . '"');


	//print_r($this->reports);
/*
	foreach ($this->reports as $repo)
	{
		if($repo->default == 0)
		{
			$reportId = $repo->parent;
		}
		else
		{
			$reportId = $repo->id;
		}

		echo $repo->name ."&nbsp&nbsp&nbsp&nbsp";
		$url = 'index.php?option=com_tjreports&view=reports&client='. $repo->client .'&reportToBuild='.$repo->plugin.'&reportId='.$reportId.'&Itemid=563';
		echo '<a href='.JRoute::_("$url").'>view </a>';
		echo "<br>";

	}

	*/

?>

<script>
	techjoomla.jQuery(document).click(function(e)
	{
		if (!techjoomla.jQuery(e.target).closest('#ul-columns-name').length && e.target.id != 'show-hide-cols-btn')
		{
			techjoomla.jQuery(".ColVis_collection").hide();
		}
	});

	techjoomla.jQuery(document).ready(function(){
		getPaginationBar();
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
			<div class="row margint20">
				<div class="col-md-10 col-lg-10 text-semibold">
					<div>
						<span>
							<?php echo JText::_("COM_TJREPORTS_AVAILABLE_REPORT_LIST"); ?>
						</span>
						<span>
							<?php
								if (!empty($this->options)): ?>
								<div>
									<?php echo JHtml::_('select.genericlist', $this->options, "filter_selectplugin", 'class="" size="1" onchange="loadReport(this.value,' . $menuItem->id . ');" name="filter_selectplugin"', "value", "text",  $reportId);
										?>
								</div>
							<?php endif; ?>
						</span>
					</div>
				</div>

				<div class="col-md-2 col-lg-2 pull-right">
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
			<hr>
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
					<div class="col-md-2 col-sm-3 col-xs-12">
						<div class="show-hide-cols ">
							<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="getColNames(); return false;" value="<?php echo JText::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>">
							<ul id="ul-columns-name" class="ColVis_collection" style="display:none">

								<?php if (!empty($this->colToshow)):	?>
									<?php  $this->colToshow = $this->colToshow; ?>
								<?php endif; ?>

								<?php foreach ($this->colNames as $constant => $colName): ?>
									<li>
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

											<input type="checkbox" <?php echo $checked; ?> name="<?php echo $colName;	?>" <?php echo $disabled; ?> id="<?php echo $colName;	?>">
												<span><?php echo JText::_($constant);	?></span>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>

					<div class="col-sm-2 col-xs-12">
						<?php
						if (!empty($this->saveQueriesList)): ?>
							<div>
									<?php echo JHtml::_('select.genericlist', $this->saveQueriesList, "filter_saveQuery", 'class="" size="1" onchange="getQueryResult(this.value);" name="filter_saveQuery"', "value", "text", $currentQuery);
									?>
							</div>
						<?php endif; ?>
					</div>

					<div class="col-md-3 col-sm-3 col-xs-12">
						<div>
							<input type="text" name="queryName" placeholder="Title for the Query"  style="display:none !important" id="queryName" />
							<input type="button" class="btn btn-primary" id="saveQuery" onclick="saveThisQuery();" value="<?php echo JText::_('COM_TJREPORTS_SAVE_THIS_QUERY'); ?>" />
						</div>
					</div>
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

				<div id="report-containing-div" class="tjlms-tbl margint20">
					<?php echo $this->items['html']; ?>
				</div>

				<div class="center">
					<div class="pagination">
						<ul id="pagination-demo" class="pagination-sm ">
						</ul>
					</div>
				</div>

				<input type="hidden" id="task" name="task" value="" />
				<input type="hidden" name="boxchecked" value="0" />
				<input type="hidden" name="totalRows" id="totalRows" value="<?php echo $this->items['total_rows']; ?>" />
				<?php echo JHtml::_('form.token'); ?>
				<?php endif; ?>
			</div>
		</div>
	</form>
</div>

<script>

function getColNames()
{
	techjoomla.jQuery('.ColVis_collection').toggle();
}

function getQueryResult(id)
{
	var queryId = id.split("_");

	if (queryId=="")
	{
		window.location.href = 'index.php?option=com_tjreports&view=reports&reportToBuild='+reportToBuild+'&client='+client+'&reportId='+reportId;
	}
	else
	{
		window.location.href = 'index.php?option=com_tjreports&view=reports&savedQuery=1&reportToBuild='+queryId[0]+'&client='+client+'&queryId='+queryId[1]+'&reportId='+reportId;
	}
}

techjoomla.jQuery(document).ready(function()
{
	switch('<?php echo $report; ?>')
	{
		case 'userreport':
			techjoomla.jQuery('#userreport').addClass('active btn-primary');
			break;
		case 'studentcoursereport':
			techjoomla.jQuery('#studentcoursereport').addClass('active btn-primary');
			break;
		case 'lessonreport':
			techjoomla.jQuery('#lessonreport').addClass('active btn-primary');
			break;
		case 'coursereport':
			techjoomla.jQuery('#coursereport').addClass('active btn-primary');
			break;
		case 'attemptreport':
			techjoomla.jQuery('#attemptreport').addClass('active btn-primary');
			break;
	}

	techjoomla.jQuery('.ColVis_collection input').click(function(){

		if (techjoomla.jQuery(".ColVis_collection input:checkbox:checked").length > 0)
		{
			getFilterdata(-1, '', 'hideShowCols');
		}
		else
		{
			var msg = Joomla.JText._('COM_TJREPORTS_REPORTS_CANNOT_SELECT_NONE');
			alert(msg);
			return false;
		}
	});
});

function loadReport(reportToLoad,mid)
{
		var path = window.location.pathname + "?option=com_tjreports&task=reports.getreport";

		// Report to load is id.
		var data = 'reportToLoad=' + reportToLoad;

		jQuery.ajax({
		url: path,
		type: 'post',
		data : data,
		dataType: 'json',

		success: function(resp)
		{
			reportToLoad = resp.plugin;
			clientt = resp.client;
			var clientArray = [];
			clientArray.push(clientt);

			 // Add current client to first and append others.

			var res = client.split(",");
			for(var i in res){
				if(res[i] == clientt){
					res.splice(i,1);
					break;
				}
			}

			for(var i = 0; i < res.length; i++){
				clientArray.push(res[i]);
			}

			clients = clientArray.toString();

			reportId = resp.id;
			var action = document.adminForm.action;
			var newAction = action+'&reportToBuild='+reportToLoad+'&client='+clients+'&reportId='+reportId+'&Itemid='+mid;
			window.location.href = newAction;

		}});
}

function cleardate()
{
	techjoomla.jQuery("#attempt_begin").val('');
	techjoomla.jQuery("#attempt_end").val('');
	getFilterdata(-1, '', 'dateSearch');
}
</script>

