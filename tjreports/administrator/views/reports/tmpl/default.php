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
$mainframe  = JFactory::getApplication();
$document = JFactory::getDocument();
$user = $this->user;
$user_id = $this->user_id;

JHtml::script(Juri::root().'/components/com_tjreports/assets/js/jquery.twbsPagination.js');
JHtml::script(Juri::root().'/components/com_tjreports/assets/js/tjreports.js');
JHtml::stylesheet(Juri::root().'administrator/components/com_tjreports/assets/css/tjreports.css');

$document->addScriptDeclaration('var site_root = "' . JUri::base() . '"');

$input = JFactory::getApplication()->input;
$queryId = $input->get('queryId', '', 'INT');
$report = $input->get('reportToBuild','','string');
$client = $input->get('client','','string');
$reportId = $input->get('reportId','','INT');
$extension=$input->get('extension','','string');

if ($reportId)
{
	$allow_permission = $user->authorise('core.viewall', 'com_tjreports.tjreport.' . $reportId);
}

$currentQuery = $report . '_' . $queryId;

$document->addScriptDeclaration('var reportToBuild = "' . $report . '"');
$document->addScriptDeclaration('var current_user = "' . $user_id . '"');
$document->addScriptDeclaration('var client = "' . $client . '"');
$document->addScriptDeclaration('var reportId = "' . $reportId . '"');
$document->addScriptDeclaration('var extension = "' . $extension . '"');
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

<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">

		<?php
			ob_start();
			$layoutOutput = ob_get_contents();
			ob_end_clean();
			echo $layoutOutput;

		// <!--// JHtmlsidebar for menu ends-->
		if (!empty($this->sidebar)):?>
				<div id="j-sidebar-container" class="span2">
					<?php echo $this->sidebar;?>
				</div>
				<div id="j-main-container" class="span10">
		<?php else :?>
				<div id="j-main-container">
		<?php endif;?>

		<form action="<?php echo JRoute::_('index.php?option=com_tjreports&view=reports'); ?>" method="post" name="adminForm" id="adminForm">
			<div class="report-top-bar row-fluid">
				<?php if (empty($this->items)):	?>
					<div class="alert alert-warning">
						<?php echo JText::_('COM_TJREPORTS_NO_REPORT'); ?>
					</div>
				<?php else: ?>

					<div class="show-hide-cols span2">
						<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="getColNames(); return false;" value="<?php echo JText::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>"></button>
						<ul id="ul-columns-name" class="ColVis_collection" style="display:none">

							<?php if (!empty($this->colToshow)):	?>
								<?php  $this->colToshow = $this->colToshow ?>
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
							<input type="checkbox" checked="checked" name="userType" id="userType" style="display:none">
						</ul>
					</div>

					<?php if (!empty($this->saveQueriesList)): ?>
						<div class="span3">
								<?php echo JHtml::_('select.genericlist', $this->saveQueriesList, "filter_saveQuery", 'class="" size="1" onchange="getQueryResult(this.value);" name="filter_saveQuery"', "value", "text", $currentQuery);
								?>
						</div>
					<?php endif; ?>


					<div class="span1 pull-right">
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

					<div class="span5 pull-right input-append">
						<input type="text" name="queryName" placeholder="Title for the Query" style="display:none" id="queryName" />
						<input type="button" class="btn btn-primary" id="saveQuery" onclick="saveThisQuery();" value="<?php echo JText::_('COM_TJREPORTS_SAVE_THIS_QUERY'); ?>" />
					</div>

				</div>
				<div>
					<button class="btn" type="button" title="Clear" onclick="window.location.reload();">Clear</button>
				</div>

				<?php if ($report == 'attemptreport'): ?>
					<div>
						<hr class="hr hr-condensed" />
						<div class="pull-right">
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
					</div>

					<div style="clear:both"></div>
				<hr class="hr hr-condensed" />
				<?php endif; ?>

				<div id="report-containing-div" class="tjlms-tbl">
					<?php echo $this->items['html']; ?>
				</div>


					<div class="pagination">
						<ul id="pagination-demo" class="pagination-sm ">
						</ul>
					</div>
					<input type="hidden" id="allow_permission" name="allow_permission" value="<?php echo  $allow_permission; ?>" />
					<input type="hidden" id="reportId" name="reportId" value="<?php echo  $reportId; ?>" />
					<input type="hidden" id="task" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<input type="hidden" name="totalRows" id="totalRows" value="<?php echo $this->items['total_rows']; ?>" />
					<input type="hidden" name="extension" value="<?php echo $input->get('client','','word'); ?>">

					<?php echo JHtml::_('form.token'); ?>
			<?php endif; ?>
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

	if(queryId=="")
	{
		window.location.href = 'index.php?option=com_tjreports&view=reports&reportToBuild='+reportToBuild+'&extension='+extension+'&reportId='+reportId;
	}
	else
	{
		window.location.href = 'index.php?option=com_tjreports&view=reports&savedQuery=1&reportToBuild='+queryId[0]+'&extension='+extension+'&queryId='+queryId[1]+'&reportId='+reportId;
	}
}

techjoomla.jQuery(document).ready(function()
{
	var report = '<?php echo $report; ?>';

	techjoomla.jQuery('#'+report).addClass('active btn-primary');


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

function loadReport(reportToLoad)
{
	var action = document.adminForm.action;
	var newAction = action+'&reportToBuild='+reportToLoad;

	if (extension)
	{
		newAction = newAction +'&extension='+extension;
	}
	window.location.href = newAction;
}

function cleardate()
{
	techjoomla.jQuery("#attempt_begin").val('');
	techjoomla.jQuery("#attempt_end").val('');
	getFilterdata(-1, '', 'dateSearch');
}
</script>
