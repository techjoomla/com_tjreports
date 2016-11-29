<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;

$xml = JFactory::getXML(JPATH_SITE . '/administrator/components/com_tjreports/tjreports.xml');
$version = $xml->version;
?>
<script>
/* no need
	techjoomla.jQuery(document).ready(function()
	{
		callXML('<?php echo $version; ?>');
	});

	function callXML(currversion, latestVar)
	{
		techjoomla.jQuery.ajax({
			url: "index.php?option=com_tjlms&task=dashboard.getLatestVersion",
			type: "POST",
			dataType: "json",
			success: function(data) {
				var latestver = data.version;
				generateOutput(currversion, latestver)
			}
		});
	}


	function generateOutput(currversion, latestver)
	{
		techjoomla.jQuery.ajax({
			url: "index.php?option=com_tjlms&task=dashboard.generateOutput",
			type: "GET",
			data:{currVer : currversion, latestver : latestver},
			dataType: "HTML",
			success: function(data) {
			  jQuery('#version-widget').html(data);
			}
		});
	}
*/
	function openMenu(thismeubutton)
	{
		techjoomla.jQuery(thismeubutton).closest('.tjlms-menu').toggleClass('open');
	}

	function tjlmstogglesidebar()
	{
		techjoomla.jQuery('body.com_tjlms #j-sidebar-container').toggleClass('tjlms-sidebar-hidden');
		techjoomla.jQuery('body.com_tjlms #j-main-container').toggleClass('tjlms-full-screen');
		techjoomla.jQuery('body.com_tjlms #j-sidebar-container #version-widget').toggleClass('tjlms-hide');
	}

</script>


<!--<div class="tjlms-header row-fluid">
	<div class="tjlms-menu hidden-desktop hidden-tablet pull-right">
		<a class="btn btn-tjlms-menu" onclick="openMenu(this)">
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</a>
		<?php /*if (!empty( $this->sidebar))
		{
		?>
			<div class="tjlms-menu-content">
				<?php echo $this->sidebar; ?>
			</div>
		<?php
		}*/
		?>
	</div>
	<div class="tjlms-version-heading">
		<span style="display:inline-block;">
			<?php echo JText::sprintf('COM_TJLMS_HAVE_INSTALLED_VER', $version); ?>
		</span>
		<span id="newVersionNotice" style="display:inline-block;">
		</span>
	</div>
</div>-->

<?php
	if (!empty($this->sidebar))
	{
	?>
		<div id="j-sidebar-container" class="span2" >
			<?php echo $this->sidebar; ?>
			<div id="version-widget">
			</div>
		</div>
		<div id="j-main-container" class="span10">
<?php
	}
	else
	{
?>
		<div id="j-main-container">
	<?php
	}
