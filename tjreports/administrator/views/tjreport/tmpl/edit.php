<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;
JHtml::_('behavior.formvalidator');
?>
<script>
	jQuery('#myTabs a').click(function (e) {
		e.preventDefault()
	jQuery(this).tab('show')
})


jQuery('#myTabs a[href="#profile"]').tab('show') // Select tab by name
jQuery('#myTabs a:first').tab('show') // Select first tab
jQuery('#myTabs a:last').tab('show') // Select last tab
jQuery('#myTabs li:eq(2) a').tab('show') // Select third tab (0-indexed)

</script>

<form action="<?php echo JRoute::_('index.php?option=com_tjreports&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" name="adminForm" id="adminForm" class="form-validate">


<div class="form-horizontal">

	<fieldset class="adminform">

		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist">
		<li role="presentation" class="active"><a href="#details" aria-controls="details" role="tab" data-toggle="tab">Details</a> </li>
		<li role="presentation"><a href="#permissions" aria-controls="permissions" role="tab" data-toggle="tab">Permissions</a></li>
		</ul>

		<!-- Tab panes -->
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="details">
				<div class="control-group" style="display:none">
					<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('userid'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('userid'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('title'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('title'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('client'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('client'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('pluginlist'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('pluginlist'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('param'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('param'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('default'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('default'); ?></div>
				</div>
			</div>

			<div role="tabpanel" class="tab-pane" id="permissions">
					<div class="control-group">
						<div class="controls"><?php echo $this->form->getInput('rules'); ?></div>
					</div>
			</div>
		</div>

	</fieldset>

</div>

    <input type="hidden" name="jform[parent]" value="" id="jform_parent" />

    <input type="hidden" name="jform[plugin]" value="" id="jform_plugin" />

    <input type="hidden" name="task" value="tjreport.edit" />
    <?php echo JHtml::_('form.token'); ?>
</form>

<script>

jQuery( "#jform_client" ).change(function(e) {

		var userid = jQuery('#jform_userid_id').val();
		var client = jQuery('#jform_client').val();
		var user = jQuery('#jform_userid').val();

		if(user == "")
		{
			alert("Please select user");
			jQuery("#jform_client option:first").attr('selected','selected');
			e.preventDefault();
		}

		var path = window.location.pathname + "?option=com_tjreports&task=tjreport.getplugins";

		jQuery("#jform_pluginlist option").remove();
		jQuery("#jform_param").val('');

		if(userid.length != 0 && client !="")
		{
			var data = 'user_id=' + userid+'&client=' + client;

			jQuery.ajax({
			url: path,
			type: 'post',
			data : data,
			dataType: 'json',

			success: function(resp)
			{
				// Append option to plugin dropdown list.
				var list = jQuery("#jform_pluginlist");
				list.append(new Option("<?php  echo JText::_('COM_TJREPORTS_FORM_DEFAULT_OPTION');  ?>"),0);
					jQuery.each(resp, function(index, item) {
					list.append(new Option(item.text, item.value));
				});
			}});
		}
});

jQuery( "#jform_pluginlist" ).change(function(e) {

		var plugin_id = jQuery('#jform_pluginlist').val();

		var path = window.location.pathname + "?option=com_tjreports&task=tjreport.getparams";

		if(plugin_id !="")
		{
			var data = 'plugin_id=' + plugin_id;

			jQuery.ajax({
			url: path,
			type: 'post',
			data : data,
			dataType: 'json',

			success: function(resp)
			{
				//	jQuery('#jform_plugin').val();
				jQuery("#jform_param").val(resp.param.toString());

				// Add plugin value to hidden plugin variable
				jQuery("#jform_plugin").val(resp.plugin);
				jQuery("#jform_parent").val(resp.id);

			}});
		}

});

</script>
