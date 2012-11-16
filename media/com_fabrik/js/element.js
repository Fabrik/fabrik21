/**
 * @author Robert
 */
var FbElement = new Class({
	initialize : function(element, options) {
		// $$$ hugh - if you add new class variables here, remember to also add them
		// to fbFileElement
		// init below. This is part of a workaround for the extend() issue in Moo
		// 1.2 vs 1.2.4.
		this.loadEvents = []; // need to store these for use if the form is reset
		this.changeEvents = []; // need to store these for gory reasons to do with cloning
		this.strElement = element;
		this.plugin = '';
		if (element !== null) {
			this.setOptions(element, options);
		}
	},

	attachedToForm : function() {
		// put ini code in here that can't be put in initialize()
		// generally any code that needs to refer to this.form, which
		// is only set when the element is assigned to the form.
	},

	setOptions : function(element, options) {
		if ($(element)) {
			this.element = $(element);
		}
		this.options = {
			element : element,
			defaultVal : '',
			value : '',
			editable : false,
			watchElements : $H({})
		};
		$extend(this.options, options);
		this.setorigId();
	},

	/**
	 * allows you to fire an array of events to element / subelements, used in
	 * calendar to trigger js events when the calendar closes *
	 */
	fireEvents : function(evnts) {
		if (this.hasSubElements()) {
			this.subElements.each(function(el) {
				$A(evnts).each(function(e) {
					el.fireEvent(e);
				}.bind(this));
			}.bind(this));
		} else {
			$A(evnts).each(function(e) {
				if ($type(this.element) !== false) {
					this.element.fireEvent(e);
				} else {
					fconsole('couldnt fire event ' + this.plugin);
				}
			}.bind(this));
		}
	},

	getElement : function() {
		// use this in mocha forms whose elements (such as database jons) arent
		// loaded
		// when the class is ini'd
		if ($type(this.element) === false) {
			this.element = $(this.options.element);
		}
		return this.element;
	},

	// used for elements like checkboxes or radio buttons
	_getSubElements : function() {
		var element = this.getElement();
		if ($type(element) === false) {
			return false;
		}
		this.subElements = element.getElements('.fabrikinput');
		return this.subElements;
	},

	hasSubElements : function() {
		this._getSubElements();
		if ($type(this.subElements) === 'array') {
			return this.subElements.length > 0 ? true : false;
		}
		return false;
	},

	unclonableProperties : function() {
		return [ 'form' ];
	},

	runLoadEvent : function(js, delay) {
		var delay = delay ? delay : 0;
		if ($type(js) === 'function') {
			js.delay(delay);
		} else {
			if (delay == 0) {
				eval(js);
			} else {
				(function() {
					eval(js);
				}.bind(this)).delay(delay);
			}
		}
	},

	renewChangeEvents : function() {
		this.element.removeEvents('change');
		this.changeEvents.each(function (js) {
			this.addNewEventAux('change', js);
		}.bind(this));
	},
	
	addNewEventAux : function(action, js) {
		this.element.addEvent(action, function(e) {
			e = new Event(e).stop();
			$type(js) === 'function' ? js.delay(0) : eval(js);
		});
	},
	
	addNewEvent : function(action, js) {
		if (action == 'load') {
			this.loadEvents.push(js);
			this.runLoadEvent(js);
		} else {
			if (!this.element) {
				this.element = $(this.strElement);
			}
			if (this.element) {
				if (action === 'change') {
					this.changeEvents.push(js);
				}
				this.addNewEventAux(action, js);

				// $$$ hugh - I think this is all handled elsewhere now
				/*
				this.element.addEvent('blur', function(e) {
					this.validate();
				}.bind(this));
				*/
			}
		}
	},

	validate : function() {
	},

	// store new options created by user in hidden field
	addNewOption : function(val, label) {
		var c = this.element.findClassUp('fabrikElementContainer');
		var f = c.getElement('input[class=addoption]');
		// var added = $(this.options.element + '_additions').value;
		var added = f.value;
		var json = {
			'val' : val,
			'label' : label
		};
		if (added !== '') {
			var a = Json.evaluate(added);
		} else {
			a = [];
		}
		a.push(json);
		var s = '[';
		for ( var i = 0; i < a.length; i++) {
			s += Json.toString(a[i]) + ',';
		}
		s = s.substring(0, s.length - 1) + ']';
		f.value = s;
	},

	// below functions can override in plugin element classes

	skipValidation : function() {
		return false;
	},

	update : function(val) {
		if (this.element) {
			if (this.options.editable) {
				this.element.value = val;
			} else {
				this.element.innerHTML = val;
			}
		}
	},

	updateHTML : function(html) {
		if (this.element) {
			this.element.setHTML(html);
		} else {
			fconsole('didnt find element to update ' + this.options.element);
		}
	},

	getValue : function() {
		if (this.element) {
			if (this.options.editable) {
				return this.element.value;
			} else {
				return this.options.value;
			}
		}
		return false;
	},

	reset : function() {
		this.loadEvents.each(function(js) {
			this.runLoadEvent(js, 100);
		}.bind(this));
		if (this.options.editable == true) {
			this.update(this.options.defaultVal);
		}
	},

	clear : function() {
		this.update('');
	},

	onsubmit : function() {
		return true;
	},

	cloned : function(c) {
		this.renewChangeEvents();
	},
	
	applyAjaxValidations:function(){
		delete this.validationFX;
		this.options.watchElements.each(function(el){
			if (el.id == this.origId) {
				this.form.watchValidation(this.options.element, el.triggerEvent);;	
			}
		}.bind(this));
	},

	decloned : function(groupid) {
		// run when the element is decleled from the form as part of a deleted
		// repeat group
	},

	// get the wrapper dom element that contains all of the elements dom objects
	getContainer : function() {
		return this.element.findClassUp('fabrikElementContainer');
	},

	// get the dom element which shows the error messages
	getErrorElement : function() {
		return this.getContainer().getElement('.fabrikErrorMessage');
	},

	// get the fx to fade up/down element validation feedback text

	getValidationFx : function() {
		if (!this.validationFX) {
			this.validationFX = this.getErrorElement().effects({
				duration : 500,
				wait : true
			});
		}
		return this.validationFX;
	},

	setErrorMessage : function(msg, classname) {
		var classes = [ 'fabrikValidating', 'fabrikError', 'fabrikSuccess' ];
		var container = this.getContainer();

		classes.each(function(c) {
			(classname == c) ? container.addClass(c) : container.removeClass(c);
		});
		this.getErrorElement().setHTML(msg);
		this.getErrorElement().removeClass('fabrikHide');

		var parent = this.form;
		if (classname == 'fabrikError' || classname == 'fabrikSuccess') {
			parent.updateMainError();
		}

		var fx = this.getValidationFx();
		switch (classname) {
			case 'fabrikValidating':
			case 'fabrikError':
				fx.start({
					'opacity' : 1
				});
				break;
			case 'fabrikSuccess':
				fx.start({
					'opacity' : 1
				}).chain(function() {
					// only fade out if its still the success message
					if (container.hasClass('fabrikSuccess')) {
						container.removeClass('fabrikSuccess');
						this.start.delay(700, this, {
							'opacity' : 0,
							'onComplete' : function() {
								parent.updateMainError();
								classes.each(function(c) {
									container.removeClass(c);
								});
							}
						});
					}
				});
				break;
		}
	},

	setorigId: function() {
		if (this.options.repeatCounter > 0) {
			var e = this.options.element;
			this.origId = e.substring(0, e.length - 1 - this.options.repeatCounter.toString().length);
		}
	},

	decreaseName: function(delIndex) {
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
	},
	
	/**
	 * @param	string	name to decrease
	 * @param	int		delete index
	 * @param	string	name suffix to keep (used for db join autocomplete element)
	 */

	_decreaseId: function(n, delIndex, suffix) {
		var suffixFound = false;
		suffix = suffix ? suffix : false;
		if (suffix !== false) {
			if (n.contains(suffix)) {
				n = n.replace(suffix, '');
				suffixFound = true;
			}
		}
		var bits = $A(n.split('_'));
		var i = bits.getLast();
		if ($type(i.toInt()) === false) {
			return bits.join('_');
		}
		if (i >= 1 && i > delIndex) {
			i--;
		}
		bits.splice(bits.length - 1, 1, i);
		var r = bits.join('_');
		if (suffixFound) {
			r += suffix;
		}
		this.options.element = r;
		return r;
	},

	/**
	 * @param	string	name to decrease
	 * @param	int		delete index
	 * @param	string	name suffix to keep (used for db join autocomplete element)
	 */
	
	_decreaseName : function(n, delIndex, suffix) {
		suffixFound = false;
		suffix = suffix ? suffix : false;
		if (suffix !== false) {
			if (n.contains(suffix)) {
				n = n.replace(suffix, '');
				suffixFound = true;
			}
		}
		var r;
		// $$$ hugh - need to handle joined and simple repeats differently
		if (n.test(/^join\[/)) {
			var namebits = n.split('][');
			var i = namebits[2].replace(']', '').toInt();
			if (i >= 1  && i > delIndex) {
				i --;
			}
			if (namebits.length === 3) {
				i = i + ']';
			}
			namebits.splice(2, 1, i);
			r = namebits.join('][');
		}
		else {
			var i = n.match(/\[(\d+)\]/)[1];
			if (i >= 1  && i > delIndex) {
				i --;
			}
			r = n.replace(/^(.*?)\[(\d+)\](.*)$/, '$1['+i+']$3');
		}
		if (suffixFound) {
			r += suffix;
		}
		return r;
	},
	
	select:function(){},
	focus:function(){}

});

FbElement.implement(new Events);

/**
 * @author Rob contains methods that are used by any element which manipulates
 *         files/folders
 */

var FbFileElement = FbElement.extend({

	initialize : function(element, options) {
		// this.parent(element, options);
		this.loadEvents = [];
		this.folderlist = [];
	},

	ajaxFolder : function() {
		if ($type(this.element) === false) {
			return;
		}
		var el = this.element.findClassUp('fabrikElement');
		if (el == false){
			fconsole('ajaxfolder: didnt find fabrikElement for' + this.element.id);
			return;
		}
		this.breadcrumbs = el.getElement('.breadcrumbs');
		this.folderdiv = el.getElement('.folderselect');
		if ($type(this.folderdiv) === false) {
			fconsole('did not find folder div for ajaxFolder');
			return;
		}
		this.slider = new Fx.Slide(this.folderdiv, {
			duration : 500
		});
		this.slider.hide();
		this.hiddenField = el.getElement('.folderpath');
		el.getElement('.toggle').addEvent('click', function(e) {
			new Event(e).stop();
			this.slider.toggle();
		}.bind(this));
		this.watchAjaxFolderLinks();
	},

	watchAjaxFolderLinks : function() {
		this.folderdiv.getElements('a').addEvent('click', this.browseFolders.bindAsEventListener(this));
		this.breadcrumbs.getElements('a').addEvent('click', this.useBreadcrumbs.bindAsEventListener(this));
	},

	browseFolders : function(e) {
		e = new Event(e).stop();
		var a = $(e.target);
		this.folderlist.push(a.innerHTML);
		var dir = this.options.dir + this.folderlist.join(this.options.ds);
		this.addCrumb(a.innerHTML);
		this.doAjaxBrowse(dir);
	},

	useBreadcrumbs : function(e) {
		e = new Event(e).stop();
		var found = false;
		var a = $(e.target);
		var c = a.className;
		this.folderlist = [];
		var res = this.breadcrumbs.getElements('a').every(function(link) {
			if (link.className == a.className) {
				return false;
			}
			this.folderlist.push(a.innerHTML);
			return true;
		}, this);

		var home = [ this.breadcrumbs.getElements('a').shift().clone(), this.breadcrumbs.getElements('span').shift().clone() ];
		this.breadcrumbs.empty();
		this.breadcrumbs.adopt(home);
		this.folderlist.each(function(txt) {
			this.addCrumb(txt);
		}, this);
		var dir = this.options.dir + this.folderlist.join(this.options.ds);
		this.doAjaxBrowse(dir);
	},

	doAjaxBrowse : function(dir) {
		var url = this.options.liveSite
				+ "index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&plugin=fabrikfileupload&method=ajax_getFolders&element_id="
				+ this.options.id;

		new Ajax(url, {
			data : {
				'dir' : dir
			},
			onComplete : function(r) {
				r = Json.evaluate(r);
				this.folderdiv.empty();

				r.each(function(folder) {
					new Element('li', {
						'class' : 'fileupload_folder'
					}).adopt(new Element('a', {
						'href' : '#'
					}).setText(folder)).injectInside(this.folderdiv);
				}.bind(this));
				if (r.length == 0) {
					this.slider.hide();
				} else {
					this.slider.slideIn();
				}
				this.watchAjaxFolderLinks();
				this.hiddenField.value = '/' + this.folderlist.join('/') + '/';
				this.fireEvent('onBrowse');
			}.bind(this)
		}).request();
	},

	addCrumb : function(txt) {
		this.breadcrumbs.adopt(new Element('a', {
			'href' : '#',
			'class' : 'crumb' + this.folderlist.length
		}).setText(txt), new Element('span').setText(' / '));
	}
});