var fbIp = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikip';
		this.setOptions(element, options);
	}
});