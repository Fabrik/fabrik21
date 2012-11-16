var adminCron = new Class({

	initialize : function(options) {
		this.options = {};
		$extend(this.options, options);
		this.watchSelector();
		this.setActive(this.options.sel)
	},

	watchSelector : function() {
		$('plugin').addEvent('change', function(e) {
			var event = new Event(e);
			var sel = event.target;
			var opt = sel.get('value');
			$$('.pluginSettings').each(function(tab) {
				if (opt == tab.id.replace('page-', '')) {
					tab.setStyles({
						display : 'block'
					});
				} else {
					tab.setStyles({
						display : 'none'
					});
				}
			});
		})
	},

	setActive : function(id) {
		if ($('page-' + id)) {
			$('page-' + id).setStyle('display', 'block');
		}
	}
})