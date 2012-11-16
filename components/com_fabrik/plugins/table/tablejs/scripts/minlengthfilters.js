//class name needs to be the same as the file name
	var minlengthfilters = new Class({

	initialize: function(table)
	{
		this.table = table; //the table js object
	},
	
	// run when a filter is submitted
	onFilterSubmit:function(e)
	{
		var ok = $('tableform_'+this.table.id).getElements('input.fabrik_filter').every(function(f) {
			return f.getValue().length >= 0;
		});
		return ok;
	}
	
});