var fbInternalId = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fbInternalId';
		this.setOptions(element, options);
	}
});