var fbDropdown = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.setOptions(element, options);
		this.plugin = 'fabrikdropdown';
		this.lang = {
			please_enter_value:'Please enter a value and/or label'
		};
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
	
	watchAdd:function() {
		if(this.options.allowadd === true && this.options.editable !== false) {
			var id = this.element.id;
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
					var opt = new Element('option', {
						'selected':'selected',
						'value': val
					}).appendText(label).injectInside($(this.element.id));
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
	
	getValue:function() {
		if(!this.options.editable) {
			return this.options.value;
		}
		if($type(this.element.get('value')) === false) {
			return '';
		}
		return this.element.get('value');
	},
	
	reset: function()
	{
		var v = this.options.defaultVal.join(this.options.splitter);
		this.update(v);
	},
	
	update: function(val) {
		if($type(val) == 'string') {
			val = val.split(this.options.splitter);
		}
		if($type(val) == false) {
			val = [];
		}
		this.getElement();
		if($type(this.element) === false) {
			return;
		}
		this.options.element = this.element.id;
		if (!this.options.editable) {
			this.element.innerHTML = '';
			var h = $H(this.options.data);
			val.each(function(v) {
				this.element.innerHTML += h.get(v) + "<br />";	
			}.bind(this));
			return;
		}
		for (var i = 0; i < this.element.options.length; i++) {
			if (val.indexOf(this.element.options[i].value) != -1) {
				this.element.options[i].selected = true;
			}else{
				this.element.options[i].selected = false;
			}
		}
		this.watchAdd();
	},
	
	cloned : function()
	{
		this.renewChangeEvents();
		if(this.options.allowadd === true && this.options.editable !== false) {
			this.watchAddToggle();
			this.watchAdd();
		}
	}
	
});