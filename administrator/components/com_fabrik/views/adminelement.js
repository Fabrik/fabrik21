//this array contains all the javascript element plugin objects 
var pluginControllers = new Array();

var fabrikAdminElement = new Class({
	initialize : function(plugins, options, lang) {
		this.options = {};
		$extend(this.options, options);

		this.translate = {
			'del' : 'Delete',
			'jsaction' : 'Action',
			'code' : 'Code',
			'action' : 'Validation rule',
			'please_select' : 'Please select',
			'options' : 'Options',
			'or' : 'or',
			'wherethis' : ' when this ',
			'do' : '- - do - -',
			'on' : '- - that - -',
			'is' : '- - is - -'
		};
		$extend(this.translate, lang);
		this.deleteValidationClick = this.deleteValidation.bindAsEventListener(this);
		this.plugins = plugins;
		this.watchPluginDd();
		this.validationCounter = 0;
		this.setParentViz();
		this.watchAddValidation();
		this.watchDeleteValidation();

		this.jsCounter = 0;
		this.jsactions = $A([ 'focus', 'blur', 'abort', 'click', 'change', 'dblclick', 'keydown', 'keypress', 'keyup', 'mouseup', 'mousedown', 'mouseover',
				'select', 'load', 'unload' ]);
		this.eEvents = $A([ 'hide', 'show', 'fadeout', 'fadein', 'slide in', 'slide out', 'slide toggle' ]);
		this.eTrigger = this.options.elements;
		this.eConditions = $A([ '<', '<=', '==', '>=', '>', '!=' ]);
		$('addJavascript').addEvent('click', function(e) {
			var event = new Event(e).stop();
			this.addJavascript();
		}.bind(this));

		this.options.jsevents.each(function(opt) {
			this.addJavascript(opt);
		}.bind(this));
	},

	deleteJS : function(e) {
		var event = new Event(e).stop();
		$(event.target).up(3).remove();
	},

	addJavascript : function(opt) {
		if ($type(opt) !== 'object') {
			opt = {
				code : '',
				action : '',
				js_e_event : '',
				js_e_trigger : '',
				js_e_condition : '',
				js_e_value : ''
			};
		}
		// /$$$rob do we need this!? should it not be done elsewhere - HUGH!!! :D
		// $$$ hugh - you're asking ME??? Talk to those nice Joomla! devs who strip
		// linebreaks out of text params!
		// var rExp = /;/gi;
		// var newString = new String (";\n")
		// opt.code = opt.code.replace(rExp, newString);
		// $$$rob - its not a textparam - its stored in jos_fabrik_jsactions
		code = new Element('textarea', {
			'rows' : 8,
			'cols' : 40,
			'name' : 'js_code[]',
			'class' : 'inputbox'
		}).appendText(opt.code);
		action = this._makeSel(this.jsCounter, 'js_action[]', this.jsactions, opt.action);
		var evs = this._makeSel(this.jsCounter, 'js_e_event[]', this.eEvents, opt.js_e_event, this.translate['do']);
		var triggers = this._makeSel(this.jsCounter, 'js_e_trigger[]', this.eTrigger, opt.js_e_trigger, this.translate.on);
		var condition = this._makeSel(this.jsCounter, 'js_e_condition[]', this.eConditions, opt.js_e_condition, this.translate.is);

		var content = new Element('table', {
			'class' : 'paramlist admintable adminform',
			'id' : 'jsAction_' + this.jsCounter
		}).adopt(new Element('tbody', {
			'class' : 'adminform',
			'id' : 'jsAction_' + this.jsCounter
		}).adopt([ new Element('tr').adopt(new Element('td', {
			'colspan' : 2
		})), new Element('tr').adopt([ new Element('td', {
			'class' : 'paramlist_key'
		}).appendText(this.translate.jsaction), new Element('td').adopt(action) ]), new Element('tr').adopt([ new Element('td', {
			'class' : 'paramlist_key'
		}).appendText(this.translate.code), new Element('td').adopt(code) ]), new Element('tr').adopt(new Element('td', {
			'colspan' : 2,
			'class' : 'paramlist_key',
			'styles' : {
				'text-align' : 'left'
			}
		}).appendText(this.translate.or)), new Element('tr').adopt(new Element('td', {
			'colspan' : 2
		}).adopt([ evs, triggers, new Element('span').appendText(this.translate.wherethis), condition, new Element('input', {
			'name' : 'js_e_value[]',
			'class' : 'inputbox',
			'value' : opt.js_e_value
		}) ])), new Element('tr').adopt(new Element('td', {
			'colspan' : 2
		}).adopt(new Element('a', {
			'href' : '#',
			'class' : 'removeButton',
			'events' : {
				'click' : function(e) {
					this.deleteJS(e);
				}.bind(this)
			}
		}).appendText(this.translate.del))) ]));
		var div = new Element('div');
		content.injectInside(div);
		div.injectInside($('javascriptActions'));
		this.jsCounter++;

	},

	watchAddValidation : function() {
		$('addValidation').addEvent('click', function(e) {
			new Event(e).stop();
			this.addValidation('', '');
		}.bind(this));
	},

	addValidation : function(pluginHTML, plugin) {
		var td = new Element('td', {
			'colspan' : '2'
		});
		td.innerHTML = pluginHTML;
		var td = new Element('td', {
			'colspan' : '2'
		});
		var str = '';
		this.plugins.each(function(aPlugin) {
			if (aPlugin.value == plugin) {
				str += pluginHTML;
			} else {
				str += aPlugin.html;
			}
		}.bind(this));
		// update the default template so that radio buttons get the correct
		// validation count in their names
		str = str.replace(/\[0\]/g, '[' + this.validationCounter + ']');
		td.innerHTML = str;
		var display = 'block';

		var c = new Element('div', {
			'class' : 'validationContainer'
		}).adopt(new Element('table', {
			'class' : 'paramlist adminform admintable tvcont',
			'id' : 'validationAction_' + this.validationCounter,
			'styles' : {
				'display' : display
			}
		}).adopt(new Element('tbody').adopt([ new Element('tr').adopt([ new Element('td', {
			'class' : 'paramlist_key'
		}).appendText(this.translate.action), new Element('td', {
			'class' : 'paramlist_value'
		}).adopt(this._makeSel('inputbox elementtype', 'params[validation-plugin][]', this.plugins, plugin)) ]), new Element('tr').adopt(td),
				new Element('tr').adopt(new Element('td', {
					'colspan' : '2'
				}).adopt(new Element('a', {
					'href' : '#',
					'class' : 'delete removeButton'
				}).appendText(this.translate.del))) ])));
		c.injectInside($('elementValidations'));

		// show the active plugin
		var validationAction = $('validationAction_' + this.validationCounter);
		var activePlugin = validationAction.getElement('.page-' + plugin);
		if (activePlugin) {
			activePlugin.setStyle('display', 'block');
		}

		// watch the drop down
		validationAction.getElement('.elementtype').addEvent('change', function(e) {
			e = new Event(e);
			var id = $(e.target).up(3).id.replace('validationAction_', '');
			$$('#validationAction_' + id + ' .validationSettings').each(function(d) {
				d.style.display = 'none';
			});
			var s = e.target.get('value');
			if (s != this.translate.please_select) {
				$('validationAction_' + id).getElement(' .page-' + s).style.display = 'block';
			}
			e.stop();
		}.bind(this));
		this.watchDeleteValidation();

		// show any tips (only running code over newly added html)
		var myTips = new Tips($$('#validationAction_' + this.counter + ' .hasTip'), {});

		this.validationCounter++;

	},

	_makeSel : function(c, name, pairs, sel, sellabel) {
		sellabel = sellabel ? sellabel : this.translate.please_select;
		var opts = [];
		opts.push(new Element('option', {
			'value' : ''
		}).appendText(sellabel));
		if ($type(pairs) == 'object') {
			pairs = $H(pairs);
			pairs.each(function(val, key) {
				opts.push(new Element('optgroup', {
					'label' : key
				}));
				opts = this._makeOpts(val, sel, opts);
			}.bind(this));
		} else {
			opts = this._makeOpts(pairs, sel, opts);
		}

		return new Element('select', {
			'class' : c,
			'name' : name
		}).adopt(opts);
	},

	_makeOpts : function(pairs, sel, opts) {
		pairs.each(function(pair) {
			if ($type(pair) == 'string') {
				pair = {
					'value' : pair,
					'label' : pair
				};
			}
			if (pair.value == sel) {
				opts.push(new Element('option', {
					'value' : pair.value,
					'selected' : 'selected'
				}).appendText(pair.label));
			} else {
				opts.push(new Element('option', {
					'value' : pair.value
				}).appendText(pair.label));
			}
		});
		return opts;
	},

	watchDeleteValidation : function() {
		$('elementValidations').getElements('.delete').each(function(c) {
			c.removeEvents('click');
			c.addEvent('click', this.deleteValidationClick);
		}.bind(this));
	},

	deleteValidation : function(e) {
		e = new Event(e);
		e.stop();
		$(e.target).findClassUp('validationContainer').remove();
		// reorder validation ids in table
		$('elementValidations').getElements('table.tvcont').each(function(t, c) {
			t.id = 'validationAction_' + c;
		});
		this.validationCounter--;
	},

	watchPluginDd : function() {
		$('detailsplugin').addEvent('change', function(e) {
			var event = new Event(e);
			var sel = event.target;
			var opt = sel.get('value');
			$$('.elementSettings').each(function(tab) {
				if (opt == tab.id.replace('page-', '')) {
					tab.setStyles({
						display : 'block'
					});
				} else {
					tab.setStyles({
						display : 'none'
					});
				}
			});
		});
		if ($('page-' + this.options.plugin)) {
			$('page-' + this.options.plugin).setStyles({
				display : 'block'
			});
		}
		;
	},

	setParentViz : function() {
		if (this.options.parentid != 0) {
			myFX = new Fx.Style('elementFormTable', 'opacity', {
				duration : 500,
				wait : false
			});
			myFX.set(0);
			$('unlink').addEvent('click', function(e) {
				var s = (this.checked) ? "" : "readonly";
				if (this.checked) {
					myFX.start(0, 1);
				} else {
					myFX.start(1, 0);
				}
			});
		}
		if ($('swapToParent')) {
			$('swapToParent').addEvent('click', function(e) {
				e = new Event(e);
				var el = $(e.target);
				var f = document.adminForm;
				f.task.value = 'parentredirect';
				var to = el.className.replace('element_', '');
				;
				f.redirectto.value = to;
				f.submit();
			});
		}
	}
});

function setAllCheckBoxes(elName, val) {
	var els = document.getElementsByName(elName);
	var c = els.length;
	for (i = 0; i < c; i++) {
		els[i].checked = val;
	}
}

function setAllDropDowns(elName, selIndex) {
	els = document.getElementsByName(elName);
	c = els.length;
	for (i = 0; i < c; i++) {
		els[i].selectedIndex = selIndex;
	}
}

function setAll(t, elName) {
	els = document.getElementsByName(elName);
	c = els.length;
	for (i = 0; i < c; i++) {
		els[i].value = t;
	}
}

function deleteSubElements(sTagId) {
	var oNode = $(sTagId);
	oNode.parentNode.removeChild(oNode);
}
