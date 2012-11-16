var fbField = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikfield';
		this.setOptions(element, options);
	},
	
	select:function(){
		this.element.select();
	},
	
	focus:function(){
		this.element.focus();
	}
});