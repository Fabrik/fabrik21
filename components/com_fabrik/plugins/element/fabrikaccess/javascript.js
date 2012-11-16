var FbAccess = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikaccess';
		this.setOptions(element, options);
	}
});