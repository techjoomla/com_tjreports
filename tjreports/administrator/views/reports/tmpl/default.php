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

	$tjrData        = $this->tjrData;
	$headerLevel    = $tjrData['headerLevel'];
	$displayFilters = $tjrData['displayFilters'];
	$listOrder      = $this->state->get('list.ordering');
	$listDirn       = $this->state->get('list.direction');
	$totalCount = 0;

	foreach ($tjrData['colToshow'] as $key=>$data)
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
	//~ echo '<pre>'.print_r($data,true).'</pre>';
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
					<div class="span12">
						<?php
						$class1 = !empty($this->savedQueries) ? 'span2' : 'span4';
						?>
						<div class="show-hide-cols <?php echo $class1 ?>">
							<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="tjrContentUI.report.getColNames(); return false;" value="<?php echo JText::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>" />
							<ul id="ul-columns-name" class="ColVis_collection" style="display:none">
								<?php
								foreach ($tjrData['showHideColumns'] as $detail)
								{
									$colTitle 	= $checked 	= '';

									if (is_array($detail))
									{
										$colKey   = $detail['key'];
										$colTitle = isset($detail['title']) ? $detail['title'] : '';
									}
									else
									{
										$colKey   = $detail;
									}

									if (!$colTitle)
									{
										$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
									}

									if (in_array($colKey, $tjrData['colToshow']))
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

						<div class="span1 pull-right">
							<div id="reportPagination" class="pull-right ">
								<?php echo $this->pagination->getLimitBox();?>
							</div>
						</div>
						<!--span1 pull-right-->

						<div class="span3 pull-right">
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

						<div class="js-stools-container-list hidden-phone hidden-tablet span4">
							<div class="ordering-select hidden-phone">
							<?php
							$totalHeadRows = count($displayFilters);
							if ($totalHeadRows > 1)
							{
								$topFilters = array_pop($displayFilters);

								foreach($topFilters as $topFilter)
								{
									if (!empty($topFilter['html']))
									{
									?>
									<div class="js-stools-field-list">
										<?php echo $topFilter['html'];?>
									</div>
									<?php
									}
								}
							}
							?>
							</div>
						</div>
						<!-- js-stools-container-list hidden-phone hidden-tablet span4 -->

					</div>
					<!-- span12 -->

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

									foreach($tjrData['colToshow'] as $index=>$detail)
									{
										if ($i == 1)
										{
											if (strpos($index, '::'))
											{
												foreach ($detail as $subKey => $subDetail)
												{
													$keyDetails   = explode('::', $subKey);

													$subTextTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $keyDetails[2] . '_' . $keyDetails[3] . '_TITLE');

													echo '<th class="subdetails ' . $keyDetails[2] . ' ' . $keyDetails[3] . '">';

													$colTitle = JText::sprintf($subTextTitle, $keyDetails[1]) ;

													if (in_array($subKey, $tjrData['sortable']))
													{
														echo $sortHtml = JHtml::_('grid.sort', $colTitle, $subKey, $listDirn, $listOrder);
														// str_replace('Joomla.tableOrdering', 'tjrContentUI.tableOrdering', $sortHtml);
													}
													else
													{
														echo JText::_($colTitle);
													}

													echo '</th>';
												}
											}
											else
											{
												if (is_array($detail) && !empty($detail['title']))
												{
													$colKey = $detail['key'];
													$colTitle = $detail['title'];
												}
												else
												{
													$colKey = $detail;
													$colTitle = 'PLG_TJREPORTS_' . strtoupper($this->pluginName . '_' . $colKey . '_TITLE');
												}

												echo '<th class="' . $colKey  . '">';

												if (in_array($colKey, $tjrData['sortable']))
												{
													echo $sortHtml = JHtml::_('grid.sort', $colTitle, $colKey, $listDirn, $listOrder);
													// str_replace('Joomla.tableOrdering', 'tjrContentUI.tableOrdering', $sortHtml);
												}
												else
												{
													echo JText::_($colTitle);
												}

												if (isset($filters[$colKey]['html']))
												{
													echo $filters[$colKey]['html'];
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

									foreach ($tjrData['colToshow'] as $arrayKey => $key)
									{
										if (is_array($key))
										{
											foreach($key as $subkey => $subVal)
											{
												$keyDetails   = explode('::', $subkey);
												echo '<td class="subdetails ' . $keyDetails[2] . ' ' . $keyDetails[3] . '">' .  $item[$arrayKey][$subkey] .'</td>';
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
								if (!empty($tjrData['messages']))
								{
									echo '
									<tr>
										<td colspan="' . $totalCount . '">
											<div class="alert alert-warning">
												' . implode('<br>', (array) $tjrData['messages']) . '
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
						// str_replace("Joomla.submitform();", "tjrContentUI.report.submitTJRData('pageChange');", $footerLinks);
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
