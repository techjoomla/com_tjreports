/*
 * @version    SVN:<SVN_ID>
 * @package    com_tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
if (typeof tjrContentUI == 'undefined'){
	var tjrContentUI = {};
}
tjrContentUI.root_url  = (typeof root_url == 'undefined') ? '' : root_url;
tjrContentUI.base_url = (typeof root_url == 'undefined') ? '' : root_url;
tjrContentUI.report    = tjrContentUI.report ? tjrContentUI.report : {};


jQuery.extend(tjrContentUI.report, {
	searchToggle: true,
	$form: null,
	url: 'index.php?option=com_tjreports&view=reports&format=json',
	querySaveUrl: 'index.php?option=com_tjreports&format=json',
	submitTJRData: function(task) {

		// Set the view layout on the basis of task
		task = (typeof task == 'undefined' ) ? 'default' : task;

		// Default layout

		var layout = 'default';

		if (task == "summary")
		{
			layout = 'summary';
			jQuery('#summaryReportLabel').addClass('btn-danger');
			jQuery('#detailsReportLabel').removeClass('btn-success');
			jQuery('#reportPagination').hide();
			jQuery('#pagination').hide();
		}
		else
		{
			jQuery('#detailsReportLabel').removeClass('btn-success');
			jQuery('#summaryReportLabel').removeClass('btn-danger');
			jQuery('#reportPagination').show();
			jQuery('#pagination').show();
		}

		try {
			jQuery("#reports-container .hasTooltip").tooltip('destroy');
		}
		catch(err) {
			jQuery("#reports-container .hasTooltip").tooltip('dispose');
		}

		this.searchToggle = jQuery('div#topFilters').is(':visible');
		tjrContentUI.utility.loadingLayer('show');
		this.$form = jQuery('#adminForm');
		var doProcess = this.validate();
		if (!doProcess) {
			return false;
		}

		try {
			jQuery(".hasPopover").popover('destroy');
		}
		catch(err) {
			jQuery(".hasPopover").popover('dispose');
		}

		var promise = tjrContentService.postData(this.url+'&tmpl='+layout, this.$form.serialize());

		promise.fail(
			function(response) {
				console.log('Something went wrong.');
				tjrContentUI.utility.loadingLayer('hide');

				if (response.status == 403)
				{
					alert(Joomla.Text._('JERROR_ALERTNOAUTHOR'));
				}
			}
		).done(
			function(response) {

				tjrContentUI.utility.loadingLayer('hide');

				if (layout == 'summary')
				{
					jQuery("#report-containing-div").html(response['html']);
				}
				else
				{
					var containerSel = '#j-main-container';
					var responseHTML = jQuery(response['html']).find(containerSel).html();
					jQuery(containerSel).html(responseHTML);

					// If sendEmail plug is enabled then try to add a column of checkboxes
					if (
					  typeof tjutilitysendemail != 'undefined' &&
					  jQuery('body').find('.td-sendemail').length > 0
					)
					{
						tjutilitysendemail.addColumn('report-table');
					}

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

					if (task == 'reset')
					{
						tjrContentUI.report.searchToggle = false;
					}

					if (tjrContentUI.report.searchToggle)
					{
						jQuery('#show-filter').addClass('btn-primary').find('i').removeClass('fa-caret-down').addClass('fa-caret-up');
						jQuery('#topFilters').show();
                    			}
                    			jQuery('.btn-displayReport').toggleClass('active btn-success');
				}
			}
		).always(
			function(response) {
				jQuery('.filter-hide').parents('.col-filter-header').hide();
				jQuery('.filter-show').parents('th').find('.table-heading').hide();
			}
		);
	},
	showFilter: function(){
		jQuery('#show-filter').toggleClass('btn-primary');
		jQuery('#topFilters').slideToggle('1000');
		jQuery('#show-filter .fa').toggleClass('fa-caret-up').toggleClass('fa-caret-down');
	},
	resetSubmitTJRData : function(task,container){
		container = (container && container.length) ? container : '#topFilters';
		jQuery('input:text', container).val('');
		jQuery(':input', container)
		 .not(':button, :submit, :reset, input:hidden')
		 .val('')
		 .removeAttr('checked')
		 .removeAttr('selected');
		tjrContentUI.report.submitTJRData(task);
	},
	validate: function() {
		return true;
	},
	getColNames: function()
	{
		jQuery('.ColVis_collection').toggle();
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
			jQuery('#saveQuery').html(Joomla.Text._('COM_TJREPORTS_SAVE_QUERY'));
		}
		else
		{
			var queryName = jQuery('#queryName').val();

			if (queryName === '')
			{
				alert(Joomla.Text._('COM_TJREPORTS_ENTER_TITLE'));
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
			deletemsg = Joomla.Text._('COM_TJREPORTS_DELETE_MESSAGE');
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
							tjrContentUI.utility.setJSCookie('showdeletemsg', '1', 1);
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

		var action = jQuery(document.adminForm).attr('action')
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
	},
	attachCalSubmit : function(elem){
		/** global: JoomlaCalendar */
		var calObj = JoomlaCalendar.getCalObject(elem);
			if (calObj && !calObj._joomlaCalendar.params.onUpdate)
			{
				calObj._joomlaCalendar.params.onUpdate = tjrContentUI.report.submitCalForm;
			}
	},
	submitCalForm : function(){
		tjrContentUI.report.submitTJRData();
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
	setJSCookie:function(name,value,days) {
		var expires = "";
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days*24*60*60*1000));
			expires = "; expires=" + date.toUTCString();
		}
		document.cookie = name + "=" + value + expires + "; path=/";
	},
	getJSCookie:function(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	},
	eraseJSCookie:function(name) {
		tjrContentUI.utility.setJSCookie(name,"",-1);
	},
});
tjrContentUI.tjreport  = tjrContentUI.tjreport ? tjrContentUI.tjreport : {};
jQuery.extend(tjrContentUI.tjreport, {
	getPlugins:function(){
		var url     = 'index.php?option=com_tjreports&format=json';
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
					alert(Joomla.Text._('JERROR_ALERTNOAUTHOR'));
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
	loadSort: function(){
		//code for hide heading and show filter
		jQuery('.col-filter-header').hide();

		jQuery(document).on('click','.col-search',function(){
			var container = jQuery(this).parents('th');
			jQuery('.col-filter-header',container).show();
			jQuery('.chzn-search').children('input').attr('readOnly',false);
			jQuery('.table-heading',container).hide();
		});

		//code for hide filter and show heading
		jQuery(document).on('click','.close-icon',function(){
			var container = jQuery(this).parents('th');
			jQuery('.table-heading', container).show();
			jQuery('.col-filter-header',container).hide();
			tjrContentUI.report.resetSubmitTJRData('submit', container);
		});
	},
	getParams:function(defaultParam){
		var url  = 'index.php?option=com_tjreports&format=json';
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
					alert(Joomla.Text._('JERROR_ALERTNOAUTHOR'));
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
	tjrContentUI.tjreport.loadSort();

	if (tjrContentUI.utility.getJSCookie('showdeletemsg'))
	{
		if (!Joomla.Text.strings['SUCCESS'])
		{
			Joomla.Text.strings['SUCCESS'] = "Success";
		}
		Joomla.renderMessages({'success' : [Joomla.Text._("COM_TJREPORTS_QUERY_DELETE_SUCCESS")]});
		tjrContentUI.utility.eraseJSCookie("showdeletemsg");
	}

	jQuery(document).on('click', '#reports-container a[href="#"]', function(e){
		e.preventDefault();
	})
});


jQuery(document).ready(function(){
jQuery('#report-table').on("liszt:ready", 'select', function(){      
	jQuery(this).next('.chzn-container-single-nosearch').removeClass('chzn-container-single-nosearch')

	});
});
