var fbTableUpdateCol = FbTablePlugin.extend({
	initialize : function(tableform, options, lang) {
		this.setOptions(tableform, options);
		this.lang = Object.extend({'selectrow':'Please select a row!'}, lang || {});
		window.addEvent('domready', function() {
			var t = this.tableform.getElement('input[name=tableid]');
		
			// in case its in a viz
			if($type(t) === false){
				return;
			};
			this.tableid = t.value;
			this.watchButton();
		}.bind(this));
	}
});