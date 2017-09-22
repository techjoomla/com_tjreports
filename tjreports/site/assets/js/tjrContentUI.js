/*
 * @version    SVN:<SVN_ID>
 * @package    com_tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
var tjrContentUI       = (typeof tjrContentUI == 'undefined') ? {} : tjrContentUI;
tjrContentUI.root_url  = (typeof root_url == 'undefined') ? '' : root_url;
tjrContentUI.base_url = (typeof root_url == 'undefined') ? '' : root_url;
tjrContentUI.report    = tjrContentUI.report ? tjrContentUI.report : {};

jQuery.extend(tjrContentUI.report, {
	$form: null,
	url: tjrContentUI.base_url + 'index.php?option=com_tjreports&view=reports&format=json',
	querySaveUrl: tjrContentUI.base_url + 'index.php?option=com_tjreports&format=json',
	submitTJRData: function(task) {
		tjrContentUI.utility.loadingLayer('show');
		this.$form = jQuery('#adminForm');
		var doProcess = this.validate();
		if (!doProcess) {
			return false;
		}
		jQuery(".hasPopover").popover('destroy')
		var promise = tjrContentService.postData(this.url, this.$form.serialize());//, {'datatype':'html'}
		promise.fail(
			function(response) {
				//console.log(response, ' error_response');
				console.log('Something went wrong.');
				tjrContentUI.utility.loadingLayer('hide');

				if (response.status == 403)
				{
					alert(Joomla.JText._('JERROR_ALERTNOAUTHOR'));
				}
			}
		).done(
			function(response) {
				// console.log(response, ' success_response');
				var containerSel = '#j-main-container';
				tjrContentUI.utility.loadingLayer('hide');
				var responseHTML = jQuery(response['html']).find(containerSel).html();
				jQuery(containerSel).html(responseHTML);

				// Reinitialze some js like for calandar, tooltip, chosen
				jQuery(".hasPopover").popover({"html": true,"trigger": "hover focus","container": "body"});

				if (task == "showHide")
				{
					tjrContentUI.report.getColNames();
				}

				var elements = jQuery(containerSel + " .field-calendar");
				for (i = 0; i < elements.length; i++) {
					JoomlaCalendar.init(elements[i]);
				}

				if(jQuery.prototype.chosen){
					jQuery(containerSel + ' select').chosen();
				}
			}
		);
	},
	showFilter: function(){
		jQuery('#show-filter').toggleClass('btn-primary');
		jQuery('#topFilters').slideToggle('1000');
		jQuery('.fa', this).toggleClass('fa-caret-up').toggleClass('fa-caret-down');
	},
	resetSubmitTJRData : function(){
		jQuery(':input','#topFilters')
		 .not(':button, :submit, :reset, input:hidden')
		 .val('')
		 .removeAttr('checked')
		 .removeAttr('selected');
		tjrContentUI.report.submitTJRData();
	},
	validate: function() {
		return true;
	},
	getColNames: function()
	{
		techjoomla.jQuery('.ColVis_collection').toggle();
	},
	cancel : function(){
		jQuery('#btn-cancel').hide();
		jQuery(".saveData").html('Want to save this?');
		jQuery('#adminForm1 input[type="text"]').val('');
		jQuery('.cancel-btn').hide();
		jQuery('#queryName').val('');
	},
	saveThisQuery: function()
	{
		this.$form = jQuery('#adminForm');
		var inputHidden = jQuery('#queryName').is(":hidden");
		jQuery('#btn-cancel').show();
		jQuery('.cancel-btn').show();
		jQuery('input').css('margin-bottom','0px');

		if (inputHidden == 1)
		{
			jQuery('#queryName').show();
			jQuery('#saveQuery').html(Joomla.JText._('COM_TJREPORTS_SAVE_QUERY'));
		}
		else
		{
			var queryName = jQuery('#queryName').val();

			if (queryName === '')
			{
				alert(Joomla.JText._('COM_TJREPORTS_ENTER_TITLE'));
				return false;
			}
			else
			{
				jQuery('#task', this.$form).val('reports.saveQuery');
				var formData = this.$form.serialize();
				jQuery('#task', this.$form).val('');

				tjrContentUI.utility.loadingLayer();
				var promise = tjrContentService.postData(this.querySaveUrl + "&queryName=" + queryName, formData);
				promise.fail(
					function(response) {
						//console.log(response, ' error_response');
						console.log('Something went wrong.');
						tjrContentUI.utility.loadingLayer('hide');;
					}
				).done(
					function(response) {
						// console.log(response, ' success_response');
						tjrContentUI.utility.loadingLayer('hide');
						if (response.success)
						{
							window.location.reload();
						}
						else
						{
							console.log('Something went wrong.', response.message, response.messages)
						}
					}
				);
			}
		}
	},
	deleteThisQuery: function()
	{
		this.$form = jQuery('#adminForm');
		var inputHidden = jQuery('#queryName').is(":hidden");

		var queryId = jQuery('#queryId').val();

		if (queryId === '')
		{
			alert('Select Any of the query');
			return false;
		}
		else
		{
			deletemsg = Joomla.JText._('COM_TJREPORTS_DELETE_MESSAGE');
			var comfirmDelete = confirm(deletemsg);
			if(comfirmDelete)
			{
				jQuery('#task', this.$form).val('reports.deleteQuery');
				var formData = this.$form.serialize();
				tjrContentUI.utility.loadingLayer();
				var promise = tjrContentService.postData(this.querySaveUrl + "&queryId=" + queryId, formData);
				promise.fail(
					function(response) {
						console.log('Something went wrong.');
						tjrContentUI.utility.loadingLayer('hide');;
					}
				).done(
					function(response) {
						tjrContentUI.utility.loadingLayer('hide');
						if (response.success)
						{
							window.location.reload();
						}
						else
						{
							console.log('Something went wrong.', response.message, response.messages)
						}
					}
				);
			}
		}
	},
	getQueryResult: function(id)
	{
		var url = tjrContentUI.base_url + 'index.php?option=com_tjreports&view=reports';
		var params = {'client':'client','reportId':'reportId','queryId':'queryId'};

			jQuery.each(params, function(id,val){
				var value = jQuery('#' + id).val();
					if (value)
					{
						url +=  '&' + val + '=' + value;
					}
			})

		window.location.href = url;
	},
	submitForm :function()
	{
		var task = jQuery('#task').val();
		if (task)
		{
			return true;
		}

		tjrContentUI.report.submitTJRData('submit');

		return false;
	},
	loadReport: function(selectedElem, extension)
	{
		var reportToLoad = jQuery(selectedElem).val();
		var reportId = jQuery(selectedElem).find(":selected").attr('data-reportid');

		var action = document.adminForm.action;
		var newAction = action+'&reportId='+reportId;

		jQuery('#report-select options').attr('selected', 'selected');

		if (extension)
		{
			newAction = newAction +'&client='+extension;
		}
		window.location.href = newAction;
	},
	submitOnEnter:function(event){

		if(event.type=="keydown" && (event.which == 13 || event.keyCode == 13))
		{
			tjrContentUI.report.submitTJRData();
		}

		return false;
	}
});
tjrContentUI.validation  = tjrContentUI.validation ? tjrContentUI.validation : {};
jQuery.extend(tjrContentUI.validation, {
	messages: {},
	resetInvalidField: function($field, msg, key) {
		if ($field) {
			$field.val('');
		}
		this.addMessage(msg, key);
	},
	addMessage: function(msg, key) {
		key = key ? key : 'error';
		this.messages[key] = this.messages[key] ? this.messages[key] : [];
		this.messages[key].push(msg);
	},
	resetMessages: function() {
		Joomla.removeMessages();
		this.messages = {};
	},
	isValidDate: function(dateVal) {
		dateVal = dateVal.split(" ");
		var validDate = dateVal[0].match(/^\d{4}[-](0?[1-9]|1[012])[-](0?[1-9]|[12][0-9]|3[01])$/);
		if (validDate != null) {
			return true;
		}
		return false;
	},
	isValidCalDate: function(dateVal, format) {
		// Extended from Calendar : should only be called if calendar object exists
		if (typeof Date.parseDate != 'undefined') {
			if (!format) {
				format = '%d-%m-%Y';
			}
			var parsedDate = Date.parseDate(dateVal, format).print(format);

			if (parsedDate == dateVal) {
				return true;
			}
		} else { //fallback
			return this.isValidDate(date);
		}
	},
});
tjrContentUI.utility  = tjrContentUI.utility ? tjrContentUI.utility : {};
jQuery.extend(tjrContentUI.utility, {
	getTimeStampFromDate: function(dateVal, format) {
		// Extended from Calendar
		if (typeof Date.parseDate != 'undefined') {
			if (!format) {
				format = '%d-%m-%Y';
			}
			var dateObj = Date.parseDate(dateVal, format);

			if (dateObj) {
				return dateObj.getTime()
			}
		}
		return dateVal;
	},
	loadingLayer: function(task, parentElement) {
		// Set default values.
		task = task || 'show';
		parentElement = parentElement || document.body;

		// Create the loading layer (hidden by default).
		if (task == 'load') {

			var loadingDiv = document.createElement('div');

			loadingDiv.id = 'loading-logo';

			// The loading layer CSS styles are JS hardcoded so they can be used without adding CSS.

			// Loading layer style and positioning.
			loadingDiv.style['position'] = 'fixed';
			loadingDiv.style['top'] = '0';
			loadingDiv.style['left'] = '0';
			loadingDiv.style['width'] = '100%';
			loadingDiv.style['height'] = '100%';
			loadingDiv.style['opacity'] = '0.8';
			loadingDiv.style['filter'] = 'alpha(opacity=80)';
			loadingDiv.style['overflow'] = 'hidden';
			loadingDiv.style['z-index'] = '10000';
			loadingDiv.style['display'] = 'none';
			loadingDiv.style['background-color'] = '#fff';

			// Loading logo positioning.
			loadingDiv.style['background-image'] = 'url("' + tjrContentUI.root_url + '/components/com_tjreports/assets/images/loadinfo.gif")';
			loadingDiv.style['background-position'] = 'center';
			loadingDiv.style['background-repeat'] = 'no-repeat';
			loadingDiv.style['background-attachment'] = 'fixed';
			loadingDiv.style['background-size'] = '200px';

			parentElement.appendChild(loadingDiv);
		}
		// Show or hide the layer.
		else {
			if (!document.getElementById('loading-logo')) {
				this.loadingLayer('load', parentElement);
			}

			document.getElementById('loading-logo').style['display'] = (task == 'show') ? 'block' : 'none';
		}

		return document.getElementById('loading-logo');
	},
	noScript: function(str) {
		var div = jQuery('<div>').html(str);
		div.find('script').remove();
		var noscriptStr = str = div.html();
		return noscriptStr;
	},
	displayMessage: function(msg, key, move) {
		this.parent.validation.messages = {};
		this.parent.validation.addMessage(msg, key);
		this.displayMessages();
	},
	displayMessages: function(move) {
		Joomla.removeMessages();
		Joomla.renderMessages(this.parent.validation.messages);
		if (move) {
			jQuery('body').animate({
				scrollTop: jQuery("#system-message-container").offset().top
			}, 500);
		}
	},
});
tjrContentUI.tjreport  = tjrContentUI.tjreport ? tjrContentUI.tjreport : {};
jQuery.extend(tjrContentUI.tjreport, {
	getPlugins:function(){
		var url     = tjrContentUI.base_url + 'index.php?option=com_tjreports&format=json';
		var $form   = jQuery('#adminForm.tjreportForm');
		jQuery('#task',$form).val('tjreport.getplugins');
		var promise = tjrContentService.postData(url, $form.serialize());
		jQuery('#task',$form).val();
		jQuery('#jform_parent',$form).find('option').not(':first').remove();
		promise.fail(
			function(response) {
				console.log('Something went wrong.');
				if (response.status == 403)
				{
					alert(Joomla.JText._('JERROR_ALERTNOAUTHOR'));
				}
			}
		).done(
			function(response) {
				if (response.success)
				{
					// Append option to plugin dropdown list.
					var list = jQuery("#jform_parent");
					jQuery.each(response.data, function(index, item) {
						list.append(new Option(item.text, item.value));
					});
					tjrContentUI.tjreport.getParams();
				}
				else
				{
					console.log('Something went wrong.', response.message, response.messages)
				}
			}
		);
	},
	getParams:function(defaultParam){
		var url  = tjrContentUI.base_url + 'index.php?option=com_tjreports&format=json';
		if(defaultParam)
		{
			url = url + '&default=1';
		}
		var $form = jQuery('#adminForm.tjreportForm');
		jQuery('#task',$form).val('tjreport.getparams');
		var promise = tjrContentService.postData(url, $form.serialize());
		jQuery('#task',$form).val();
		jQuery("#jform_param",$form).val('');
		jQuery("#jform_plugin",$form).val('');
		promise.fail(
			function(response) {
				console.log('Something went wrong.');
				if (response.status == 403)
				{
					alert(Joomla.JText._('JERROR_ALERTNOAUTHOR'));
				}
			}
		).done(
			function(response) {
				if (response.success)
				{
					var params = JSON.stringify(JSON.parse(response.data.param), null, "\t");
					jQuery("#jform_param",$form).val(params);
					jQuery("#jform_plugin",$form).val(response.data.plugin);
				}
				else
				{
					console.log('Something went wrong.', response.message, response.messages)
				}
			}
		);
	}
});
jQuery(document).click(function(e)
{
	if (!jQuery(e.target).closest('#ul-columns-name').length && e.target.id != 'show-hide-cols-btn')
	{
		jQuery(".ColVis_collection").hide();
	}
});

jQuery(document).ready(function(){
	jQuery('#topFilters').hide();
	jQuery('#btn-cancel').hide();
	jQuery('.cancel-btn').hide();
});
