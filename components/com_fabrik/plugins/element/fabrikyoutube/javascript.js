var fbYouTube = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikyoutube';
		this.setOptions(element, options);
	}
});