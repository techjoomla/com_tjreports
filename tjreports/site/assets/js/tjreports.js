function getColNames()
{
	techjoomla.jQuery('#saveQuery').show();
	techjoomla.jQuery('.ColVis_collection').toggle();
}

function getQueryResult(id, Itemid)
{
	var queryId = id.split("_");

	if (queryId=="")
	{
		window.location.href = site_root + 'index.php?option=com_tjreports&view=reports&reportToBuild='+reportToBuild+'&client='+client+'&reportId='+reportId+"&Itemid="+Itemid;
	}
	else
	{
		window.location.href = site_root + 'index.php?option=com_tjreports&view=reports&savedQuery=1&reportToBuild='+queryId[0]+'&client='+client+'&queryId='+queryId[1]+'&reportId='+reportId+"&Itemid="+Itemid;
	}
}

function loadReport(reportToLoad,mid)
{
		var path = site_root + "index.php?option=com_tjreports&task=reports.getreport";

		// Report to load is id.
		var data = 'reportToLoad=' + reportToLoad;

		jQuery.ajax({
		url: path,
		type: 'post',
		data : data,
		dataType: 'json',
		error: function()
				{
					console.log('Problem with AJAX Request while loading report');
					return false;
				},
		success: function(resp)
		{
			reportToLoad = resp.plugin;
			clientt = resp.client;
			var clientArray = [];
			clientArray.push(clientt);

			 // Add current client to first and append others.

			var res = client.split(",");
			for(var i in res){
				if(res[i] == clientt){
					res.splice(i,1);
					break;
				}
			}

			for(var i = 0; i < res.length; i++){
				clientArray.push(res[i]);
			}

			clients = clientArray.toString();

			reportId = resp.id;
			var action = document.adminForm.action;
			var newAction = action+'&reportToBuild='+reportToLoad+'&client='+clients+'&reportId='+reportId+'&Itemid='+mid;
			window.location.href = newAction;

		}});
}

function cleardate()
{
	techjoomla.jQuery("#attempt_begin").val('');
	techjoomla.jQuery("#attempt_end").val('');
	getFilterdata(-1, '', 'dateSearch');
}

	techjoomla.jQuery(document).click(function(e)
	{
		if (!techjoomla.jQuery(e.target).closest('#ul-columns-name').length && e.target.id != 'show-hide-cols-btn')
		{
			techjoomla.jQuery(".ColVis_collection").hide();
		}
	});

techjoomla.jQuery(document).ready(function(){

		/*
		* get char remianing for short desc
		*/

		getPaginationBar();

		techjoomla.jQuery('.ColVis_collection input').click(function(){

			if (techjoomla.jQuery(".ColVis_collection input:checkbox:checked").length > 0)
			{
				getFilterdata(-1, '', 'hideShowCols');
			}
			else
			{
				var msg = Joomla.JText._('COM_TJREPORTS_REPORTS_CANNOT_SELECT_NONE');
				alert(msg);
				return false;
			}
		});

		techjoomla.jQuery( "#jform_short_desc" ).keyup(function(event) {
			var desc_length = techjoomla.jQuery("#jform_short_desc").val().length;
			var characters = lesson_characters_allowed;

			var sht_desc_length = characters - desc_length;
			if(sht_desc_length <= 0)
			{
				sht_desc_length = 0;

				techjoomla.jQuery('#max_body1').html('<span class="disable"> '+sht_desc_length+' </span>');
				document.getElementById('jform_short_desc').value = document.getElementById('jform_short_desc').value.substr(0,characters);
				techjoomla.jQuery('#sBann1').show();
			}
			else
			{
				techjoomla.jQuery( '#max_body1' ).html(sht_desc_length);
				techjoomla.jQuery('#sBann1').show();
			}
		});

	techjoomla.jQuery('.tjlms_add_lesson_form label').hover(function(){

		var offset = techjoomla.jQuery(this).offset();
		var tooltipContainer = techjoomla.jQuery(this).attr('aria-describedby');
		techjoomla.jQuery("#" + tooltipContainer).css('left',offset.left + 10);

		var contents = techjoomla.jQuery("#" + tooltipContainer + ' .ui-tooltip-content').html();
		techjoomla.jQuery("#" + tooltipContainer + ' .ui-tooltip-content').remove();

		techjoomla.jQuery("#" + tooltipContainer).text('');
		var html = "<div class='ui-tooltip-content'>" + dencodeEntities(contents) + "</div>";
		techjoomla.jQuery("#" + tooltipContainer).append(html);
	});
});

