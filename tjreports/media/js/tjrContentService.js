/*
 * @version    SVN:<SVN_ID>
 * @package    com_tjlms
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2015 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */

var tjrContentService = {
	postData: function(url, formData, params) {
		if(!params){
			params = {};
		}

		params['url']		= this.getBaseUrl() + url;
		params['data'] 		= formData;
		params['type'] 		= typeof params['type'] != "undefined" ? params['type'] : 'POST';
		params['async'] 	= typeof params['async'] != "undefined" ? params['async'] :true;
		params['dataType'] 	= typeof params['datatype'] != "undefined" ? params['datatype'] : 'json';

		var promise = jQuery.ajax(params);
		return promise;
	},
	getBaseUrl : function(){
		if (typeof tjrContentUI !== 'undefined'){
			return tjrContentUI.base_url;
		}

		return '';
	}
}
