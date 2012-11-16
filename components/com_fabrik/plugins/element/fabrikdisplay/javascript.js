var fbDisplay = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikdisplay';
		this.setOptions(element, options);
	}
});