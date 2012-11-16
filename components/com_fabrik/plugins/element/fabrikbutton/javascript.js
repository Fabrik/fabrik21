var fbButton = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikButton';
		this.setOptions(element, options);
	},
	setOptions: function(element, options) {
		this.element = $(element);
		var d = [];
		this.options = {
			element:      element,
			defaultVal: d
		};
		$extend(this.options, options);
		this.setorigId();
	},
	// used to assertain the original element id (used on return from ajax validation)
	setorigId: function()
	{
		if(this.options.repeatCounter > 0) {
			var e = this.options.element;
			this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
		}
	}
});