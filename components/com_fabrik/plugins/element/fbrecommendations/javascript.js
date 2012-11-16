var fbRecommendations = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fbRecommendations';
		this.setOptions(element, options);
	}
});