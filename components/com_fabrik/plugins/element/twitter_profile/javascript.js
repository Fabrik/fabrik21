var fbTwitter_profile = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'twitter_profile';
		this.setOptions(element, options);
	}
});