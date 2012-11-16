var fbBirthday = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikbirthday';
		this.setOptions(element, options);
	}
});