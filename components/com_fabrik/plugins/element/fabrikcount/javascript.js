var fbCount = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikcount';
		this.setOptions(element, options);
	}
});