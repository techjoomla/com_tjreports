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

use Joomla\CMS\Plugin\PluginHelper;
Use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filter\OutputFilter;

HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');

$emailColmClass = 'td-sendemail';
$emailColumCnt  = 0;
$app             = Factory::getApplication();
$headerLevel     = $this->headerLevel;
$this->listOrder = $this->state->get('list.ordering');
$this->listDirn  = $this->state->get('list.direction');
$totalCount      = 0;

foreach ($this->colToshow as $key => $data)
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

$input                = Factory::getApplication()->input;
$displayFilters       = $this->userFilters;
$totalHeadRows        = count($displayFilters);
$reportId             = $app->getUserStateFromRequest('reportId', 'reportId', '');
$user                 = Factory::getUser();
$userAuthorisedExport = $user->authorise('core.export', 'com_tjreports.tjreport.' . $reportId);

if ($app->isClient('site'))
{
	$siteUrl               = Uri::root();
	$message               = array();
	$message['success']    = Text::_("COM_TJREPORTS_EXPORT_FILE_SUCCESS");
	$message['error']      = Text::_("COM_TJREPORTS_EXPORT_FILE_ERROR");
	$message['inprogress'] = Text::_("COM_TJREPORTS_EXPORT_FILE_NOTICE");
	$message['text']       = Text::_("COM_TJREPORTS_CSV_EXPORT");

	HTMLHelper::script(Uri::base() . 'libraries/techjoomla/assets/js/tjexport.js');
	$document = Factory::getDocument();
	$csv_url  = 'index.php?option=' . $input->get('option') . '&view=' . $input->get('view') . '&format=csv';

	$document->addScriptDeclaration("var csv_export_url='{$csv_url}';");
	$document->addScriptDeclaration("var csv_export_success='{$message['success']}';");
	$document->addScriptDeclaration("var csv_export_error='{$message['error']}';");
	$document->addScriptDeclaration("var csv_export_inprogress='{$message['inprogress']}';");
	$document->addScriptDeclaration("var tj_csv_site_root='{$siteUrl}';");
}

if ($this->showSummaryReport == 'Yes')
{
	HTMLHelper::_('script', 'media/com_tjreports/js/chartBundle.min.js');
}