function dencodeEntities(s){
	return techjoomla.jQuery("<div/>").html(s).text();
}

function getUploadedfilename()
{
	var filename ='';
	var format = techjoomla.jQuery('#jform_format option:selected').val();
	var fileobj = techjoomla.jQuery('#'+ format + ' input[type="file"]')[0].files[0];
	if(fileobj != undefined)
	  filename = fileobj.name;
	return filename;
}
function setUploaderroSuccess(format, success , error, msg)
{

	techjoomla.jQuery('#lesson_format #'+format+' .format_upload_error').hide();
	techjoomla.jQuery('#lesson_format #'+format+' .format_upload_error').html('');

	techjoomla.jQuery('#lesson_format #'+format+' .format_upload_success').hide();
	techjoomla.jQuery('#lesson_format #'+format+' .format_upload_success').html('');

	//techjoomla.jQuery('#lesson_format #'+format+'#'+format+' .fileupload-preview').text('');


	if(success == 1)
	{
		techjoomla.jQuery('#lesson_format #'+format+' .format_upload_success').show();
		techjoomla.jQuery('#lesson_format #'+format+' .format_upload_success').html(msg);
	}
	else if(error == 1)
	{
		techjoomla.jQuery('#lesson_format #'+format+' .format_upload_error').show();
		techjoomla.jQuery('#lesson_format #'+format+' .format_upload_error').html(msg);

	}
}

function checkforalpha(el,allowed_ascii)
{
	allowed_ascii= (typeof allowed_ascii === 'undefined') ? '' : allowed_ascii;
	var i =0 ;
	for(i=0;i<el.value.length;i++){
		if((el.value.charCodeAt(i) <= 47 || el.value.charCodeAt(i) >= 58) || (el.value.charCodeAt(i) == 45 )){
			if(allowed_ascii !=el.value.charCodeAt(i) ){
				alert(numeric_value_validation_msg);
				el.value = el.value.substring(0,i);
				 break;
			}
		}
	}
}
//get subs_plan div depending on course type.

function openSubscriptionDiv(coursetype)
{
	if(coursetype==1)
	{
		document.getElementById('subs_plan_div').style.display='block';
	}
	else
	{
		techjoomla.jQuery('#subs_plan_div input').val('');
		document.getElementById('subs_plan_div').style.display='none';
	}
}


/*
*	to make duration drop down readable for unlimited plans
*/
function checkForUnlimited(time_measure,time_measure_id)
{
	//slipt the ID
	var split_id = time_measure_id.split('_');

	if(time_measure=='unlimited')
	{

		techjoomla.jQuery('#subs_plan_duration_'+split_id[4]).val(1);
		var textbox = document.getElementById('subs_plan_duration_'+split_id[4]);
		textbox.readOnly = "readonly";

	}
	else
	{
		var textbox = document.getElementById('subs_plan_duration_'+split_id[4]);
		textbox.readOnly = false;
	}
}
//repective input to show depending on video format if lesson format is video...
function getVideoFormat1(subformat,thiselement)
{
	var format_lesson_form = techjoomla.jQuery(thiselement).closest('.lesson-format-form');
	var thiselementval = techjoomla.jQuery(thiselement).val();

	if(thiselementval != 'upload')
	{
		techjoomla.jQuery('.video_subformat #video_package',format_lesson_form).hide();
		techjoomla.jQuery('.video_subformat #video_textarea',format_lesson_form).show();
	}
	else
	{
		techjoomla.jQuery('.video_subformat #video_package',format_lesson_form).show();
		techjoomla.jQuery('.video_subformat #video_textarea',format_lesson_form).hide();
	}
}
/*respective HTML to show depending on video sub format...*/
function getVideosubFormat(thiselement)
{
	var format_lesson_form = techjoomla.jQuery(thiselement).closest('.lesson-format-form');
	var thiselementval = techjoomla.jQuery(thiselement).val();
	techjoomla.jQuery('[id^="video_subformat_"]',format_lesson_form).hide();
	techjoomla.jQuery('#video_subformat_'+thiselementval,format_lesson_form).show();
}

function remove_file(id)
{

	var required_id = parseInt(id.replace('remove_file_btn',''));
	techjoomla.jQuery('#tr_'+required_id).remove();
}

