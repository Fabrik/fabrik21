//class name needs to be the same as the file name
	var example = new Class({

	initialize: function(form)
	{
		this.form = form; //the form js object
	},
	
	//run when dom is ready
	onFormLoad: function()
	{
		alert('onFormLoad');
	},
	
	//run when submit button is pressed
	onSubmit: function()
	{
		alert('onSubmit');
		//return false if you want the form to stop submission
	},
	
	// Run when submit button has been pressed, after any required validation (if no errors), as
	// last thing doSubmit() does.  Return false to abort submission.
	// NOTE - does not (currently) run on AJAX form submits.
	// 
	onSubmitEnd: function()
	{
		alert('onSubmitEnd');
		// return false to abort form submission
	},
	
	//run once the form has sucessfully submitted data via ajax
	onAjaxSubmitComplete: function() {
		alert('complete');
	},
	
	onDoElementFX: function() {
		alert('onDoElementFX');
	},
	
	//run at the start of saving a group to the db
	// when you move from one group to another on
	//multipage forms 
	saveGroupsToDb: function() {
		alert('saveGroupsToDb');
	},
	
	//run once the ajax call has completed when moving from one group to another
	//on multipage forms
	onCompleteSaveGroupsToDb: function() {
		alert('onCompleteSaveGroupsToDb');
	},
	
	//run each time you move from one group to another on
	//multipage forms, at the start of the change of page
	onChangePage: function() {
		alert('onChangePage');
	},
	
	//run each time you move from one group to another on
	//multipage forms, after the change of page is complete
	onEndChangePage: function() {
		alert('onEndChangePage');
	},
	
	
	//run if the form has ajax validaton
	//run at start of element validaton that occurs on that elements onblur event
	onStartElementValidation: function() {
		alert('onStartElementValidation');
	},
	
	//run when above element validation's ajax call is completed
	onCompleteElementValidation: function() {
		alert('onCompleteElementValidation');
	},
	
	//called when a repeatable group is deleted
	onDeleteGroup: function() {
		alert('onDeleteGroup');
	},
	
	//called when a repeat group is duplicated
	onDuplicateGroup: function(event, args) {
		alert('onDuplicateGroup');
	},
	
	//called when a repeat group is duplicated
	// args[0] is numeric group ID
	// args[1] is repeat count
	onDuplicateGroupEnd: function(event, args) {
		alert('onDuplicateGroupEnd');
	},
	
	//called when the form gets updated
	onUpdate: function() {
		alert('onUpdate');
	},
	
	//called when the form is reset
	onReset: function() {
	}
	
});