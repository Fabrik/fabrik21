var fbTablePivot = FbTablePlugin.extend({
	
	initialize: function(tableform, options, lang) {
		this.setOptions(tableform, options);
		this.lang = Object.extend({'selectrow':'Please select a row!'}, lang || {});
		window.addEvent('domready', function() {
			var t = this.tableform.getElement('input[name=tableid]');
			//in case its in a viz
			if($type(t) === false) {
				return false;
			}
			this.tableid = t.value;
			this.requireChecked = false;
			this.watchButton();
		}.bind(this));
	},
	makePopUp: function() {
		var url = this.options.liveSite + "index.php?option=com_fabrik&controller=table.pivot&task=popupwin&tmpl=component&iframe=1&id="+this.tableid+"&renderOrder="+this.options.renderOrder;
		this.tableform.getElements('input[name^=ids]').each( function(id) {
			if(id.getValue() !== false && id.checked !== false) {
				url += "&ids[]="+id.getValue();
			}
		});
		var id = 'email-table-plugin';
		this.windowopts = {
			'id': id,
			title: 'Email',
			contentType: 'xhr',
			loadMethod:'xhr',
			contentURL: url,
			width: 520,
			height: 420,
			evalScripts:true,
			y:100,
			'minimizable':false,
			'collapsible':true,
			onContentLoaded: function() {
				var myfx = new Fx.Scroll(window).toElement(id);

			}.bind(this)
		};

		if(this.options.mooversion > 1.1) {
			var mywin = new MochaUI.Window(this.windowopts);
		} else {
			document.mochaDesktop.newWindow(this.windowopts);
		}
	},
	
	buttonAction:function(){
		this.makePopUp();
	}
	
});