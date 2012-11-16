var fbCheckBox = FbElement.extend({

	initialize: function(element, options, lang) {
		this.parent(element, options);
		this.plugin = 'fabrikcheckbox';
		this.setOptions(element, options);
		this.lang ={
			please_enter_value:'Please enter a value and/or label'
		};
		$extend(this.lang, lang);
		if(this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	},
	
	watchAddToggle : function() {
		var c = this.getContainer();
		var d = c.getElement('div.addoption');

		var a =c.getElement('.toggle-addoption');
		if(this.mySlider){
			//copied in repeating group so need to remove old slider html first
			var clone = d.clone();
			var fe = c.getElement('.fabrikElement');
			d.getParent().destroy();
			fe.adopt(clone);
			d = c.getElement('div.addoption');
			d.setStyle('margin', 0);
		}
		this.mySlider = new Fx.Slide(d, {
			duration : 500
		});
		this.mySlider.hide();
		a.addEvent('click', function(e) {
			new Event(e).stop();
			this.mySlider.toggle();
		}.bind(this));
	},
	
	watchAdd:function(){
		if(this.options.allowadd == true && this.options.editable !== false) {
			var id = this.options.element;
			var c = this.getContainer();
			c.getElement('input[type=button]').addEvent( 'click', function(event) {
				var l = c.getElement('input[name=addPicklistLabel]');
				var v = c.getElement('input[name=addPicklistValue]');
				var label = l.value;
				if(v) {
					var val = v.value;
				}else{
					val = label;
				}
				if (val === '' || label === '') {
					alert(this.lang.please_enter_value);
				}
				else {
					var r = this.subElements.getLast().findUp('div').clone();
					r.getElement('input').value = val;
					var lastid = r.getElement('input').id.replace(id + '_', '').toInt();
					lastid++;
					r.getElement('input').checked = 'checked';
					r.getElement('input').id = id + '_' + lastid;
					r.getElement('label').setProperty('for', id + '_' + lastid);
					r.getElement('span').setText(label);
					r.injectAfter(this.subElements.getLast().findUp('div'));
					this._getSubElements();
					var e = new Event(event).stop();
					if (v) {
						v.value = '';
					}
					l.value = '';
					this.addNewOption(val, label);
				}
			}.bind(this));
		}
	},
	
	getValue: function() {
		if(!this.options.editable) {
			return this.options.value;
		}
		var ret = [];
		if(!this.options.editable) {
			return this.options.value;
		}
		this._getSubElements().each( function(el) {
			if(el.checked) {
				ret.push(el.get('value'));
			}
		});
		return ret;
	},

	setOptions: function(element, options) {
		this.element = $(element);
		var d = [];
		this.options = {
			element:       element,
			value: d,
			defaultVal:d
		};
		$extend(this.options, options);

		this._getSubElements();
		this.setorigId();
	},
	
	setorigId: function()
	{
		if(this.options.repeatCounter > 0) {
			var e = this.options.element;
			this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
		}
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
	
	update: function(val) {
		if($type(val) == 'string') {
			val = val.split(this.options.splitter);
		}
		if (!this.options.editable) {
			this.element.innerHTML = '';
			if(val === '') {
				return;
			}
			var h = $H(this.options.data);
			val.each(function(v) {
				this.element.innerHTML += h.get(v) + "<br />";	
			}.bind(this));
			return;
		}
		this._getSubElements();
		this.subElements.each( function(el) {
			var chx = false;
			val.each(function(v) {
				if(v == el.value) {
					chx = true;
				}
			}.bind(this));
			el.checked = chx;
		}.bind(this));
	},
	
	cloned:function(){
		this.renewChangeEvents();
		if(this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	}

});