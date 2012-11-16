//class name needs to be the same as the file name
var submit_spinner = new Class({

	initialize: function(form)
	{
		this.form = form; //the form js object
	},
	

	// Run when submit button has been pressed, after any required validation (if no errors), as
	// last thing doSubmit() does.  Return false to abort submission.
	// NOTE - does not (currently) run on AJAX form submits.
	//
	// In the submit-spinner example, this is just used to show a standard 'progress' spinner
	// 
	onSubmitEnd: function()
	{
		oPackage.startLoading(this.form.getBlock(), 'loading')
	}
});