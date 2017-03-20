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
JHtml::_('formbehavior.chosen', 'select');

$listOrder     = $this->escape($this->state->get('list.ordering'));
$listDirn      = $this->escape($this->state->get('list.direction'));

$input=JFactory::getApplication()->input;

?>
<form action="index.php?option=com_tjreports&view=tjreports" method="post" id="adminForm" name="adminForm">
<?php
		if (!empty($this->sidebar)):?>
				<div id="j-sidebar-container" class="span2">
					<?php echo $this->sidebar;?>
				</div>
				<div id="j-main-container" class="span10">
		<?php else :?>
				<div id="j-main-container">
		<?php endif;?>

	<div class="row-fluid">
		<div class="span6">
			<?php
				echo JLayoutHelper::render(
					'joomla.searchtools.default',
					array('view' => $this,'options' => array('filtersHidden' =>true))
				);
			?>
		</div>
	</div>
	<?php
	if (!empty($this->items)) :
	?>
	<table class="table table-striped table-hover">
		<thead>
		<tr>
			<th width="1%"></th>
			<th width="2%">
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this)" />
			</th>
			<th width="60%">
				<?php echo JHtml::_('grid.sort', 'Title', 'title', $listDirn, $listOrder);?>
			</th>

			<th width="60%">
				<?php echo JHtml::_('grid.sort', 'Plugins', 'plugin', $listDirn, $listOrder);?>
			</th>

			<th width="60%">
				<?php echo JHtml::_('grid.sort', 'Client', 'client', $listDirn, $listOrder);?>
			</th>

			<th width="60%">
				<?php echo JHtml::_('grid.sort', 'View Report', '', $listDirn, $listOrder);?>
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
				<?php foreach ($this->items as $i => $row) :

						if($row->default == 0)
						{
							$reportId = $row->parent;
						}
						else
						{
							$reportId = $row->id;
						}
						$link = JRoute::_('index.php?option=com_tjreports&task=tjreport.edit&id=' . $row->id.'&extension='.$input->get('extension','','STRING'));
						$report_link = JRoute::_('index.php?option=com_tjreports&view=reports&client=' . $row->client . '&reportToBuild='. $row->plugin . '&reportId=' . $reportId);
				?>
					<tr>
						<td><?php  echo $this->pagination->getRowOffset($i); ?></td>
						<td>
							<?php echo JHtml::_('grid.id', $i, $row->id); ?>
						</td>
						<td>
							<a href="<?php echo $link; ?>" title="<?php echo JText::_('COM_TJREPORTS_EDIT'); ?>">
								<?php echo $row->title; ?>
							</a>
						</td>
						<td align="center">
							<?php echo $row->plugin; ?>
						</td>
						<td align="center">
							<?php echo $row->client; ?>
						</td>
						<td align="center">
							<?php echo $row->parent; ?>
						</td>
						<td align="center">
							<?php echo "<a href=" . $report_link . "> View </a>"; ?>
						</td>
					</tr>
					</tr>
				<?php endforeach; ?>
		</tbody>
	</table>
	<?php else : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php endif; ?>

	<input type="hidden" name="task" value=""/>
	<input type="hidden" name="boxchecked" value="0"/>
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>"/>
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>"/>

	<?php echo JHtml::_('form.token'); ?>
</form>
<script>
	function checkAll(count)
	{
alert(count);
	}
</script>
