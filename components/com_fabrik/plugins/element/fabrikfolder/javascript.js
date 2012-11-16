var FbFolder = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikfolder';
		this.setOptions(element, options);
	}
});