<?php
/**
 * @package      TJRports
 * @subpackage   com_tjreports
 *
 * @author       Techjoomla <extensions@techjoomla.com>
 * @copyright    Copyright (C) 2009 - 2019 Techjoomla. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;

$chartdata = $this->items;

// Items conditional classes
if (count($chartdata) > 2){
	$itemsClass ="col-xs-12 col-md-4 chartItems";
}
else
{
    $itemsClass ="col-xs-12 col-md-6 chartItems";
}

if (count($chartdata) > 0)
{ ?>
	<div class="equal-height-col">
	<?php
	foreach ($chartdata as $chart)
	{
		$chartType = ($chart['fieldType']  == 'radio' || $chart['fieldType']  == 'checkbox' || $chart['fieldType']  == 'rating') ? 'pie' : 'bar';
		?>
		<div class="<?php echo $itemsClass; ?>">
			<div class="tj-card">
				<h4 class="tj-card-title">
					<?php echo $chart['fieldLable']; ?>
				</h4>
				<div class="tj-card-body">
					<canvas id="chart_<?php echo $chart['fieldId']; ?>">
					</canvas>
				</div>
			</div>
		</div>
		<script>
		var ctx = document.getElementById("chart_<?php echo $chart['fieldId']; ?>");
		var fieldOptions = `"[<?php echo implode(",",$chart['chartData']['labels']); ?>]"`;
		var color = [];

		for (var i = 0; i < fieldOptions.length; i++)
		{
			var dynamicColors = function() {
				var r = Math.floor(Math.random() * 255,0.9);
				var g = Math.floor(Math.random() * 255,0.9);
				var b = Math.floor(Math.random() * 255,0.9);
				return "rgb(" + r + "," + g + "," + b + ")";
			};
			color.push(dynamicColors());
		}

		var myChart = new Chart(ctx, {
			type: "<?php echo $chartType; ?>",
			data: {
				labels: [<?php echo $chart['labels']; ?>],
				datasets: [{
					label: `"<?php echo $chart['fieldLable']; ?>"`,
					data: [<?php echo $chart['data']; ?>],
					backgroundColor: color,
					borderWidth: 1
				}]
			},
			options: {
				responsive: true,
				legend:{
				position: 'bottom',
					labels:{
						fontColor: "gray",
						boxWidth: 15,
						padding: 20,
						fontStyle:'400',
						fontSize:15
					}
				}
			}
		});
	</script>
		<?php
	} ?>
	</div>
	<?php
}
else
{
	?>
	<div class="text-center alert alert-danger"><?php echo Text::_('COM_TJREPORTS_NO_RECORDS_FOUND_SUMMARY')?></div>
	<?php
}
?>

