/**
 * @author Robert
 */

var FabAutocomplete = new Class({

	initialize: function(element, options) {
		this.options = {
			menuclass:'auto-complete-container',
			url:'index.php',
			max:10,
			container:'fabrikElementContainer',
			onSelection: Class.empty
		};
		$extend(this.options, options);
		this.options.labelelement = $(element + '-auto-complete');
		this.cache = {};
		this.selected = -1;
		this.mouseinsde = false;
		this.watchKeys = this.doWatchKeys.bindAsEventListener(this);
		this.testMenuClose = this.doTestMenuClose.bindAsEventListener(this);
		if($type(element) === false){
			return;
		}
		this.element = $(element);
		if ($type(this.element) == false){
			//specific table set as element e.g. '#table_33 #mfg_so_mstr___so_nbrvalue-auto-complete'
			this.element = document.getElement(element);
			if ($type(this.element) == false){
				return;
			}
			this.options.labelelement = this.element.getParent().getElement('.autocomplete-trigger');
		}
		this.buildMenu();
		this.spinner = oPackage.ensureLoader(this.getInputElement());
		this.getInputElement().setProperty('autocomplete', 'off');
		this.getInputElement().addEvent('keyup', this.search.bindAsEventListener(this));
	},

	doWatchKeys: function(e){
		var max = this.getListMax();
		e = new Event(e);
		if(e.key == 'enter'){
			window.fireEvent('blur');
		}
		switch(e.code){
			case 40://down
				if(this.selected + 1 < max){
					this.selected ++;
					this.highlight();
				}
				e.stop();
				break;
			case 38: //up
				if(this.selected - 1 >= -1){
					this.selected --;
					this.highlight();
				}
				e.stop();
				break;
			case 13://enter
			case 9://tab
				e.stop();
				this.makeSelection({}, this.getSelected());
				this.closeMenu();
				break;
			case 27://escape
				e.stop();
				this.closeMenu();
				break;
		}
	},
	
	doTestMenuClose:function(){
		if(!this.mouseinsde){
			this.closeMenu();
		}
	},
	
	buildMenu:function()
	{
		if ($type(document.getElement('.this.options.menuclass')) !== false) {
			this.menu = document.getElement('.this.options.menuclass');
		} else {
			//this.menu = new Element('div', {'class':this.options.menuclass, 'styles':{'position':'absolute'}}).adopt(new Element('ul')).injectAfter(this.element);
			this.menu = new Element('div', {'class':this.options.menuclass, 'styles':{'position':'absolute'}}).adopt(new Element('ul')).injectInside(document.body);
		}
		
		this.menu.addEvent('mouseenter', function(){this.mouseinsde = true;}.bind(this));
		this.menu.addEvent('mouseleave', function(){this.mouseinsde = false;}.bind(this));
		this.fx = this.menu.effect('opacity', {duration: 500, transition: Fx.Transitions.linear});
	},
	
	getInputElement:function(){
		return this.options.labelelement ? this.options.labelelement : this.element;
	},
	
	search:function(){
		var v = this.getInputElement().get('value');
		if(v == ''){this.element.value = '';};
		if (v !== this.searchText && v !== ''){
			this.element.value = v;
			this.positionMenu();
			if(this.cache[v]){
				this.populateMenu(this.cache[v]);
				this.openMenu();
			}else{
				this.spinner.setStyle('display', '');
				if(this.ajax) {
					this.closeMenu();
					this.ajax.cancel();
				}
				this.ajax = new Ajax(this.options.url, {
		  	data: {
		  		value: v
		  	},
				onComplete: this.completeAjax.bindAsEventListener(this, [v])
		  }).request();
			}
		}
		this.searchText = v;
	},
	
	completeAjax:function(r, v){
		r = Json.evaluate(r);
		this.cache[v] = r;
		this.spinner.setStyle('display', 'none');
		this.populateMenu(r);
		this.openMenu();
		window.fireEvent('fabrik.autocomplete.update', r);
	},
	
	positionMenu:function(){
		var v = this.getInputElement();
		var coords = v.getCoordinates();
		this.menu.setStyles({ 'left': coords.left, 'top': coords.bottom, 'width':coords.width });
	},
	
	populateMenu: function(data){
		this.data = data;
		var max = this.getListMax();
		var ul = this.menu.getElement('ul');
		ul.empty();
		if(data.length == 1){
			this.element.value = data[0].value;
			this.fireEvent('selection', [this, this.element.value]);
		}
		for(var i=0; i<max; i++){
			var pair = data[i];
			var li = new Element('li', {'data-value':pair.value, 'class':'unselected'}).setText(pair.text);
			li.injectInside(ul);
			li.addEvent('click', this.makeSelection.bindAsEventListener(this, [li]));
		}
		if(data.length > this.options.max){
			new Element('li').setText('....').injectInside(ul);
		}

	},
	
	makeSelection:function(e, li){
		if ($type(li) !== false) {
			this.getInputElement().value = li.getText();
			this.element.value = li.getProperty('data-value');
		}
		this.closeMenu();
		this.fireEvent('selection', [this, this.element.value]);
		this.element.fireEvent('change', {'target': this.element, 'type': 'change'});
		window.fireEvent('fabrik.autocomplete.selection', this);
	},
	
	closeMenu: function(){
		if(this.shown){
			this.shown = false;
			this.fx.start(1, 0);
			this.selected = -1;
			document.removeEvent('keydown', this.watchKeys);
			document.removeEvent('click', this.testMenuClose);
			window.fireEvent('fabrik.autocomplete.closed', this);
		}
	},
	
	openMenu: function(){
		if(!this.shown){
			this.shown = true;
			this.fx.start(0, 1);
			 document.addEvent('keydown', this.watchKeys);
			 document.addEvent('click', this.testMenuClose);
			 this.selected = 0;
			 this.highlight();
			 window.fireEvent('fabrik.autocomplete.opened', this);
		}
	},
	
	getListMax: function(){
		return this.data.length > this.options.max ? this.options.max : this.data.length;
	},
	
	getSelected: function(){
		var a = this.menu.getElements('li').filter(function(li, i){
			return i === this.selected;
		}.bind(this));
		return a[0];
	},
	
	highlight:function(){
		this.menu.getElements('li').each(function(li, i){
			i === this.selected ? li.addClass('selected') : li.removeClass('selected');
		}.bind(this));
	}
	
});

var FabCddAutocomplete = FabAutocomplete.extend({
	
	search:function(){
		var v = this.getInputElement().get('value');
		if(v == ''){this.element.value = '';};
		if (v !== this.searchText && v !== ''){
			var key = $(this.options.observerid).get('value')+'.'+v;
			this.positionMenu();
			if(this.cache[key]){
				this.populateMenu(this.cache[key]);
				this.openMenu();
			}else{
				this.spinner.setStyle('display', '');
				if(this.ajax) {
					this.closeMenu();
					this.ajax.cancel();
				}
				this.ajax = new Ajax(this.options.url, {
		  	data: {
		  		value: v,
		  		fabrik_cascade_ajax_update:1,
		  		v:$(this.options.observerid).get('value')
		  	},
				onComplete: this.completeAjax.bindAsEventListener(this, [key])
		  }).request();
			}
		}
		this.searchText = v;
	}
});

FabAutocomplete.implement(new Events);