var fbComment = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fbComment';
		this.setOptions(element, options);
	}
});