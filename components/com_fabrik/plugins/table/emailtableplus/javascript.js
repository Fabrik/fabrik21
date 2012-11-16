// Use HTML input type='file' form submission to access files through javascript.
// Requires use of iframes, but allows arbitrary form loading with full css control.
// Flash based uploads like FancyUpload are less flexible. Can not set events on
// iframe elements from within parent window, so callbacks are used in forms.
//
// This code requires Mocha 0.9 as loadMethod:iframe is broken in Mocha 0.7.


var fbTableEmailPlus = FbTablePlugin.extend({
	mochaWindow: null,
	iframeform: null,
	attach_button_count: 0,
	firstload: true,

	initialize: function(tableformid, options) {
		this.setOptions(tableformid, options);
	},
	
	watchPluginButton:function() {
		if (!this.firstload) {
			return;
		}
		var t = this.tableform.getElement('input[name=tableid]');
		//in case its in a viz
		if($type(t) === false){
			return false;
		}
		this.tableid = t.value;
		
		var ok = false;
		var url = this.options.liveSite + "index.php?option=com_fabrik&controller=table.emailtableplus&tmpl=component&id="+this.tableid+"&renderOrder="+this.options.renderOrder;

		this.tableform.getElements('input[name^=ids]').each(function(id) {
			if(id.get('value') !== false && id.checked !== false) {
				ok = true;
				url += "&ids[]="+id.get('value');
			}
		});

		if(!ok) {
			alert('Please select a row!');
			return;
		}

		this.windowopts = {
			title: 'Email',
			loadMethod:'iframe',
			contentURL: url,
			width: 520,
			height: 400,
			evalScripts:true,
			evalResponse:true,
			y:100,
			minimizable:false,
			collapsible:true,
			onClose: function() {
				this.firstload = true;
			}.bind(this),
			onContentLoaded: function(windowEl) {
				var contentWrapperEl = this.mochaWindow.contentWrapperEl;

				var h = contentWrapperEl.getSize().scrollSize.y + 45 < window.getHeight() ? contentWrapperEl.getSize().scrollSize.y + 45 : window.getHeight();
				var w = contentWrapperEl.getSize().scrollSize.x + 45 < window.getWidth() ? contentWrapperEl.getSize().scrollSize.x + 45 : window.getWidth();  
				contentWrapperEl.setStyle('height', h);
				contentWrapperEl.setStyle('width', w);

				var iframe = $(this.mochaWindow.contentWrapperEl.getElement('iframe'));
				var iframeDoc = iframe.contentDocument || iframe.contentWindow.document || iframe.contentWindow;
				this.iframeform = $(iframeDoc.body).getElement('form');

				this.mochaWindow.drawWindow(windowEl);
				new Fx.Scroll(window).toElement(windowEl);
			}.bind(this)
		};

		this.mochaWindow = new MochaUI.Window(this.windowopts);
	},
	
	watchSend:function(warning) {
		var err = '';
		if (this.iframeform.getElement('input[name=subject]').get('value') == '') {
			err += (warning);
		}
		if (err != '') {
			alert(err);
		}else{
			this.iframeform.submit();
		}
	},

	watchAttach:function() {
		var newid = 'attachment' + (this.attach_button_count++);
		var attach_input = new Element('input', {
			id: newid,
			name: newid,
			'class': 'inputbox fabrikinput',
			'type': 'file'
			});

		(new Element('li', {}).adopt(attach_input)).inject(this.iframeform.getElement('ul'),'bottom');
	}
});

