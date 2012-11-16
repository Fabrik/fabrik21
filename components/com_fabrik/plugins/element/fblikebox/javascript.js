var fbLikebox = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fbLikebox';
		this.setOptions(element, options);
	}
});