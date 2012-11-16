/**
 * Simple example of using the fabrikjs plugin, in this case to hide the 'Save'
 * button on multipage forms on all but the last page of the form.
 * 
 * It uses two of the available plugin hooks (see example.js for all available hooks),
 * onFormLoad, and onEndChangePage.
 * 
 * Note that we use setStyle() to hide and show the button, rather than the hide() and show()
 * methods, because show() sets 'display: block', which forces a linebreak after the button,
 * so we have to specifically set 'display: inline'.
 */
var lastpage = new Class({

	initialize: function(form)
	{
		this.form = form; //the form js object
	},

	//run when form is loaded and DOM is ready
	onFormLoad: function()
	{
		if (this.form.currentPage < this.form.options.pages.keys().length - 1) {
			//alert('not on last page!');
			if ($('fabrikSubmit' + this.form.id)) {
				$('fabrikSubmit' + this.form.id).setStyle('display','none');
			}
		}
	},
	
	onEndChangePage: function() {
		if (this.form.currentPage == this.form.options.pages.keys().length - 1) {
			//alert('on last page!');
			if ($('fabrikSubmit' + this.form.id)) {
				$('fabrikSubmit' + this.form.id).setStyle('display','inline');
			}
		}
		else {
			//alert('not on last page!');
			if ($('fabrikSubmit' + this.form.id)) {
				$('fabrikSubmit' + this.form.id).setStyle('display','none');
			}
		}
	}
});
