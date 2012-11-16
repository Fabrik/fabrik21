/**
 * @author Robert
 */
var fabrikAdminFileupload = new Class({

	initialize: function(lang) {
		this.lang = {};
		$extend(this.lang, lang);
	},
	
	onSave:function() {
		var r = true;
		if($('paramsmake_thumbnail1').checked === true) {
			if ($('paramsthumb_max_width').get('value') === '' && $('paramsthumb_max_height').get('value') === '') {
				alert(this.lang.error_thumb);
				r = false;
			}
			var thumbprefix = $('paramsthumb_prefix').get('value');
			thumbprefix = thumbprefix.replace(/[^a-zA-Z0-9_-]+/g,'');
	 		$('paramsthumb_prefix').value = thumbprefix;
		}
		if($('paramsfileupload_crop1').checked === true) {
			if ($('paramsfileupload_crop_width').get('value') === '' && $('paramsfileupload_crop_height').get('value') === '') {
				alert(this.lang.error_crop);
				r = false;
			}
		}
		return r;
	}
});