//class name needs to be the same as the file name
	var timer = new Class({

	initialize: function(form)
	{
		this.form = form; //the form js object
		this.table_name = 'fab_timer_test';
		var timer_id = this.table_name + '___timer' + this.form.currentPage;
		var timer_obj = this.form.formElements.get(timer_id);
		timer_obj.start();
	},
	
	//run when submit button is pressed
	onSubmit: function()
	{
		alert('onSubmit');
		//return false if you want the form to stop submission
	},
	
	//run once the form has sucessfully submitted data via ajax
	onAjaxSubmitComplete: function(){
		alert('complete');
	},
	
	onDoElementFX: function(){
		alert('onDoElementFX');
	},
	
	//run each time you move from one group to another on
	//multipage forms 
	onChangePage: function(){
		var stop_timer_id = this.table_name + '___timer' + this.form.currentPage;
		var next_page = parseInt(this.form.currentPage) + parseInt(this.form.changePageDir);
		var start_timer_id = this.table_name + '___timer' + next_page;
		this.form.formElements.get(stop_timer_id).pause();
		this.form.formElements.get(start_timer_id).start();
	},
	
	//run if the form has ajax validaton
	//run at start of element validaton that occurs on that elements onblur event
	onStartElementValidation: function(){
		alert('onStartElementValidation');
	},
	
	//run when above element validation's ajax call is completed
	onCompleteElementValidation: function(){
		alert('onCompleteElementValidation');
	},
	
	//called when a repeatable group is deleted
	onDeleteGroup: function(){
		alert('onDeleteGroup');
	},
	
	//called when a repeat group is duplicated
	onDuplicateGroup: function(){
		alert('onDuplicateGroup');
	},
	
	//called when the form gets updated
	onUpdate: function(){
		alert('onUpdate');
	},
	
	//called when the form is reset
	onReset: function(){
	}
	
});