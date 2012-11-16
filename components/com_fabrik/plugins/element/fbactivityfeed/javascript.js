var fbActivityfeed = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fbActivityfeed';
		this.setOptions(element, options);
	}
});