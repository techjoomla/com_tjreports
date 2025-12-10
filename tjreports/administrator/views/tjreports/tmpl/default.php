<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
HTMLHelper::stylesheet('administrator/components/com_tjreports/assets/css/tjreports.css');
HTMLHelper::_('behavior.multiselect'); // only for list tables

$user	= Factory::getUser();
$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape($this->state->get('list.direction'));

$saveOrder	= $listOrder == 'ordering';

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_tjreports&task=tjreports.saveOrderAjax&tmpl=component';
	HTMLHelper::_('sortablelist.sortable', 'reportList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

$input = Factory::getApplication()->input;
?>
<form action="index.php?option=com_tjreports&view=tjreports" method="post" id="adminForm" name="adminForm">
	<?php if (!empty($this->sidebar)):?>
		<div id="j-sidebar-container" class="col-md-2">
			<?php echo $this->sidebar;?>
		</div>
		<div id="j-main-container" class="col-md-10">
	<?php else :?>
		<div id="j-main-container">
	<?php endif;?>

	<div class="row">
		<div class="col-md-12">
			<?php
				echo LayoutHelper::render(
					'joomla.searchtools.default',
					array('view' => $this)
				);
			?>
		</div>
	</div>
	<?php if (!empty($this->items)) {?>
	<table class="table table-striped table-hover" id="reportList">
		<thead>
			<tr>
				<th width="2%" class="nowrap hidden-phone center">
					<?php echo HTMLHelper::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
				</th>

				<th width="2%">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
				</th>

				<th width="30%">
					<?php echo HTMLHelper::_('grid.sort', 'COM_TJREPORTS_FORM_LBL_REPORT_TITLE', 'title', $listDirn, $listOrder); ?>
				</th>

				<th width="20%">
					<?php echo HTMLHelper::_('grid.sort', 'COM_TJREPORTS_FORM_LBL_REPORT_PLUGIN', 'plugin', $listDirn, $listOrder); ?>
				</th>

				<th width="20%" class="center">
					<?php echo HTMLHelper::_('grid.sort', 'COM_TJREPORTS_FORM_LBL_REPORT_CLIENT', 'client', $listDirn, $listOrder); ?>
				</th>

				<th width="10%" class="center">
					<?php echo HTMLHelper::_('grid.sort', 'COM_TJREPORTS_LIST_SAVED_QUERY', 'savedquery', $listDirn, $listOrder); ?>
				</th>

				<th width="17%" class="center">
					<?php echo Text::_('COM_TJREPORTS_REPORTS_VIEW_REPORT');?>
				</th>
				<th width="17%" class="center">
					<?php echo HTMLHelper::_('grid.sort', 'COM_TJREPORTS_LIST_ID', 'id', $listDirn, $listOrder); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="8">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			$canEdit = $user->authorise('core.edit', 'com_tjreports');
			foreach ($this->items as $i => $row)
			{
				if($row->default == 0)
				{
					$reportId = $row->parent;
				}
				else
				{
					$reportId = $row->id;
				}
				$link = Route::_('index.php?option=com_tjreports&task=tjreport.edit&id=' . $row->id.'&client='.$input->get('client','','STRING'));
				$report_link = Route::_('index.php?option=com_tjreports&view=reports&client=' . $row->client . '&reportToBuild='. $row->plugin . '&reportId=' . $reportId);
				?>
				<tr>
					<td>
						<?php $canChange = $user->authorise('core.edit.state', 'com_tjreports'); ?>
						<?php $iconClass = ''; ?>
						<?php if (!$canChange) : ?>
							<?php $iconClass = ' inactive'; ?>
						<?php elseif (!$saveOrder) : ?>
						<?php $iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::tooltipText('JORDERINGDISABLED'); ?>
						<?php endif; ?>
						<span class="sortable-handler<?php echo $iconClass; ?>">
							<span class="icon-menu" aria-hidden="true"></span>
						</span>
						<?php if ($canChange && $saveOrder) : ?>
							<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $row->ordering; ?>" />
						<?php endif; ?>
					</td>
					<td>
						<?php echo HTMLHelper::_('grid.id', $i, $row->id); ?>
					</td>
					<td>
						<?php
						if ($canEdit)
						{
						?>
						<a href="<?php echo $link; ?>" title="<?php echo Text::_('COM_TJREPORTS_EDIT'); ?>">
							<?php echo htmlspecialchars($row->title, ENT_COMPAT, 'UTF-8'); ?>
						</a>
						<?php
						}
						else
						{
							echo htmlspecialchars($row->title, ENT_COMPAT, 'UTF-8');
						}
						?>
					</td>
					<td>
						<?php echo $row->plugin; ?>
					</td>
					<td class="center">
						<?php echo $row->client; ?>
					</td>
					<td class="center">
						<?php echo $row->savedquery; ?>
					</td>
					<td class="center">
						<a href="<?php echo $report_link; ?>"><?php echo Text::_('COM_TJREPORTS_REPORTS_VIEW');?></a>
					</td>
					<td class="center">
						<?php echo $reportId; ?>
					</td>
				</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php }else{?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php } ?>
	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="extension" value="<?php echo $input->get('extension','','word'); ?>">
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo HTMLHelper::_('form.token'); ?>
</form>