function remove_selected_files_btn()
{
	techjoomla.jQuery('.remove_selected_file').each(function(){
			if(techjoomla.jQuery(this).is(":checked"))
			{
				var table_id= techjoomla.jQuery(this).attr('id');
				var required_id = parseInt(table_id.replace('remove_selected_file',''));
				techjoomla.jQuery('#tr_'+required_id).remove();
			}
	});

}
function getDoc(frame) {
	 var doc = null;

	 // IE8 cascading access check
	 try {
		 if (frame.contentWindow) {
			 doc = frame.contentWindow.document;
		 }
	 } catch(err) {
	 }

	 if (doc) { // successful getting content
		 return doc;
	 }

	 try { // simply checking may throw in ie8 under ssl or mismatched protocol
		 doc = frame.contentDocument ? frame.contentDocument : frame.document;
	 } catch(err) {
		 // last attempt
		 doc = frame.document;
	 }
	 return doc;
}

/*Function to check if a file with valid extension has been uploaded for lesson*/
function validate_file(thisfile,mod_id,subformat)
{
		/*remove status bar if already appneded*/
		techjoomla.jQuery(thisfile).closest('.controls').children( ".statusbar" ).remove();

		/*remove missing file alert*/
		techjoomla.jQuery('.tjlms_form_errors .msg').html('').hide();

		/*Disable create lesson and add quiz button*/
		techjoomla.jQuery(".btn-add-lesson").attr("disabled",true);
		techjoomla.jQuery(".btn-add-lesson").css("pointer-events", "none");

		/*if (format != 'associate')
		{
			var format_lesson_form	= techjoomla.jQuery(thisfile).closest('.lesson-format-form');
		}
		else
		{
			var format_lesson_form	= techjoomla.jQuery(thisfile).closest('.lesson-associatefile-form');
		}*/

		var format_lesson_form	= techjoomla.jQuery(thisfile).closest('form');

		var format	= techjoomla.jQuery('#jform_format',format_lesson_form).val();

		/* Hide all alerts msgs */
		var obj = techjoomla.jQuery(thisfile);
		var status = new createStatusbar(obj, format); //Using this we can set progress.


		/* Get uploaded file object */
		var uploadedfile	=	techjoomla.jQuery(thisfile)[0].files[0];

		/* Get uploaded file name */
		var filename = uploadedfile.name;

		/* pop out extension of file*/
		var ext = filename.split('.').pop().toLowerCase();

		/* Get valid extension availiable for chosen lesson format*/
		if (format != 'associate')
		{
			var valid_extensions_str	= techjoomla.jQuery('#lesson_format #'+format+' .'+format+'_subformat .valid_extensions',format_lesson_form).val();
			var valid_extensions = valid_extensions_str.split(',');

			/* If extension is not in provided valid extensions*/
			if(techjoomla.jQuery.inArray(ext, valid_extensions) == -1)
			{
				status.setMsg(nonvalid_extension,'alert-error');
				return false;
			}
		}


		/* if file size is greater than allowed*/
		if((lesson_upload_size * 1024 * 1024) < uploadedfile.size)
		{
			status.setMsg(filesize_exceeded,'alert-error');
			return false;
		}

		/* IF evrything is correct so far, popolate file name in fileupload-preview*/

		var file_name_container	=	techjoomla.jQuery(".fileupload-preview",techjoomla.jQuery(thisfile).closest('.fileupload-new'));

		//	var file_name_container	=	techjoomla.jQuery('#'+format+' #'+format+'_subformat .fileupload-preview',format_lesson_form);
		techjoomla.jQuery(file_name_container).show();
		techjoomla.jQuery(file_name_container).text(filename);

		startUploading(uploadedfile,format,subformat,format_lesson_form,status,thisfile);

		return true;

}

