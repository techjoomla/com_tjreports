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

if (count($chartdata) > 0)
{
	foreach ($chartdata as $chart)
	{
		$chartType = ($chart['fieldType']  == 'radio' || $chart['fieldType']  == 'checkbox' || $chart['fieldType']  == 'rating') ? 'pie' : 'bar';
		?>
		<div class="col-xs-12 col-md-4 mb-15">
			<span><?php echo $chart['fieldLable']; ?></span>
			<canvas id="chart_<?php echo $chart['fieldId']; ?>">
			</canvas>
		</div>
		<script>
		var ctx = document.getElementById("chart_<?php echo $chart['fieldId']; ?>");
		var fieldOptions = "[<?php echo implode(",",$chart['chartData']['labels']); ?>]";
		var color = [];

		for (var i = 0; i < fieldOptions.length; i++)
		{
			var dynamicColors = function() {
				var r = Math.floor(Math.random() * 255);
				var g = Math.floor(Math.random() * 255);
				var b = Math.floor(Math.random() * 255);
				return "rgb(" + r + "," + g + "," + b + ")";
			};
			color.push(dynamicColors());
		}

		var myChart = new Chart(ctx, {
			type: "<?php echo $chartType; ?>",
			data: {
				labels: [<?php echo $chart['labels']; ?>],
				datasets: [{
					label: "<?php echo $chart['fieldLable']; ?>",
					data: [<?php echo $chart['data']; ?>],
					backgroundColor: color,
					borderWidth: 1
				}]
			}
		});
	</script>
		<?php
	}
}
else
{
	?>
	<div class="text-center"><?php echo Text::_('COM_TJREPORTS_NO_RECORDS_FOUND_SUMMARY')?></div>
	<?php
}
?>