?>
<div id="reports-container">
	<div class="<?php echo COM_TJLMS_WRAPPER_DIV ?> tjBs5">
		<div id="j-main-container">
		<?php
		if ($app->isClient('site') && isset($this->reportData->title))
		{
		?>
			<h2 class="title"><?php echo htmlspecialchars($this->reportData->title, ENT_COMPAT, 'UTF-8'); ?></h2>
		<?php
		}
		?>
			<form action="<?php echo Route::_('index.php?option=com_tjreports&view=reports'); ?>" method="post" name="adminForm" id="adminForm" onsubmit="return tjrContentUI.report.submitForm();">
				<!--html code-->
				<div class="row">
					<div class="col-md-4 col-sm-6 col-xs-10">
						<div class="form-group">
							<select class="form-select" id="report-select" onchange="tjrContentUI.report.loadReport(this,'<?php echo $this->client; ?>');">
							<?php
								foreach ($this->enableReportPlugins as $eachPlugin) :
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
							<?php
								endforeach;
							?>
							</select>
						</div>
						<!--form-group-->
					</div>
					<!--col-md-3-->
					<div class="col-xs-2 col-sm-6 col-md-8">
						<div id="reportPagination" class="float-end">
							<?php
							/*if (!$app->isClient('administrator'))
							{
								echo $this->pagination->getPaginationLinks('joomla.pagination.links', array('showPagesLinks' => false,'showLimitStart' => false));
							}
							else
							{
								echo $this->pagination->getLimitBox();
							}*/

							$defaultLimits = array(5, 10, 15, 20, 25, 30, 50, 100, 200, 500);
							$limits        = array();

							// Make the option list.
							foreach ($defaultLimits as $lm)
							{
								$limits[] = (object) array(
									'value' => $lm,
									'text' => Text::_('J' . $lm),
								);
							}

							// Build the select list.
							echo HTMLHelper::_(
								'select.genericlist',
								$limits,
								'limit',
								'class="form-select" size="1" onchange="Joomla.submitform();"',
								'value',
								'text',
								$this->pagination->limit
							);
							?>
						</div>
					</div>
					<!--/col-md-1-->
				</div>
				<!--row-->
				<!--/html code-->
				<div class="row">
					<?php
					if (!empty($this->savedQueries))
					{
					?>
						<div class="col-md-4 col-sm-4">
							<?php	echo HTMLHelper::_('select.genericlist', $this->savedQueries, "queryId", 'class="" size="1" onchange="tjrContentUI.report.getQueryResult(this.value);" name="filter_saveQuery"', "value", "text", $this->queryId);
							?>
							<?php
							if ($this->queryId)
							{
							?>
								<a class="btn btn-default" onclick="tjrContentUI.report.deleteThisQuery();">
									<i class="fa fa-trash"></i>
								</a>
							<?php
							}
							?>
						</div>
					<!--/col-md-4-->
					<?php
					}

					if ($app->isClient('site'))
					{
						if ($this->isExport)
						{
						}
					?>
						<div class="col-sm-7 col-md-12 mt-2">
							<span id="btn-cancel">
								<input type="text" name="queryName" autocomplete="off" placeholder="Title for the Query"  id="queryName"/>
							</span>
							<a class="btn btn-primary  saveData" type="button" id="saveQuery"
							onclick="tjrContentUI.report.saveThisQuery();">
								<?php echo Text::_('COM_TJREPORTS_SAVE_THIS_QUERY'); ?>
							</a>
							<button class="btn btn btn-default cancel-btn " type="button" style="display:none;" onclick="tjrContentUI.report.cancel();">Cancel</button>
						</div>
					<?php
					}
					?>
					<!--/col-md-5-->
				</div>
				<!--/row-->
				<div class="report-top-bar">
					<div class="row form-group">
						<?php
						if ($totalHeadRows > 1)
						{
						?>
							<div class="col-md-3 col-sm-4">
								<button type="button" class="btn btn-custom btn-block btn-primary" id="show-filter" onclick="tjrContentUI.report.showFilter();">
									<?php echo Text::_("COM_TJREPORTS_SEARCH_TOOLS"); ?>
									<i class="fa fa-caret-down"></i>
								</button>
							</div>
					  <?php
						}
						?>
					<!--/col-md-2-->
							<div class="show-hide-cols col-md-3 col-sm-3">
								<input type="button" id="show-hide-cols-btn" class="btn btn-success" onclick="tjrContentUI.report.getColNames(); return false;" value="<?php echo Text::_('COM_TJREPORTS_HIDE_SHOW_COL_BUTTON'); ?>" />
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
											$checked = 'checked="checked"';
										}
									?>
										<li>
											<label>
												<input onchange="tjrContentUI.report.submitTJRData('showHide');" type="checkbox" value="<?php echo $colKey;	?>" <?php echo $checked; ?> name="colToshow[<?php echo $colKey;	?>]" id="<?php echo $colKey;	?>">
												<span><?php echo Text::_($colTitle);?></span>
											</label>
										</li>
									<?php
									}
								?>
								</ul>
							</div>
							<div class="col-md-2 col-sm-3">
								<button type="button" class="btn btn-primary" onclick="tjrContentUI.report.resetSubmitTJRData('reset'); return false;">
									<?php echo Text::_('JCLEAR'); ?>
								</button>
							</div>
							<span id="sendEmail">
							</span>
					<!--/col-md-2-->

					<!--/col-md-3-->
					<?php if ($this->showSummaryReport == 'Yes')
							{
								$reportContainer = "hide";
									?>
								<div class="show-hide-cols col-md-5 col-sm-5 inputbox radio btn-group btn-group-yesno radio summaryReport">
									<input type="radio" id="detailsReport" name="displayReport" value="1" class="displayReport hide"  onclick="javascript:tjrContentUI.report.submitTJRData('default');" required="" aria-required="true"><label id="detailsReportLabel" for="detailsReport" class="btn btn-success"><?php echo Text::_('COM_TJREPORTS_REPORT_DETAILS'); ?></label>
									<input type="radio" id="summaryReport" name="displayReport" value="0" class="displayReport hide" required="" aria-required="true" onclick="javascript:tjrContentUI.report.submitTJRData('summary');"><label id="summaryReportLabel" for="summaryReport" class="btn"><?php echo Text::_('COM_TJREPORTS_REPORT_SUMMARY'); ?></label>
										</fieldset>
								</div>
								<?php
							}
					?>
					<!--/col-md-3-->

					  <?php
						if (!$app->isClient('administrator') && $userAuthorisedExport && $user)
						{
						?>
							<div class="col-sm-5 col-md-12">
								<a onclick="tjexport.exportCsv(0)" class="btn btn-small export pull-right">
									<i class='fa fa-download'></i>&nbsp;<?php echo Text::_('COM_TJREPORTS_CSV_EXPORT'); ?>
								</a>
							</div>
					  <?php
						}
						?>
					<!--/col-md-2-->
					</div>
					<!--/row-->
					<div class="js-stools-container-list hidden-phone hidden-tablet row">
						<div class="ordering-select hidden-phone show-tools col-md-12" id="topFilters">
							<?php
							if ($totalHeadRows > 1)
							{
								$this->filters  = array_pop($displayFilters);
								$this->filterLevel = 1;
								echo $this->loadTemplate('filters');

								if ($this->srButton)
								{
								?>
									<div class="btn-group filter-btn-block control-group mt-0">
									<?php
										if ($this->srButton !== -1)
										{
										?>
										<div class="col-md-2">
											<button class="btn hasTooltip btn-primary" onclick="tjrContentUI.report.submitTJRData(); return false;" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT')?>">
												<i class="fa fa-search"></i>
											</button>
											</div>
										<?php
										} ?>
                                       <!-- <div class="col-md-2">
										<button class="btn hasTooltip btn-primary" type="button" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR')?>" onclick="tjrContentUI.report.resetSubmitTJRData('reset'); return false;">
											<i class="fa fa-remove"></i>
										</button>
										</div> -->
									</div>
							 <?php
								}
							}
							?>
						</div>
					<!--/col-md-12-->
					</div>
					<!--/row-->
					<!-- js-stools-container-list hidden-phone hidden-tablet span4 -->
					<div id="report-containing-div" class="row">
						<div class="col-xs-12">
							<div class="table-responsive report-tbl">
								<table id="report-table" class="table table-striped left_table">
									<thead>
										<?php
										$filters = array();

										if (!empty($displayFilters))
										{
											$filters = array_pop($displayFilters);
										}

										for($i = $headerLevel; $i > 0 ; $i--)
										{
											echo '<tr class="report-row">';

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

															$colTitle = Text::sprintf($subTextTitle, $keyDetails[1]) ;

															if (in_array($subKey, $this->sortable))
															{
																echo $sortHtml = HTMLHelper::_('grid.sort', $colTitle, $subKey, $this->listDirn, $this->listOrder);
															}
															else
															{
																echo '<div class="header_title">' . Text::_($colTitle) . '</div>';
															}

															echo '</th>';
														}
													}
													else
													{
														$colKey = $detail;
														$colKeyClass = OutputFilter::stringURLSafe($colKey);
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
															echo $sortHtml = HTMLHelper::_('grid.sort', $colTitle, $colKey, $this->listDirn, $this->listOrder);
														}
														else
														{
															echo '<div class="header_title">' . Text::_($colTitle) . '</div>';
														}
														if ($hasFilter)
														{
															echo '<a href="#" title="search" class="col-search">
																		<i class="fa fa-search"></i>
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
														$isSendEmailClass = '';

														if ($key == $this->emailColumn)
														{
															$isSendEmailClass = $emailColmClass;
															$emailColumCnt++;
														}

                            $value = isset($item[$key]) ? $item[$key] : '';
                            
                            // Wrap the report values
                            $value = wordwrap($value, 30, "\n", true);
														echo "<td class=\"{$key} {$isSendEmailClass} \">{$value}</td>";
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
						</div>
						<!--/col-md-12-->
					</div>
					<!--report-containing-div-->
					<?php
					if (!$app->isClient('administrator'))
					{
						echo $this->pagination->getPaginationLinks('joomla.pagination.links', array('showLimitBox' => false));
					}
					else
					{
						?>
						<div id="pagination" class="pull-right">
							<?php echo $this->pagination->getListFooter();?>
						</div>
					<?php
					}
					?>
					<input type="hidden" id="filter_order" name="filter_order" value="<?php echo  $this->listOrder; ?>" />
					<input type="hidden" id="filter_order_Dir" name="filter_order_Dir" value="<?php echo  $this->listDirn; ?>" />
					<input type="hidden" id="reportId" name="reportId" value="<?php echo  $this->reportId; ?>" />
					<input type="hidden" id="reportToBuild" name="reportToBuild" value="<?php echo  $this->pluginName; ?>" />
					<input type="hidden" id="task" name="task" value="" />
					<input type="hidden" name="boxchecked" value="0" />
					<input type="hidden" name="client" id="client" value="<?php echo $this->client; ?>">
					<?php echo HTMLHelper::_('form.token'); ?>
				</div>
				<!--report-top-bar row-fluid-->
			</form>
		</div>
		<!--j-main-container-->
		</div>
	<!-- COM_TJLMS_WRAPPER_DIV -->
</div>
<!-- reports-container -->
<!-- reports-container -->
<?php
// If plg_system_sendemail enable then load following js
if ($emailColumCnt > 0 && PluginHelper::isEnabled('system', 'tjsendemail'))
{
	HTMLHelper::script('media/editors/tinymce/tinymce.min.js');
	HTMLHelper::script('plugins/system/tjsendemail/assets/bulksendemail.min.js');
}