function getReportdata(page, colToShow, limit, sortCol, sortOrder, action, allow_permission, reportId)
{
	var filter = [];
	var filterTitle = [];
	var filterValue;
	var filterName;

	techjoomla.jQuery('th .filter-input').each(function(index) {
		filterValue = techjoomla.jQuery(this).val();
		filterName = techjoomla.jQuery(this).attr('id');
		filterName = filterName.replace('search-filter-','');

		if (filterName == 'id' || filterName == 'attempt')
		{
			if (isNaN(filterValue))
			{
				var msg = Joomla.JText._('COM_TJREPORTS_NO_NEGATIVE_NUMBER');
				alert(msg);

				return false;
			}
		}

		var ifInColsToShow = techjoomla.jQuery.inArray(filterName, colToShow);

		if (filterValue != '' && ifInColsToShow != -1)
		{
			filterTitle.push(filterName);
			filter.push(filterValue);
		}
	});

	var fromDate = techjoomla.jQuery('#attempt_begin').val();
	var toDate = techjoomla.jQuery('#attempt_end').val();

	if (fromDate)
	{
		filterName = 'fromDate';
		filterValue = fromDate;
		filterTitle.push(filterName);
		filter.push(filterValue);
	}

	if (toDate)
	{
		filterName = 'toDate';
		filterValue = toDate;
		filterTitle.push(filterName);
		filter.push(filterValue);
	}

	techjoomla.jQuery.ajax({
		url: site_root+"index.php?option=com_tjreports&task=reports.getFilterData",
		type: "POST",
		dataType: "json",
		data:{filterValue:filter, filterName:filterTitle, limit:limit, page:page, colToShow:colToShow, sortCol:sortCol, sortOrder:sortOrder,action:action,reportToBuild:reportToBuild,allow_permission:allow_permission,reportId:reportId},
		error: function()
		{
			console.log('Problem with AJAX Request while getting the reports');
			return false;
		},
		success: function(data)
		{
			techjoomla.jQuery('#report-containing-div').html('');
			techjoomla.jQuery('.user-report').remove();
			techjoomla.jQuery('#report-containing-div').html(data.html);
			techjoomla.jQuery('#totalRows').val(data.total_rows);
			getPaginationBar(action, data.total_rows);
		}
	});
}

function getFilterdata(page, event, action, sortCol, sortOrder)
{
	allow_permission = techjoomla.jQuery('#allow_permission').val();
	reportId = techjoomla.jQuery('#reportId').val();

	sortCol = typeof sortCol !== 'undefined' ? sortCol : '';
	sortOrder = typeof sortOrder !== 'undefined' ? sortOrder : '';

	var isPaginationBarHidden = techjoomla.jQuery("#pagination-demo").is(':hidden');

	if (isPaginationBarHidden == 0 && (typeof page == 'undefined' || page == -1))
	{
		page = techjoomla.jQuery('#pagination-demo li.active a').html();
	}

	var limit = techjoomla.jQuery('#reportPagination #list_limit').val();

	var colToShow = [];

	techjoomla.jQuery('.ColVis_collection input').each(function(){
		var isChecked = techjoomla.jQuery(this).is(":checked");

		if (isChecked == 1)
		{
			var eachColName = techjoomla.jQuery(this).attr('id');
			colToShow.push(eachColName);
		}
	});

	var ifInColsToShow = techjoomla.jQuery.inArray(sortCol, colToShow);

	if (ifInColsToShow == -1)
	{
		sortCol = '';
	}

	if (colToShow.length === 0) {
		msg = Joomla.JText._('COM_TJREPORTS_REPORTS_CANNOT_SELECT_NONE');
		alert(msg);
		return false;
	}

	if (action == 'search')
	{
		if(event.which == 13)
		{
			getReportdata(page, colToShow, limit, sortCol, sortOrder, action, allow_permission, reportId);
		}
	}
	else
	{
		getReportdata(page, colToShow, limit, sortCol, sortOrder, action, allow_permission, reportId);
	}
}

