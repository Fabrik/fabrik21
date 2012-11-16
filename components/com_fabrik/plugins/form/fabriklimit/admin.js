var fabrikAdminLimit = fabrikAdminPlugin.extend({
	
	initialize:function(name, label, options) {
		this.name = name;
		this.label = label;
		this.options = {};
		$extend(this.options, options);
	},
	
	cloned:function(counter) {
		var opts = {
				conn:	'params'+this.options.connection_id + '-' + counter,
				livesite:this.options.livesite,
				value:''
		};
		var id = 'paramslimit_table-'+counter;
		tableElements.set(id, new fabriktablesElement(id, opts));
		
		opts = {
				conn:	'params'+this.options.connection_id + '-' + counter,
				livesite:this.options.livesite,
				value:'',
				table : 'params'+this.options.limit_user.table_id + '-' + counter,
				published : this.options.limit_user.published,
				showintable : this.options.limit_user.showintable,
				include_calculations : this.options.limit_user.include_calculations
		};
		new elementElement('paramslimit_user-'+counter, opts);
		
		opts.table_id = this.options.limit_max.table_id;
		opts.published = this.options.limit_max.published;
		opts.showintable = this.options.limit_max.showintable;
		opts.include_calculations = this.options.limit_max.include_calculations;
		new elementElement('paramslimit_max-'+counter, opts);
		
	}
});