/**
 * @author Robert
*/
var fbKaltura = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.setOptions(element, options);
		this.plugin = 'kaltura';
		this.element = $(element);
		swfobject.embedSWF("http://www.kaltura.com/kcw/ui_conf_id/36200", "kcw", "680", "360", "9.0.0", false, this.options.flash, this.options.uploader);
	},
	
	doneUploading: function(e, entries)
	{

	}
});