function getPaginationBar(action, totalRows)
	{
		action = typeof action !== 'undefined' ? action : '';
		totalRows = typeof totalRows !== 'undefined' ? totalRows : techjoomla.jQuery('#totalRows').val();

		if (action !== 'paginationPage')
		{
			techjoomla.jQuery('.pagination').html('');
			techjoomla.jQuery('.pagination').html('<ul id="pagination-demo" class=" "></ul>');
		}

		var limit = techjoomla.jQuery('#reportPagination #list_limit').val();

		var totalpages = 0;

		if (limit != 0)
		{
			var totalpages = totalRows/limit;
			totalpages = Math.ceil(totalpages);
		}

		if (totalpages > 1)
		{
			var pagesToShow = totalpages;

			if (totalpages > 5)
			{
				pagesToShow = 5;
			}

			jQuery('#pagination-demo').twbsPagination({
				totalPages: totalpages,
				visiblePages: pagesToShow,
				startPage:1,
				onPageClick: function (event, page) {
					getFilterdata(page, '', 'paginationPage');
				}
			});
			techjoomla.jQuery('#pagination-demo').show();
		}
		else
		{
			techjoomla.jQuery('#pagination-demo').hide();
		}
	}

	function sortColumns(label)
	{

		var sortOrder = 'asc';

		// Check if the th has class
		var colOrder = techjoomla.jQuery(label).closest('th').hasClass('hearderSorted');
		var sortCol = techjoomla.jQuery(label).attr('data-value');

		if (colOrder == true)
		{
			sortOrder = 'desc';
			techjoomla.jQuery(label).closest('th').removeClass('hearderSorted');
		}
		else
		{
			techjoomla.jQuery(label).closest('th').addClass('hearderSorted');
		}

		getFilterdata(1, '', 'hideShowCols', sortCol, sortOrder);
	}

	function csvExport()
	{
		Joomla.submitbutton();
	}

	function saveThisQuery()
	{
		var inputHidden = jQuery('#queryName').is(":hidden");

		if (inputHidden == 1)
		{
			jQuery('#queryName').show();
			jQuery('#saveQuery').val('Save Query');
		}
		else
		{
			var queryName = jQuery('#queryName').val();

			if (queryName === '')
			{
				alert('Enter title for the Query');
				return false;
			}
			else
			{
				jQuery.ajax({
					url:site_root+"index.php?option=com_tjreports&task=reports.saveQuery",
					type: "POST",
					dataType: "json",
					data:{queryName:queryName,current_user:current_user,client:client,reportId:reportId},
					error: function()
					{
						console.log('Problem with AJAX Request while Saving the Query');
						return false;
					},
					success: function(data)
					{
						if (data == 1)
						{
							window.location.reload();
						}
					}
				});
			}
		}
	}

function validatezero(oldval, e)
{
	var value = e.value;
	var msg = Joomla.JText._('COM_TJLMS_COURSE_DURATION_VALIDATION');
	if(e.value == 0)
	{
		if (oldval)
		{
			jQuery('#'+e.id).val(oldval);
		}

		else
		{
			jQuery('#'+e.id).val('');
			alert(msg);
		}
	}
}

/* Function to load the loading image. */
function loadingImage(format_lesson_form)
{
	techjoomla.jQuery('.formatloadingcontainer',format_lesson_form).show();
	techjoomla.jQuery('.formatloading',format_lesson_form).show();
}

/* Function to hide the loading image. */
function hideImage(format_lesson_form)
{
	techjoomla.jQuery('.formatloading',format_lesson_form).hide();
	techjoomla.jQuery('.formatloadingcontainer',format_lesson_form).hide();
}

/* prev button on create lesson page*/
function lessonBackButton(formId)
{
	var nextLi = techjoomla.jQuery('#tjlmsTab_'+formId+'Tabs li.active').prev();

	techjoomla.jQuery('#tjlmsTab_'+formId+'Tabs li').removeClass('active');

	techjoomla.jQuery(nextLi).addClass('active');

	var tabToShow = techjoomla.jQuery('a',nextLi).attr('href');

	techjoomla.jQuery('#tjlms_add_lesson_form_'+formId+' .tab-content .tab-pane').removeClass('active');
	techjoomla.jQuery('a[href="'+tabToShow+'"]').closest('li').addClass('active');
	techjoomla.jQuery('.tab-content '+tabToShow+'').addClass('active');


	if(tabToShow == '#format_' +  formId)
	{
		/*This is to get the lesson format html from respective plugin and show*/
		var format = techjoomla.jQuery('#format_' + formId + ' #jform_format').val();
		var subformat = techjoomla.jQuery('#format_' + formId + ' #jform_subformat').val();
		var lesson_id = techjoomla.jQuery('#format_' + formId + ' #lesson_id').val();

		var lesson_basic_form	= techjoomla.jQuery('#lesson-basic-form_'+formId);
		var mod_id = techjoomla.jQuery('#mod_id', lesson_basic_form).val();

		getsubFormatHTML(formId,format,mod_id,lesson_id,subformat);
	}
}

function enqueueSystemMessage(message, parentDiv)
{
	techjoomla.jQuery(parentDiv + " #system-message-container").empty();
	techjoomla.jQuery(parentDiv + " #system-message-container").append("<div class='alert alert-error'><p>"+message+"</p></div>");
}

/*Disable the prev next tabs browse button
 *shouldbedisabled = 1 disable them
 * */
