var Tips = new Class({

	Implements: [Events, Options],

	options: {
		onShow: function(tip) {
			tip.setStyle('visibility', 'visible');
		},
		onHide: function(tip) {
			tip.setStyle('visibility', 'hidden');
		},
		showDelay: 100,
		hideDelay: 100,
		className: 'tool',
		offsets: {x: 16, y: 16},
		fixed: false
	},

	initialize: function() {
		//this.toolTip = new Element('div'
		var params = Array.link(arguments, {options: Object.type, elements: $defined});
		this.setOptions(params.options || null);
		this.toolTip = new Element('div',{'class': this.options.className + '-tip'}).inject(document.body);
		if (this.options.className) this.toolTip.addClass(this.options.className);
		//just for jceutilities.js compat when in mootools 1.2
		this.wrapper = new Element('div').inject(this.toolTip);
		var top = new Element('div', {'class': 'tip-top'}).inject(this.toolTip);
		this.container = new Element('div', {'class': 'tip'}).inject(this.toolTip);
		var bottom = new Element('div', {'class': 'tip-bottom'}).inject(this.toolTip);
		this.toolTip.setStyles({position: 'absolute', top: 0, left: 0, visibility: 'hidden'});
		if (params.elements) this.attach(params.elements);
	},
	
	attach: function(elements) {
		$$(elements).each(function(element) {
			var title = element.retrieve('tip:title', element.get('title'));
			if($type(title) !== false) {
				var dual = title.split('::');
				var text = element.retrieve('tip:text', element.get('rel') || element.get('href'));
				if (dual.length > 1) {
					var text = dual[1].trim();
					var title = dual[0].trim();
				}else{
					var text = dual[0].trim();
					var title = '';
				}
				element.store('tip:title', title);
				element.store('tip:text', text);
				var enter = element.retrieve('tip:enter', this.elementEnter.bindWithEvent(this, element));
				var leave = element.retrieve('tip:leave', this.elementLeave.bindWithEvent(this, element));
				element.addEvents({mouseenter: enter, mouseleave: leave});
				if (!this.options.fixed) {
					var move = element.retrieve('tip:move', this.elementMove.bindWithEvent(this, element));
					element.addEvent('mousemove', move);
				}
				element.store('tip:native', element.get('title'));
				element.erase('title');
			}
		}, this);
		return this;
	},
	
	detach: function(elements) {
		$$(elements).each(function(element) {
			element.removeEvent('mouseenter', element.retrieve('tip:enter') || $empty);
			element.removeEvent('mouseleave', element.retrieve('tip:leave') || $empty);
			element.removeEvent('mousemove', element.retrieve('tip:move') || $empty);
			element.eliminate('tip:enter').eliminate('tip:leave').eliminate('tip:move');
			var original = element.retrieve('tip:native');
			if (original) element.set('title', original);
		});
		return this;
	},
	
	elementEnter: function(event, element) {
		$A(this.container.childNodes).each(Element.dispose);
		var title = element.retrieve('tip:title');
		if (title) {
			this.titleElement = new Element('div', {'class': this.options.className + '-title'}).inject(this.container);
			this.fill(this.titleElement, title);
		}
		
		var text = element.retrieve('tip:text');
		if (text) {
			this.textElement = new Element('div', {'class': this.options.className + '-text'}).inject(this.container);
			this.fill(this.textElement, text);
		}
		this.timer = $clear(this.timer);
		this.timer = this.show.delay(this.options.showDelay, this);

		this.position((!this.options.fixed) ? event : {page: element.getPosition()});
	},
	
	elementLeave: function(event) {
		$clear(this.timer);
		this.timer = this.hide.delay(this.options.hideDelay, this);
	},
	
	elementMove: function(event) {
		this.position(event);
	},
	
	position: function(event) {
		var size = window.getSize(), scroll = window.getScroll();
		var tip = {x: this.toolTip.offsetWidth, y: this.toolTip.offsetHeight};
		var props = {x: 'left', y: 'top'};
		for (var z in props) {
			var pos = event.page[z] + this.options.offsets[z];
			if ((pos + tip[z] - scroll[z]) > size[z]) pos = event.page[z] - this.options.offsets[z] - tip[z];
			this.toolTip.setStyle(props[z], pos);
		}
	},
	
	fill: function(element, contents) {
		(typeof contents == 'string') ? element.set('html', contents) : element.adopt(contents);
	},

	show: function() {
		this.fireEvent('show', this.toolTip);
	},

	hide: function() {
		this.fireEvent('hide', this.toolTip);
	}

});