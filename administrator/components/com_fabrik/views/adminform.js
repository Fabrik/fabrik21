fabrikAdminForm = PluginManager.extend({
	initialize : function(plugins) {
		var lang = arguments[1] || {};
		this.plugins = plugins;
		this.counter = 0;
		this.translate = {};
		$extend(this.translate, lang);
		this.opts = this.opts || {};
		this.deletePluginClick = this.deletePlugin.bindAsEventListener(this);
		this.watchAdd();
		this.opts.actions = [ {
			'value' : 'front',
			'label' : this.translate.front_end
		}, {
			'value' : 'back',
			'label' : this.translate.back_end
		}, {
			'value' : 'both',
			'label' : this.translate.both
		} ];
		this.opts.when = [ {
			'value' : 'new',
			'label' : this.translate['new']
		}, {
			'value' : 'edit',
			'label' : this.translate.edit
		}, {
			'value' : 'both',
			'label' : this.translate.both
		} ];
		this.selPlugins = this._makeSel('inputbox elementtype', 'params[plugin_events][]', this.plugins, '');
	},

	getPluginTop : function(plugin, loc, when) {
		return new Element('tr').adopt([ new Element('td').appendText(this.translate['do'] + ' ').adopt(
				this._makeSel('inputbox elementtype', 'params[plugin][]', this.plugins, plugin)).appendText(' ' + this.translate['in'] + ' ').adopt(
				this._makeSel('inputbox elementtype', 'params[plugin_locations][]', this.opts.actions, loc)).appendText(' ' + this.translate['on'] + ' ').adopt(
				this._makeSel('inputbox events', 'params[plugin_events][]', this.opts.when, when)) ]);
	}

});