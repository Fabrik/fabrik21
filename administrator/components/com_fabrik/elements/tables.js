var tablesElement = new Class({
	
	initialize: function(el, options) {
		this.el = el;
		this.options = {
			conn:null
		};
		$extend(this.options, options);
		this.updateMeEvent = this.updateMe.bindAsEventListener(this);
		//if loading in a form plugin then the connect is not yet avaiable in the dom
		if($type($(this.options.conn)) === false) {
			this.periodical = this.getCnn.periodical(500, this);
		}else{
			this.setUp();
		}
	},
	
	cloned:function()
	{
		
	},
	
	getCnn:function() {
		if($type($(this.options.conn)) === false) {
			return;
		}
		this.setUp();
		$clear(this.periodical);
	},
	
	setUp:function() {
		this.el = $(this.el);
		$(this.options.conn).addEvent('change', this.updateMeEvent);
		//see if there is a connection selected
		var v = $(this.options.conn).get('value');
		if(v != '' && v != -1) {
			this.updateMe();
		}
	},
	
	updateMe: function(e) {
		if(e) {
			new Event(e).stop();
		}
		if($(this.options.conn+'_loader')) {
			$(this.options.conn+'_loader').setStyle('display','inline');
		}
		var cid = $(this.options.conn).get('value');
		// $$ hugh - why are we hard coding g=visualization and plugin=chart?
		// I presume it's because we have to specify something in those fields so we'll load the
		// model and have the default FabrikModelPlugin class, which has the ajax_tables method.
		// And because we aren't really a viz/plugin, we picked something at random?
		// Anywyay - this is breaking stuff for people because the chart viz wasn't in the b2 ZIP.
		var url = this.options.livesite + 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=visualization&plugin=chart&method=ajax_tables&cid=' + cid;
		// $$$ hugh - changed this to 'get' method, because some servers barf (Length Required) if
		// we send it a POST with no postbody.
		var myAjax = new Ajax(url, { method:'get', 
			onComplete: function(r) {
				var opts = eval(r);
				if($type(opts) !== false) {
					this.el.empty();
					opts.each( function(opt) {
						//var o = {'value':opt.value};//wrong for calendar
						var o = {'value':opt};
						if(opt == this.options.value) {
							o.selected = 'selected';
						}
						if($(this.options.conn+'_loader')) {
							$(this.options.conn+'_loader').setStyle('display','none');
						}
						new Element('option', o).appendText(opt).injectInside(this.el);
					}.bind(this));
				}
			}.bind(this),
			onFailure:function(r) {
				if($(this.options.conn+'_loader')) {
					$(this.options.conn+'_loader').setStyle('display','none');
				}
			}
		}).request();
	}
});