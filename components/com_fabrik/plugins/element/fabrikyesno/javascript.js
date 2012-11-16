var fbYesno =  FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikyesno';
		this.setOptions(element, options);
	},
	getValue: function() {
		if(!this.options.editable) {
			return this.options.value;
		}
		var v = '';
		this._getSubElements().each(function(sub) {
			if(sub.checked) {
				v = sub.get('value');
				return v;
			}
			return null;
		});
		return v;
	},
	
	update : function(val) {
		if (!this.options.editable) {
			if (val === '') {
				this.element.innerHTML = '';
				return;
			}
			this.element.innerHTML = $H(this.options.data).get(val);
			return;
		} else {
			var els = this._getSubElements();
			if ($type(val) == 'array') {
				els.each(function(el) {
					if (val.contains(el.value)) {
						el.setProperty('checked', 'checked');
					}
				});
			} else {
				els.each(function(el) {
					if (el.value == val) {
						el.setProperty('checked', 'checked');
					}
				});
			}
		}
	},
	
	setValue: function(v)
	{
		if(!this.options.editable) {
			return;
		}
		this._getSubElements().each(function(sub) {
			if(sub.value == v) {
				sub.checked = 'checked';
			}
		});
	},
	
	//get the sub element which are the checkboxes themselves
	
	_getSubElements: function() {
		if(!this.element) {
			this.subElements = $A([]);
		}else{
			this.subElements = this.element.getElements('input');
		}
		return this.subElements;
	},

	renewChangeEvents : function() {
		this._getSubElements();
		this.subElements.each( function(el) {
			el.removeEvents('change');
		});
		this.changeEvents.each(function (js) {
			this.addNewEventAux('change', js);
		}.bind(this));
	},
	
	addNewEventAux: function(action, js) {
		this._getSubElements();
		this.subElements.each( function(el) {
			el.addEvent(action, function(e) {
				$type(js) === 'function' ? js.delay(0) : eval(js);
			});
		});		
	},
	
	addNewEvent: function(action, js) {
		if(action == 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
		}else{
			if (action === 'change') {
				this.changeEvents.push(js);
			}
			this.addNewEventAux(action, js);
		}
	}
});