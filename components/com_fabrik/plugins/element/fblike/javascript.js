var fbLike = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fblike';
		this.setOptions(element, options);
	}
});