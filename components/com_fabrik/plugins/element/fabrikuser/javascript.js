var fbUser = fbDatabasejoin.extend({
	initialize: function(element, options) {
		// $$$ hugh - something funky happens if we try and run parent init from join element
		// so need to add loadEvents by hand here, as would usually get add added in parent init.
		//this.parent(element, options);
		this.loadEvents = [];
		this.plugin = 'fabrikuser';
		this.setOptions(element, options); 
	}
});