var tablefieldsElement = new Class({
	
	initialize: function(el, options) {
		this.el = el;
		this.options = {
			conn:null
		};
		$extend(this.options, options);
		this.updateMeEvent = this.updateMe.bindAsEventListener(this);
		if($type($(this.options.conn) === false)) {
			this.cnnperiodical = this.getCnn.periodical(500, this);
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
		$clear(this.cnnperiodical);
	},
	
	setUp:function() {
		this.el = $(this.el);
		$(this.options.conn).addEvent('change', this.updateMeEvent);
		$(this.options.table).addEvent('change', this.updateMeEvent);
			
		//see if there is a connection selected
		var v = $(this.options.conn).get('value');
		if(v != '' && v != -1) {
			this.periodical = this.updateMe.periodical(500, this);
		}
	},
	
	updateMe: function(e) {
		if(e) {
			new Event(e).stop();
		}
		if($(this.el.id+'_loader')) {
			$(this.el.id+'_loader').setStyle('display','inline');
		}
		var cid = $(this.options.conn).get('value');
		var tid = $(this.options.table).get('value');
		if(!tid) {
			return;
		}
		$clear(this.periodical);
		var url = this.options.livesite + 'index.php?option=com_fabrik&format=raw&controller=plugin&&task=pluginAjax&g=visualization&plugin=chart&method=ajax_fields&showall=true&cid=' + cid + '&t=' + tid;
		var myAjax = new Ajax(url, { method:'get', 
			onComplete: function(r) {
				var opts = eval(r);
				this.el.empty();
				opts.each( function(opt) {
					var o = {'value':opt.value};
					if(opt.value == this.options.value) {
						o.selected = 'selected';
					}
					
					new Element('option', o).appendText(opt.label).injectInside(this.el);
				}.bind(this));
				if($(this.el.id+'_loader')) {
					$(this.el.id+'_loader').setStyle('display','none');
				}
			}.bind(this)
		}).request();
	}
});