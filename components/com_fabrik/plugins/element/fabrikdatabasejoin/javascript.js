var fbDatabasejoin = FbElement.extend({
	initialize: function(element, options) {
		if (element == null) {
			return;
		}
		this.changeEvents = [];
		this.canAppend = false;
		this.parent(element, options);
		this.plugin = 'fabrikdatabasejoin';
		this.options = {
			'liveSite':'',
			'popupform':49,
			'id':0,
			'formid':0,
			'key':'',
			'label':'',
			'popwiny':0,
			'windowwidth':360,
			'displayType':'dropdown',
			autoCompleteOpts:null
		};
		$extend(this.options, options);
		this.setOptions(element, this.options);
		//if users can add records to the database join drop down
		if(this.options.allowadd === true && this.options.editable !== false) {
			this.startEvent = this.start.bindAsEventListener(this);
			this.watchAdd();
			//register the popup window with the form this element is in
			//do this so that the database join drop down can be updated
			oPackage.bindListener('form_' + this.options.popupform, 'form_' + this.options.formid);
		}
		
		if (this.options.editable !== false) {
			this.watchSelect();
			
			if(this.options.showDesc === true) {
				this.element.addEvent('change', this.showDesc.bindAsEventListener(this));
			}
			this.watchCheckbox();
		}
		
	},
	
	watchCheckbox:function(){
		if(this.options.displayType == 'checkbox'){
			// $$$rob 15/07/2011 - when selecting checkboxes have to programatically select hidden checkboxes which store the join ids.
			document.getElements('input[name*='+this.options.elementName+'___'+this.options.elementShortName+']').each(function(i, k){
				i.addEvent('click', function(e){
						document.getElements('input[name*='+this.options.elementName+'___id]')[k].checked = i.checked;
				}.bind(this));
			}.bind(this));
		}
	},
	
	watchSelect:function(){
		var sel = this.getContainer().getElement('.toggle-selectoption');
		if($type(sel) !== false) {
			sel.addEvent('click', this.selectRecord.bindAsEventListener(this));
			//register the popup window with the form this element is in
			//do this so that the database join drop down can be updated
			oPackage.bindListener('table_' + this.options.tableid, 'form_' + this.options.formid);
		}
	},
	
	watchAdd:function(){
		var b = this.getContainer().getElement('.toggle-addoption');
		//if duplicated remove old events
		b.removeEvents('click');
		b.addEvent('click', this.startEvent);
	},
	
	selectRecord: function(e) {
  	e = new Event(e).stop();
  	var id = this.element.id + '-popupwin';
  	var url = this.options.liveSite + "index.php?option=com_fabrik&view=table&tmpl=component&layout=dbjoinselect&_postMethod=ajax&tableid=" + this.options.tableid;
  	url += "&triggerElement="+this.element.id;
  	url += "&winid="+id;
  	url += "&resetfilters=1";
  	this.windowopts = {
			'id': id,
			title: 'Select',
			contentType: 'xhr',
			loadMethod: 'xhr',
			evalScripts:true,
			contentURL: url,
			width: this.options.windowwidth.toInt(),
			height: 320,
			y: this.options.popwiny,
			'minimizable': false,
			'collapsible': true,
			onContentLoaded: function() {
				oPackage.resizeMocha(id);
			}
		};
		if(this.options.mooversion > 1.1) {
			var mywin = new MochaUI.Window(this.windowopts);
		}else{
			document.mochaDesktop.newWindow(this.windowopts);
		}
	},
	
	getValue:function() {
		this.getElement();
		if(!this.options.editable) {
			return this.options.value;
		}
		if($type(this.element) === false) {
			return '';
		}
		switch(this.options.display_type) {
			default:
			case 'dropdown':
				if($type(this.element.get('value')) === 'null') {
					return '';
				}
				return this.element.get('value');
				break;
				
			case 'auto-complete':
				return this.element.value;
				break;
			case 'radio':
				var v = '';
				this._getSubElements().each(function(sub) {
					if(sub.checked) {
						v = sub.get('value');
						return v;
					}
					return null;
				});
				return v;
				break;
		}
	},
	
	start: function(event) {
		this.activeAdd = true;
		var e = new Event(event);
		this.canAppend = true;
		var url = this.options.liveSite + "index.php?option=com_fabrik&view=form&tmpl=component&_postMethod=ajax&fabrik=" + this.options.popupform;
		var id = this.element.id + '-popupwin';
		url += "&winid="+id;
		this.windowopts = {
			'id': id,
			title: 'Add',
			contentType: 'xhr',
			loadMethod:'xhr',
			contentURL: url,
			width: this.options.windowwidth.toInt(),
			height: 320,
			y:this.options.popwiny,
			'minimizable':false,
			'collapsible':true,
			onContentLoaded: function() {
				oPackage.resizeMocha(id);
			}
		};
				
		if(this.options.mooversion > 1.1) {
			this.win = new MochaUI.Window(this.windowopts);
		}else{
			document.mochaDesktop.newWindow(this.windowopts);
		}
		e.stop();

	},
	
	update:function(val) {
		this.getElement();
		if($type(this.element) === false) {
			return;
		}
		if (!this.options.editable) {
			this.element.innerHTML = '';
			if(val === '') {
				return;
			}
			val = val.split(this.options.splitter);
			//was a security issue as options.data contained unaccessible element data
			//var h = $H(this.options.data);
			var h = this.form.getFormData();
			if ($type(h) === 'object') {
				h = $H(h);
			}
			val.each(function(v) {
				if ($type(h.get(v)) !== false) {
					this.element.innerHTML += h.get(v) + "<br />";
				}else{
					//for detailed view prev/next pagination v is set via elements 
					//getROValue() method and is thus in the correct format - not sure that
					// h.get(v) is right at all but leaving in incase i've missed another scenario 
					this.element.innerHTML += v + "<br />";
				}	
			}.bind(this));
			return;
		}
		this.setValue(val);
	},
	
	setValue:function(val) {
		var found = false;
		if($type(this.element.options) !== false) { //needed with repeat group code
			for (var i = 0; i < this.element.options.length; i++) {
				if (this.element.options[i].value == val) {
					this.element.options[i].selected = true;
					found = true;
					break;
				}
			}
		}
		if(!found && this.options.show_please_select) {
			if (this.element.getTag() == 'input') {
				this.element.value = val;
				if(this.options.display_type == 'auto-complete') {
					//update the field label as well (do ajax as we dont know what the label should be (may included concat etc))
					var myajax = new Ajax(this.options.liveSite+'index.php?option=com_fabrik&view=form&format=raw&fabrik='+this.form.id+'&rowid='+val, {
						options:{
							'evalScripts':true
						},
						onSuccess:function(r) {
							r = Json.evaluate(r.stripScripts());
							var v = r.data[this.options.key];
							var l = r.data[this.options.label];
							if($type(l) !== false){
								labelfield = this.element.findClassUp('fabrikElement').getElement('.autocomplete-trigger');
								this.element.value = v;
								labelfield.value = l;
							}
						}.bind(this)
					}).request();
				}
			}else{
				if (this.options.displayType == 'dropdown') {
					this.element.options[0].selected = true;
				} else {
					this.element.getElements('input').each(function(i){
						if (i.get('value') == val) {
							i.checked = true;
						}
					});
				}
			}
		}
		this.options.value = val;
	},

	appendInfo: function(data, key) {
		if(data === '' || $type(data.data) === false || this.canAppend === false) {
			this.closeWin();
			return;
		}
		this.canAppend = false;
		//$$$ rob only update if element found - see http://fabrikar.com/forums/showthread.php?p=100896
		if ($type(data.data) != 'array') {
			// only do this for selected row from popup table
			if($H(data.data).getKeys().contains(this.options.element) == false) {
				this.closeWin();
				return;
			}
		} else {
			// popup add form has been submitted - does that form cotain this element's key
			// sub optimal fix here as there could be 2 db joins to the same key
			if($H(data.data[0][0].data).getKeys().contains(this.options.key) == false) {
				return;
			}
		}

		var key = this.options.key;
		var label = this.options.label;
		var rowid = data.rowid;
		var formid = data.formid;
		data = data.data;
			var myajax = new Ajax(this.options.liveSite+'index.php?option=com_fabrik&view=form&format=raw&fabrik='+formid+'&rowid='+rowid, {
				options:{
					'evalScripts':true
				},
				onSuccess:function(r) {
					r = Json.evaluate(r.stripScripts());
					var v = r.data[this.options.key];
					var l = r.data[this.options.label];
					switch(this.options.display_type) {
						case 'dropdown':
							var opts = {'value':v};
							if(this.activeAdd == true) {
								opts.selected = 'selected';
							}
							$(this.element.id).adopt(new Element('option', opts).appendText(l));
							break;
					case 'auto-complete':
						labelfield = this.getAutoCompleteLabelField();
						this.element.value = v;
						labelfield.value = l;
						break;
					default:
						var subEls = this._getSubElements();
						var optName = subEls.length == 0 ? this.options.element : subEls[0].name;
						var opts = {
				  		'class': 'fabrikinput',
				  		'type': 'radio',
				  		'name': optName,
				  		'value': v
				  	};
						if (this.activeAdd == true) {
							opts.checked = true;
						}
						var opt = new Element('div', {
				  		'class': 'fabrik_subelement'
				  	}).adopt(new Element('label').adopt([new Element('input', opts), new Element('span').setText(l)]));
						opt.injectAfter($(this.element.id).getElements('.fabrik_subelement').getLast());
						break;
				}
				this.activeAdd = false;
				if($type(this.element) === false) {
					return;
				}
				// $$$ hugh - fire change and blur events, for things like CDD and autofill
				this.element.fireEvent('change', new Event.Mock(this.element, 'change'));
				this.element.fireEvent('blur', new Event.Mock(this.element, 'blur'));
				this.closeWin();
			}.bind(this)
		}).request();
	},
	
	getAutoCompleteLabelField: function(){
		// this was not working when deleting a the element inside a repeat group.
		//return this.element.findClassUp('fabrikElement').getElement('input[name='+this.element.id+'-auto-complete]');
		return this.element.findClassUp('fabrikElement').getElement('input[name*=-auto-complete]');
	},
	
	closeWin:function() {
		var id = this.element.id + '-popupwin';
		//this if was at the end of the onSuccess method - but if no data added it wasnt called
		if ($type($(id)) !== false) {
			oPackage.closeMocha(id);
		}
		//needed in mt1.1
		oPackage.stopLoading('form_' + this.options.popupform);
		oPackage.stopLoading('form_' + this.options.formid);
	},
	
	getValues:function()
	{
		var v = $A([]);
		var search = (this.options.display_type != 'dropdown') ? 'input' : 'option';
			$(this.element.id).getElements(search).each(function(f) {
				v.push(f.value);
			});
		return v;
	},
	
	cloned: function(c) {
		this.renewChangeEvents();
		if(this.options.allowadd === true && this.options.editable !== false) {
			this.startEvent = this.start.bindAsEventListener(this);
			this.watchAdd();
		}
		this.watchSelect();
		this.watchCheckbox();
		if(this.options.display_type == 'auto-complete') {
			$(this.element.id).value = '';
			//update auto-complete fields id and create new autocompleter object for duplicated element
			var f = this.getContainer().getElement('.autocomplete-trigger');
			f.id = this.element.id + '-auto-complete';
			$(f.id).value = '';
			f.name = this.element.name + '-auto-complete';
			new FabAutocomplete(this.element.id, this.options.autoCompleteOpts);
		}
	},
	
	addNewEventAux: function(action, js) {
		switch (this.options.displayType) {
		case 'dropdown':
		default:
			if (this.element) {
		  	this.element.addEvent(action, function(e) {
		  		e = new Event(e).stop();
		  		($type(js) === 'function') ? js.delay(0) : eval(js);
		  	});
	  	}
			break;
		case 'radio':
		case 'checkbox':
			this._getSubElements();
			this.subElements.each(function(el) {
				el.addEvent(action, function(e) {
					($type(js) === 'function')?js.delay(0):eval(js);
				});
			});
			break;
		case 'auto-complete':
			var f = this.getAutoCompleteLabelField();
			if ($type(f) !== false) {
				f.addEvent(action, function(e) {
		  		e = new Event(e).stop();
		  		($type(js) === 'function') ? js.delay(0) : eval(js);
		  	});
		  }
			break;
		}		
	},
	
	addNewEvent: function(action, js) {
		if (action == 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
			return;
		}
		if (action === 'change') {
			this.changeEvents.push(js);
		}
		this.addNewEventAux(action, js);
	},
	
	showDesc: function(e) {
	  	e = new Event(e);
	  	var v = $(e.target).selectedIndex;
	  	var c = this.element.findClassUp('fabrikElementContainer').getElement('.dbjoin-description');
	  	var show = c.getElement('.description-' + v);
	  	c.getElements('.notice').each(function(d) {
	  		if (d === show) {
	  			var myfx = new Fx.Style(show, 'opacity', {
	  				duration: 400,
	  				transition: Fx.Transitions.linear
	  			});
	  			myfx.set(0);
	  			d.setStyle('display', '');
	  			myfx.start(0, 1);
	  		} else {
	  			d.setStyle('display', 'none');
	  		}
	  	});
	  },
	  
	  decreaseName: function(delIndex) {
		  if (this.options.displayType === 'auto-complete') {
			  var f = this.getAutoCompleteLabelField();
				if ($type(f) !== false) {
					f.name = this._decreaseName(f.name, delIndex, '-auto-complete');
					f.id = this._decreaseId(f.id, delIndex, '-auto-complete');
				}
		  }
		  var element = this.getElement();
			if ($type(element) === false) {
				return false;
			}
			if (this.hasSubElements()) {
				this._getSubElements().each(function(e) {
					e.name = this._decreaseName(e.name, delIndex);
					e.id = this._decreaseId(e.id, delIndex);
				}.bind(this));
			} else {
				if ($type(this.element.name) !== false) {
					this.element.name = this._decreaseName(this.element.name, delIndex);
				}
			}
			if ($type(this.element.id) !== false) {
				this.element.id = this._decreaseId(this.element.id, delIndex);
			}
			return this.element.id;
	  }
	
});