function changeformatbtnstate(form_id, shouldbedisabled)
{
	var format_form = techjoomla.jQuery("#lesson-format-form_" + form_id);
	if (shouldbedisabled == '1')
	{
		techjoomla.jQuery("a.lecture-icons",format_form).addClass("inactiveLink");
		techjoomla.jQuery("button",format_form).attr("disabled",true);
	}
	else
	{
		techjoomla.jQuery("a.lecture-icons",format_form).removeClass("inactiveLink");
		techjoomla.jQuery("button",format_form).removeAttr("disabled");
	}
}


/*Disable all the links and tabs
 *shouldbedisabled = 1 disable them
 * */
function inactivelinks(shouldbedisabled)
{
	if (shouldbedisabled == '1')
	{
		techjoomla.jQuery(".admin.com_tjlms input").attr("disabled",true);
		techjoomla.jQuery(".admin.com_tjlms button").attr("disabled",true);
		techjoomla.jQuery(".admin.com_tjlms a").addClass("inactiveLink");
		techjoomla.jQuery('.admin.com_tjlms .nav-tabs li').addClass('inactiveLink');
		techjoomla.jQuery(".btn-add-lesson").attr("disabled",true);	// Disable create lesson and add quiz button
		techjoomla.jQuery(".btn-add-lesson").css("pointer-events", "none");

	}
	else
	{
		techjoomla.jQuery(".admin.com_tjlms button").removeAttr("disabled");
		techjoomla.jQuery(".admin.com_tjlms input").removeAttr("disabled");
		techjoomla.jQuery(".admin.com_tjlms a").removeClass("inactiveLink");
		techjoomla.jQuery('.admin.com_tjlms .nav-tabs li').removeClass('inactiveLink');
		techjoomla.jQuery(".btn-add-lesson").attr("disabled",false);	// Enable create lesson and add quiz button
		techjoomla.jQuery(".btn-add-lesson").css("pointer-events", "auto");
	}
}

function removeAssocFile(id, formId)
{
	var removeConfirm = confirm("Do you really want to remove this file?");

	if (removeConfirm == 1)
	{
		var required_id = parseInt(id.replace('removeFile',''));
		var lesson_id = techjoomla.jQuery('#lesson-associatefile-form_'+formId+' #lesson_id' ).val();

		techjoomla.jQuery.ajax({
			url: 'index.php?option=com_tjreports&task=lessons.removeAssocFiles&media_id='+required_id+'&lesson_id='+lesson_id,
			datatype:'json',
			success: function(data)
			{
				techjoomla.jQuery(".assocFileMedia").val(null); /* Set media_id null */
				if (data == 1)
				{
					techjoomla.jQuery('#lesson-associatefile-form_'+formId+' #assocfiletr_'+required_id).remove();

					var rowCount = techjoomla.jQuery('#lesson-associatefile-form_'+formId + ' .list_selected_files tr').length;

					if (rowCount == 1)
					{
						techjoomla.jQuery('#lesson-associatefile-form_'+formId+' .list_selected_files').hide();
						techjoomla.jQuery('#lesson-associatefile-form_'+formId+' .no_selected_files').show();
					}
				}
				else
				{
					alert("Some error occured.");
				}
			},
			error: function()
			{
				alert("Some error occured.");
			}
		});
	}
	else
	{
		return false;
	}
}

function closePopup(donotload)
{
	if (donotload == '1')
	{
	parent.SqueezeBox.close();
	}
	else
	{
	window.parent.location.reload();
	}

}

function opentjlmsSqueezeBox(link)
{
	var width = techjoomla.jQuery(window).width();
	var height = techjoomla.jQuery(window).height();

	var wwidth = width-(width*0.10);
	var hheight = height-(height*0.10);
	parent.SqueezeBox.open(link, { handler: 'iframe', size: {x: wwidth, y: hheight},classWindow: 'tjlms-modal'});
}

function deleteQuery(queryId)
{
	var delete_msg = Joomla.JText._('COM_TJREPORTS_DELETE_CONFIRMATION_MESSAGE');
	var check = confirm(delete_msg);
	if( check == true )
	{
		var qid=parseInt(queryId);
			jQuery.ajax({
				url: site_root+'index.php?option=com_tjreports&task=reports.deleteQuery',
				dataType: 'json',
				type: 'POST',
				data: {'cid':qid} ,
				error: function()
				{
					console.log('Problem with AJAX Request in deleteQuery');
					return false;
				},
				success: function (resp)
				{
					window.location.reload();
				}
			});
	}
	else
	{
		window.location.reload();
	}
}
