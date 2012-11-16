var fabrikPackage = new Class({
	
	initialize: function(options){
		
		this.options = {
			liveSite 	:'',
			'mooversion':1.1,
			'tmpl':'components/com_fabrik/views/package/tmpl/default/images/',
			'loading':'loading'
		};
		$extend(this.options, options);
		this.blocks = $H();
	},

	startLoading: function(senderBlock, msg, inline){
		msg = $pick(msg, this.options.loading);
		var inline = inline ? inline : false;
		if($type(senderBlock) !== false){
			if($type(senderBlock) === 'element'){
				senderBlock = senderBlock.id;
			}
			// is it in a mocha mt1.2 window?
			var b = this.blocks.get(senderBlock);
			if($type(b)!== false && (b.options.winid !=='' && this.options.mooversion > 1.1)){
				var win = MochaUI.Windows.instances.get(b.options.winid);
				if (win) {
				win.showSpinner();
				}
			}else{
				var s = this.ensureLoader(senderBlock, inline);
				if(!inline){
					s.addClass('fbPackageStatusOverlay');
					var l = window.getWidth()/2 - s.getWidth()/2;
					var t = window.getScrollTop() + (window.getHeight()/2 - s.getHeight()/2);
					s.setStyles({left:l, top:t});
					this.overlay.setStyles({top: window.getScrollTop(), height: window.getHeight()});
					this.overlay.effect('opacity', {'duration':700}).start(0, 0.3);
				}else{
					s.removeClass('fbPackageStatusOverlay');
				}
				s.getElement('img').setStyle('opacity', '1');
				s.getElement('span').setText(msg);
				s.effect('opacity', {'duration':500}).start(0, 1);
			}
		}
	},
	
	ensureLoader:function(senderBlock, inline){
		var inline = inline ? inline : false;
		var existing = inline ? $(senderBlock).getParent().getElement('.fbPackageStatus') : $('fbPackageLoader');
		if($type(existing) === false){
			var i = new Element('img', {'src':this.options.liveSite + this.options.tmpl + 'ajax-loader.gif'});
			var s = new Element('span');
			var d = new Element('div', {'class':'fbPackageStatus'}).adopt(i).adopt(s);
			if (inline){
				d.injectAfter($(senderBlock));
			}else{
				d.id = 'fbPackageLoader';
				d.injectInside(document.body);
			}
			if(!$('fbPackageOverlay')) {
				this.overlay = new Element('div', {'id':'fbPackageOverlay'}).injectInside(document.body);
				this.overlay.effect('opacity', {'duration':5500}).set(0);
			}
		}
		var s = inline? $(senderBlock).getParent().getElement('.fbPackageStatus') : $('fbPackageLoader');
		s.effect('opacity', {}).set(0);
		return s;
	},
	
	stopLoading: function(senderBlock, msg, inline){
		var inline = inline ? inline : false;
		msg = $pick(msg, 'complete');
		if($type(senderBlock) !== false && $type($(senderBlock)) !== false){
		// is it in a mocha mt1.2 window?
			var b = this.blocks.get($(senderBlock).id);
			if($type(b) !== false && (b.options.winid !=='' && this.options.mooversion > 1.1)){
				var win = MochaUI.Windows.instances.get(b.options.winid);
				if (win) {
					win.hideSpinner();
				}
			}else{
				var s = this.ensureLoader(senderBlock, inline);
				if (!inline) {
					this.overlay.effect('opacity', {'duration':700}).start(0.3, 0);
				}
				s.getElement('span').setText(msg);
				var fx = s.effects({duration: 1000, transition: Fx.Transitions.Sine.easeInOut});
				(function(){fx.start(
						{'opacity': '0'}
				);
				}).delay(200);
			}
		}
	},

	addBlock: function( blockid, block ){
		this.blocks.set(blockid, block);
	},
	
	removeBlock: function( blockid ){
		// attempt to remove block? from memory
		this.blocks.set(blockid, null);
		this.blocks.remove( blockid );
	},
	
	// bind a block object to listen to another block objects messages
	
	bindListener:function(fromId, toId){
		this.blocks.each(function(val, key){
			if(toId == key){
				val.addListenTo(fromId);
			}
		});	
	},
	
	// broadcast messages to all blocks
	// @TODO really tables and forms etc should Implement an abstract observable class
	
	sendMessage:function(senderBlock, task, taskStatus, json, msg){
		msg = $pick(msg, 'complete');
		if($type(json) !== 'object'){
			json = Json.evaluate(json);
		}
		this.stopLoading(senderBlock, msg);
		this.blocks.each(function(block, key){
			try{
				block.receiveMessage(senderBlock, task, taskStatus, json);
			}catch(err){}
		});
	},
	
	submitfabrikTable: function(tableid, task){
		this.blocks.each(function(block, key){
			if(key == 'table_' + tableid){
				this.startLoading($('table_' +  tableid));
				if(block.submitfabrikTable(task) === false){
					this.stopLoading('table_' + tableid, this.options.loading);
				}
			}
		}.bind(this));
	},
	
	openRedirectInMocha: function(url){
		var opts = {
		'id': 'redirect',
		title: '',
		contentType: 'xhr',
		loadMethod: 'xhr',
		contentURL: url,
		width: 300,
		height: 320,
		'minimizable': false,
		'collapsible': true,
		onContentLoaded: function(){
			(function(){oPackage.resizeMocha('redirect');}).delay(1000);
		}.delay(1000)
	};
		if(this.options.mooversion > 1.1){
			var win = new MochaUI.Window(opts);
		}else{
			document.mochaDesktop.newWindow(opts);
		}
		this.stopLoading();
	},
	
	resizeMocha:function(win)
	{
		var myfx = new Fx.Scroll(window).toElement(win);
		//resize //@TODO add check to ensure window size isnt greater than browser window
		var windowEl = $(win);
		if (this.options.mooversion > 1.1) {
			var currentInstance = MochaUI.Windows.instances.get(windowEl.id);
			var contentWrapperEl = currentInstance.contentWrapperEl;
			var contentEl = currentInstance.contentEl;
		} else {
			contentWrapperEl = windowEl.getElement('.mochaContent');
			contentEl = windowEl.getElement('.mochaScrollerpad');
		}
		var h = contentEl.offsetHeight < window.getHeight() ? contentEl.offsetHeight : window.getHeight();
		var w = contentWrapperEl.getSize().scrollSize.x + 40 < window.getWidth() ? contentWrapperEl.getSize().scrollSize.x + 40 : window.getWidth();
		contentWrapperEl.setStyle('height', h);
		contentWrapperEl.setStyle('width', w);
		if (this.options.mooversion > 1.1) {
			currentInstance.drawWindow(windowEl);
		} else {
			document.mochaDesktop.drawWindow(windowEl);
		}
	},
	
	closeMocha: function(win)
	{
		var mv = this.options.mooversion;
		(function(){
			if(mv > 1.1) {
				if(mv == 1.2){
					win = $(win);
				}
				MochaUI.closeWindow(win);
			} else {
				document.mochaDesktop.closeWindow(win);
			}
			}).delay(1500);
	}
});

var Plugins = new Class({
	
	runPlugins : function(func, event) {
		var args = $A(arguments).filter(function(a, k) {
			return k > 1;
		});
		var ret = true;
		// ie wierdness with multple table plugins in content article?
		if ($type(this.options) === false){
			return;
		}
		// $$$ hugh - had to put this back to the way it was, the $H/bind thing just flat out wasn't working
		//$H(this.options.plugins).each( function(plugin) {
		this.options.plugins.each( function(plugin) {
			if ($type(plugin) !== false && $type(plugin[func]) != false) {
				if (plugin[func](event, args) == false) {
					ret = false;
				}
			}
		});
		//}.bind(this));
		return ret;
	},
	
	addPlugin : function(plugin) {
			this.options.plugins.push(plugin);
	}
});