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
?>
<div id="reports-container">
	<?php
	JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

	$headerLevel    = $this->headerLevel;
	$listOrder      = $this->state->get('list.ordering');
	$listDirn       = $this->state->get('list.direction');
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
	?>
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?>">
	<?php
	//~ ob_start();
	//~ $layoutOutput = ob_get_contents();
	//~ ob_end_clean();
	//~ echo $layoutOutput;

	// <!--// JHtmlsidebar for menu ends-->
	if (!empty($this->sidebar)):?>
		<div id="j-sidebar-container" class="span2">
			<?php echo $this->sidebar;?>
		</div><!--j-sidebar-container-->
		<div id="j-main-container" class="span10">
	<?php else :?>
		<div id="j-main-container">
	<?php endif;?>

			<form action="<?php echo JRoute::_('index.php?option=com_tjreports&view=reports'); ?>" method="post" name="adminForm" id="adminForm" onsubmit="return tjrContentUI.report.submitForm();">
				<div class="report-top-bar row-fluid">
					<div class="span12 form-inline-header">
						<?php
						$class1 = !empty($this->savedQueries) ? 'span4' : 'span6';
						?>
						<div class="show-hide-cols <?php echo $class1 ?>">
							<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="tjrContentUI.report.getColNames(); return false;" value="<?php echo JText::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>" />
							<ul id="ul-columns-name" class="ColVis_collection" style="display:none">
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
						<!--show-hide-cols-->

						<div class="span2 pull-right">
							<div id="reportPagination" class="pull-right ">
								<?php echo $this->pagination->getLimitBox();?>
							</div>
						</div>
						<!--span1 pull-right-->

						<div class="span4 pull-right">
							<input type="text" name="queryName" placeholder="Title for the Query" class="pull-right" style="display:none" id="queryName" />
							<input type="button" class="btn btn-primary pull-right" id="saveQuery" onclick="tjrContentUI.report.saveThisQuery();" value="<?php echo JText::_('COM_TJREPORTS_SAVE_THIS_QUERY'); ?>" />
						</div>
						<!--span3 pull-right-->
						<?php
						if (!empty($this->savedQueries))
						{ ?>
							<div class="span2 pull-right">
									<?php echo JHtml::_('select.genericlist', $this->savedQueries, "queryId", 'class="" size="1" onchange="tjrContentUI.report.getQueryResult(this.value);" name="filter_saveQuery"', "value", "text", $this->queryId);
									?>
							</div>
							<!--span2 pull-right-->
						<?php
						}
						?>
					</div>
					<!-- span12 -->


					<div class="js-stools-container-list hidden-phone hidden-tablet row-fluid">
						<div class="ordering-select hidden-phone" id="topFilters">
							<?php
								$displayFilters = $this->userFilters;
								$totalHeadRows = count($displayFilters);
								if ($totalHeadRows > 1)
								{
									$this->filters  = array_pop($displayFilters);
									echo $this->loadTemplate('filters');

									if ($this->srButton)
									{
										?>
										<div class="btn-group filter-btn-block control-group">
											<?php if ($this->srButton !== -1) { ?>
											<button class="btn hasTooltip" onclick="tjrContentUI.report.submitTJRData(); return false;" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT')?>"><i class="icon-search"></i>
											</button>
											<?php } ?>
											<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR')?>" onclick="tjrContentUI.report.resetSubmitTJRData(); return false;"><i class="icon-remove"></i>
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
														echo $sortHtml = JHtml::_('grid.sort', $colTitle, $subKey, $listDirn, $listOrder);
														// str_replace('Joomla.tableOrdering', 'tjrContentUI.tableOrdering', $sortHtml);
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

												if (!isset($this->columns[$colKey]['title']))
												{
													$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
												}
												else
												{
													$colTitle = $this->columns[$colKey]['title'];
												}

												echo '<th class="' . $colKey  . '">';

												if (in_array($colKey, $this->sortable))
												{
													echo $sortHtml = JHtml::_('grid.sort', $colTitle, $colKey, $listDirn, $listOrder);
													// str_replace('Joomla.tableOrdering', 'tjrContentUI.tableOrdering', $sortHtml);
												}
												else
												{
													echo '<div class="header_title">' . JText::_($colTitle) . '</div>';
												}

												if (isset($filters[$colKey]))
												{
													$this->filters  = array($colKey => $filters[$colKey]);
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
								foreach((array) $this->items as $itemKey => $item)
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

								// No Result Found
								if (empty($this->items))
								{
									echo '<tr>
											<td class="center" colspan="' . $totalCount . '">No Results Found.</td>
										</tr>';
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
						// str_replace("Joomla.submitform();", "tjrContentUI.report.submitfilter('pageChange');", $footerLinks);
						?>
					</div>

					<input type="hidden" id="filter_order" name="filter_order" value="<?php echo  $listOrder; ?>" />
					<input type="hidden" id="filter_order_Dir" name="filter_order_Dir" value="<?php echo  $listDirn; ?>" />
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
