//class name needs to be the same as the file name
	var example = new Class({

	initialize: function(table)
	{
		this.table = table; //the table js object
	},
	
	//run once the table has run its ondomready event (gives access to this.table.form etc)
	onDomready:function(e){
		
	},
	
	// run when a filter is submitted
	onFilterSubmit:function(e)
	{
		alert('onFilterSubmit');
		var ok = $('tableform_'+this.table.id).getElements('input.fabrik_filter').every(function(f) {
			return f.getValue().length >= 0;
		});
		return ok;
	},
	
	//run when submit button is pressed
	onSubmitTable: function(evnt, task)
	{
		alert('onSubmit: ' + task);
		//return false if you want the form to stop submission
	},
	
	//run when page navigation occurs
	onNavigate: function() {
		alert('onNavigate');
	},
	
	// run when the table is reordered
	onOrder: function() {
		alert('onOrder');
	},
	
	//run when the limit list is changed 
	onLimit: function() {
		alert('onLimit');
	}
	
});