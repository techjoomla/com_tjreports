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

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

	$app = JFactory::getApplication();
	$headerLevel    = $this->headerLevel;
	$this->listOrder      = $this->state->get('list.ordering');
	$this->listDirn       = $this->state->get('list.direction');
	$totalCount = 0;

	foreach ($this->colToshow as $key=>$data)
	{
		if (is_array($data))
		{
			$totalCount = $totalCount + count($data);
		}
		else
		{
			$totalCount++;
		}
	}

	$input  = JFactory::getApplication()->input;
	$displayFilters = $this->userFilters;
	$totalHeadRows = count($displayFilters);

?>
<div id="reports-container">
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">
	<?php
		if (!empty($this->sidebar)):?>
			<div id="j-sidebar-container" class="span2">
				<?php echo $this->sidebar;?>
			</div><!--j-sidebar-container-->
			<div id="j-main-container" class="span10">
<?php 	else :?>
			<div id="j-main-container">
<?php	endif;
		if ($app->isSite())
		{
			if ($this->isExport)
			{
				?>
			<a class='btn'
				type='submit' onclick="Joomla.submitbutton('reports.csvexport'); jQuery('#task').val('');" href='#'><span title='Export'
				class='icon-download'></span><?php echo JText::_('COM_TJREPORTS_CSV_EXPORT'); ?></a>
	<?php	}	?>
			<span id="btn-cancel">
						<input type="text" name="queryName" autocomplete="off" placeholder="Title for the Query"  id="queryName"/>
					</span>
					<a class="btn btn-primary  saveData" type="button" id="saveQuery"
						onclick="tjrContentUI.report.saveThisQuery();"><?php echo
						 JText::_('COM_TJREPORTS_SAVE_THIS_QUERY'); ?></a>

					<button class="btn btn btn-default  cancel-btn" type="button" style="display:none;" onclick="tjrContentUI.report.cancel();">
						Cancel
					</button><hr>
<?php	}	?>
			<form action="<?php echo JRoute::_('index.php?option=com_tjreports&view=reports'); ?>" method="post" name="adminForm" id="adminForm" onsubmit="return tjrContentUI.report.submitForm();">
				<!--html code-->
				<div class="row-fluid">
					<div class="span4">
						<div class="form-group">
							<select class="form-control" id="report-select" onchange="tjrContentUI.report.loadReport(this,'<?php echo $this->client; ?>');">
							<?php foreach ($this->enableReportPlugins as $eachPlugin) :
									$this->model->loadLanguage($eachPlugin['plugin']);
									$selected = ' ';

									if ($this->reportId == $eachPlugin['reportId'])
									{
										$selected = 'selected="selected"';
									}

									$pluginName = strtoupper($eachPlugin['plugin']);
									$langConst = "PLG_TJREPORTS_" . $pluginName;
							?>
								<option value="<?php echo $eachPlugin['plugin'];?>" <?php echo $selected; ?> data-reportid="<?php echo $eachPlugin['reportId']; ?>">
									<?php echo $eachPlugin['title']; ?>
								</option>
						<?php	endforeach;	?>
							</select>
						</div><!--form-group-->
					</div><!--span3-->

					<div class="span1 pull-right">
						<div id="reportPagination" class="pull-right ">
							<?php echo $this->pagination->getLimitBox();?>
						</div>
					</div>

			<?php	if (!empty($this->savedQueries))
					{	?>
					<div class="span4">
						<?php	echo JHtml::_('select.genericlist', $this->savedQueries, "queryId", 'class="" size="1" onchange="tjrContentUI.report.getQueryResult(this.value);" name="filter_saveQuery"', "value", "text", $this->queryId);	?>
					</div><!--span3-->
					<div class="span1">
						<?php if ($this->queryId) { ?>
						<input type='button' value="<?php echo JText::_('COM_TJREPORTS_DELETE_QUERY'); ?>" class="btn btn-primary" onclick="tjrContentUI.report.deleteThisQuery();"/>
						<?php } ?>
					</div><!--span1-->
			<?php	}	?>
				</div><!--row-fluid-->
				<!--/html code-->

				<div class="report-top-bar row-fluid">
					<div class="row-fluid">

						<div class="show-hide-cols span3">
							<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="tjrContentUI.report.getColNames(); return false;" value="<?php echo JText::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>" />
							<ul id="ul-columns-name" class="ColVis_collection">
								<?php
								foreach ($this->showHideColumns as $colKey)
								{
									$checked 	= '';

									if (isset($this->columns[$colKey]['title']))
									{
										$colTitle = $this->columns[$colKey]['title'];
									}
									else
									{
										$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
									}

									if (in_array($colKey, $this->colToshow))
									{
										$checked 	= 'checked="checked"';
									}
									?>
									<li>
										<label>
											<input onchange="tjrContentUI.report.submitTJRData('showHide');" type="checkbox" value="<?php echo $colKey;	?>" <?php echo $checked; ?> name="colToshow[<?php echo $colKey;	?>]" id="<?php echo $colKey;	?>">
												<span><?php echo JText::_($colTitle);?></span>
										</label>
									</li>
								<?php
								}
								?>
							</ul>
						</div>
						<?php
						if ($totalHeadRows > 1)
						{
						?>
							<div class="span3">
								<button type="button" class="btn btn-default" id="show-filter" onclick="tjrContentUI.report.showFilter();">
									<?php echo JText::_("COM_TJREPORTS_SEARCH_TOOLS"); ?>
									<i class="fa fa-caret-down"></i>
								</button>
							</div>
						<?php
						}
						?>
					</div>

					<div class="js-stools-container-list hidden-phone hidden-tablet row-fluid">
						<div class="ordering-select hidden-phone show-tools" id="topFilters">
							<?php
								if ($totalHeadRows > 1)
								{
									$this->filters  = array_pop($displayFilters);
									$this->filterLevel = 1;

									echo $this->loadTemplate('filters');

									if ($this->srButton)
									{
										?>
										<div class="btn-group filter-btn-block control-group">
											<?php if ($this->srButton !== -1) { ?>
											<button class="btn hasTooltip" onclick="tjrContentUI.report.submitTJRData(); return false;" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT')?>"><i class="icon-search"></i>
											</button>
											<?php } ?>
											<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR')?>" onclick="tjrContentUI.report.resetSubmitTJRData('reset'); return false;"><i class="icon-remove"></i>
											</button>
										</div>
										<?php
									}
								}
							?>
						</div>
					</div>
					<!-- js-stools-container-list hidden-phone hidden-tablet span4 -->

					<div id="report-containing-div" class="tjlms-tbl row-fluid">
						<table id="report-table" class="table table-striped left_table ">
							<thead>
								<?php
								 jimport('joomla.filter.output');
								$filters = array();

								if (!empty($displayFilters))
								{
									$filters = array_pop($displayFilters);
								}

								for($i = $headerLevel; $i > 0 ; $i--)
								{
									echo '<tr>';

									foreach($this->colToshow as $index=>$detail)
									{
										if (!is_array($detail))
										{
											$hasFilter = isset($filters[$detail]);
										}

										if ($i == 1)
										{
											if (strpos($index, '::'))
											{
												$indexArray   = explode('::', $index);
												$contentTitle = $indexArray[0];
												$contentId    = $indexArray[0];

												foreach ($detail as $subKey => $subDetail)
												{
													$keyDetails   = explode('::', $subKey);

													if (!isset($this->columns[$subKey]['title']))
													{
														$subTextTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $keyDetails[0] . '_' . $keyDetails[1] . '_TITLE');
													}
													else
													{
														$subTextTitle = $this->columns[$subKey]['title'];
													}

													echo '<th class="subdetails ' . $keyDetails[0] . ' ' . $keyDetails[1] . '">';

													$colTitle = JText::sprintf($subTextTitle, $keyDetails[1]) ;

													if (in_array($subKey, $this->sortable))
													{
														echo $sortHtml = JHtml::_('grid.sort', $colTitle, $subKey, $this->listDirn, $this->listOrder);
													}
													else
													{
														echo '<div class="header_title">' . JText::_($colTitle) . '</div>';
													}

													echo '</th>';
												}
											}
											else
											{
												$colKey = $detail;
												$colKeyClass = JFilterOutput::stringURLSafe($colKey);
												if (!isset($this->columns[$colKey]['title']))
												{
													$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
												}
												else
												{
													$colTitle = $this->columns[$colKey]['title'];
												}

												echo '<th class="' . $colKeyClass  . '">';

												if ($hasFilter)
												{
													echo '<span class="table-heading">';
												}

												if (in_array($colKey, $this->sortable))
												{
													echo $sortHtml = JHtml::_('grid.sort', $colTitle, $colKey, $this->listDirn, $this->listOrder);
												}
												else
												{
													echo '<div class="header_title">' . JText::_($colTitle) . '</div>';
												}
												if ($hasFilter)
												{
													echo '<a href="#" title="search attempts" class="col-search">
																<i class="icon-search"></i>
															</a></span>';
												}

												if ($hasFilter)
												{
													$this->filterLevel = 2;

													$this->filters  = array($colKey => $filters[$colKey]);
													$this->colKey = $colKey;

													echo $this->loadTemplate('filters');
												}

												echo '</th>';
											}
										}
										elseif ($i == 2)
										{
											if (strpos($index, '::'))
											{
												$indexDetail = explode('::', $index, 2);

												echo '<th class="center" colspan="' . count($detail) . '">' . array_pop($indexDetail) . '</th>';
											}
											else
											{
												echo '<th>&nbsp;</th>';
											}
										}
									}
									echo '</tr>';
								}

								?>
							</thead>
							<tbody>
								<?php
								// Loop through items

								// No Result Found
								if (empty($this->items))
								{
									echo '<tr>
											<td class="center" colspan="' . $totalCount . '">No Results Found.</td>
										</tr>';
								}
								else
								{
									foreach($this->items as $itemKey => $item)
									{
										echo '<tr>';

										foreach ($this->colToshow as $arrayKey => $key)
										{
											if (is_array($key))
											{
												foreach($key as $subkey => $subVal)
												{
													$keyDetails   = explode('::', $subkey);
													echo '<td class="subdetails ' . $keyDetails[0] . ' ' . $keyDetails[1] . '">' .  $item[$arrayKey][$subkey] .'</td>';
												}
											}
											else
											{
												echo '<td class="' . $key . '">'. $item[$key] .'</td>';
											}
										}

										echo '</tr>';
									}
								}

								// Any message to display
								if (!empty($this->messages))
								{
									echo '
									<tr>
										<td colspan="' . $totalCount . '">
											<div class="alert alert-warning">
												' . implode('<br>', (array) $this->messages) . '
											</div>
										</td>
									</tr>';
								}
								?>
							</tbody>
						</table>
					</div>
					<!--report-containing-div-->

					<div id="pagination">
						<?php
						echo $footerLinks = $this->pagination->getListFooter();
						?>
					</div>

					<input type="hidden" id="filter_order" name="filter_order" value="<?php echo  $this->listOrder; ?>" />
					<input type="hidden" id="filter_order_Dir" name="filter_order_Dir" value="<?php echo  $this->listDirn; ?>" />
					<input type="hidden" id="reportId" name="reportId" value="<?php echo  $this->reportId; ?>" />
					<input type="hidden" id="reportToBuild" name="reportToBuild" value="<?php echo  $this->pluginName; ?>" />
					<input type="hidden" id="task" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<input type="hidden" name="client" id="client" value="<?php echo $this->client; ?>">

					<?php echo JHtml::_('form.token'); ?>
				</div>
				<!--report-top-bar row-fluid-->
			</form>
		</div>
		<!--j-main-container-->
	</div>
	<!-- COM_TJLMS_WRAPPER_DIV -->
</div>
<!-- reports-container -->
