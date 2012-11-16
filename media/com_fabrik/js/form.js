/**
 * @author Robert
 */

var fabrikForm = new Class({

	initialize : function(id, options, lang) {
		this.id = id;
		this.options = {
			'admin' : false,
			'postMethod' : 'post',
			'primaryKey' : null,
			'error' : '',
			'delayedEvents' : false,
			'liveSite' : '',
			'pages' : [],
			'start_page' : 0,
			'ajaxValidation' : false,
			'plugins' : [],
			'ajaxmethod' : 'post',
			'mooversion' : 1.1,
			'lang' : ''
		};
		this.addedGroups= [];
		$extend(this.options, options);
		this.options.pages = $H(this.options.pages);
		this.subGroups = $H({});
		this.lang = {
			'validation_altered_content' : 'The validation has altered your content:',
			'validating' : 'Validating',
			'success' : 'Success',
			'nodata' : 'No data',
			'confirmDelete' : 'Are you sure you want to delete these records?',
			validation_error : 'Validation error',
			form_saved : 'Form saved'
		};

		$extend(this.lang, lang);
		this.currentPage = this.options.start_page;
		this.changePageDir = 0;
		this.formElements = $H({});
		this.delGroupJS = $H({});
		this.duplicateGroupJS = $H({});
		this.listenTo = $A([]);
		this.bufferedEvents = $A([]);
		this.duplicatedGroups = $H();
		this.clickDeleteGroup = this.deleteGroup.bindAsEventListener(this);
		this.clickDuplicateGroup = this.duplicateGroup.bindAsEventListener(this);

		this.fx = {};
		this.fx.elements = [];
		this.fx.validations = {};
		window.addEvent('domready', this.setUpAll.bindAsEventListener(this));
	},

	setUpAll : function() {
		this.setUp();
		this.form.addEvent('reset', this.reset.bindAsEventListener(this));
		this.winScroller = new Fx.Scroll(window, {
			duration : 1000
		});
		console.log(this.options.hiddenGroup);
		$H(this.options.hiddenGroup).each(function(v, k) {
			if (v == true && $type($('group' + k)) !== false) {
				var subGroup = $('group' + k).getElement('.fabrikSubGroup');
				this.subGroups.set(k, subGroup.cloneWithIds());
				this.hideLastGroup(k, subGroup);
			}
		}.bind(this));

		// get an int from which to start incrementing for each repeated group id
		// dont ever decrease this value when deleteing a group as it will cause all
		// sorts of
		// reference chaos with cascading dropdowns etc
		this.repeatGroupMarkers = $H({});
		this.form.getElements('.fabrikGroup').each(function(group) {
			var id = group.id.replace('group', '');
			var c = group.getElements('.fabrikSubGroup').length;
			//if no joined repeating data then c should be 0 and not 1
			if (c === 1) {
				if (group.getElement('.fabrikSubGroupElements').getStyle('display') === 'none') {
					c = 0;
				}
			}
			this.repeatGroupMarkers.set(id, c);
		}.bind(this));

		// $$$ hugh - hacky experiment for "show repeat max", so we just show the configured
		// max repeats, with no +/- buttons
		$H(this.options.showMaxRepeats).each(function (v, k) {
			if (v == true && $type($('group' + k)) !== false) {
				if (this.options.maxRepeat[k] > 0) {
					var group_count = $('group' + k).getElements('.fabrikSubGroup').length;
					if (this.options.hiddenGroup[k]) {
						group_count--
					}
					var add_count = this.options.maxRepeat[k] - group_count;
					var add_btn = $('group' + k).getElement('.addGroup');
					for (i=0; i < add_count; i++) {
						add_btn.fireEvent('click', new Event.Mock(add_btn, 'click'));
					}
				}
			}
		}.bind(this));
		
		// testing prev/next buttons
		var v = this.options.editable === true ? 'form' : 'details';
		var row = this.form.getElement('input[name=rowid]');
		var rowid = $type(row) === false ? 0 : row.value;
		var editopts = {
			option : 'com_fabrik',
			'view' : v,
			'controller' : 'form',
			'task' : 'getNextRecord',
			'fabrik' : this.id,
			'rowid' : rowid,
			'format' : 'raw',
			'task' : 'paginate',
			'dir' : 1
		};
		[ '.previous-record', '.next-record' ].each(function(b, dir) {
			editopts.dir = dir;
			if (this.form.getElement(b)) {

				var myAjax = new Ajax('index.php', {
					method : this.options.ajaxmethod,
					data : editopts,
					onComplete : function(r) {
						oPackage.stopLoading(this.getBlock());
						r = Json.evaluate(r.stripScripts());
						this.update(r);
						this.form.getElement('input[name=rowid]').value = r.post.rowid;
					}.bind(this)
				});

				this.form.getElement(b).addEvent('click', function(e) {
					myAjax.options.data.rowid = this.form.getElement('input[name=rowid]').value;
					new Event(e).stop();
					oPackage.startLoading(this.getBlock(), 'loading');
					myAjax.request();
				}.bind(this));
			}
		}.bind(this));
	},

	setUp : function() {
		this.form = this.getForm();
		this.watchGroupButtons();
		if (this.options.editable) {
			this.watchSubmit();
		}
		this.createPages();
		this.watchClearSession();
	},

	getForm : function() {
		this.form = $(this.getBlock());
		return this.form;
	},

	getBlock : function() {
		return this.options.editable == true ? 'form_' + this.id : 'details_' + this.id;
	},

	// id is the element or group to apply the fx TO, triggered from another
	// element
	addElementFX : function(id, method) {
		id = id.replace('fabrik_trigger_', '');
		if (id.slice(0, 6) == 'group_') {
			id = id.slice(6, id.length);
			var k = id;
			var c = $(id);
		} else {
			id = id.slice(8, id.length);
			k = 'element' + id;
			if (!$(id)) {
				return;
			}
			c = $(id).findClassUp('fabrikElementContainer');
		}
		if (c) {
			// c will be the <li> element - you can't apply fx's to this as it makes
			// the
			// DOM squiffy with
			// multi column rows, so get the li's content and put it inside a div
			// which
			// is injected into c
			// apply fx to div rather than li - damn im good
			if ((c).getTag() == 'li') {
				var fxdiv = new Element('div').adopt(c.getChildren());
				c.empty();
				fxdiv.injectInside(c);
			} else {
				fxdiv = c;
			}

			var opts = {
				duration : 800,
				transition : Fx.Transitions.Sine.easeInOut
			};
			this.fx.elements[k] = {};
			this.fx.elements[k].css = fxdiv.effect('opacity', opts);
			if ($type(fxdiv) != false && (method == 'slide in' || method == 'slide out' || method == 'slide toggle')) {
				this.fx.elements[k]['slide'] = new Fx.Slide(fxdiv, opts);
			} else {
				this.fx.elements[k]['slide'] = null;
			}
		}
	},

	doElementFX : function(id, method) {
		id = id.replace('fabrik_trigger_', '');
		if (id.slice(0, 6) == 'group_') {
			id = id.slice(6, id.length);
			// wierd fix?
			if (id.slice(0, 6) == 'group_')
				id = id.slice(6, id.length);
			var k = id;
			var groupfx = true;
		} else {
			groupfx = false;
			id = id.slice(8, id.length);
			k = 'element' + id;
		}
		var fx = this.fx.elements[k];
		if (!fx) {
			return;
		}
		var fxElement = groupfx ? fx.css.element : fx.css.element.findClassUp('fabrikElementContainer');
		switch (method) {
			case 'show':
				fxElement.removeClass('fabrikHide');
				fx.css.set(1);
				fx.css.element.show();
				if (groupfx) {
					// strange fix for ie8
					// http://fabrik.unfuddle.com/projects/17220/tickets/by_number/703?cycle=true
					$(id).getElements('.fabrikinput').setStyle('opacity', '1');
				}
				break;
			case 'hide':
				fxElement.addClass('fabrikHide');
				fx.css.set(0);
				fx.css.element.hide();
				break;
			case 'fadein':
				fxElement.removeClass('fabrikHide');
				if (fx.css.lastMethod !== 'fadein') {
					fx.css.element.show();
					fx.css.start(0, 1);
				}
				break;
			case 'fadeout':
				if (fx.css.lastMethod !== 'fadeout') {
					fx.css.start(1, 0).chain(function() {
						fx.css.element.hide();
						fxElement.addClass('fabrikHide');
					});
				}
				break;
			case 'slide in':
				fx.slide.slideIn();
				break;
			case 'slide out':
				fx.slide.slideOut();
				fxElement.removeClass('fabrikHide');
				break;
			case 'slide toggle':
				fx.slide.toggle();
				// fxElement.toggleClass('fabrikHide');
				break;
		}
		fx.lastMethod = method;
		this.runPlugins('onDoElementFX', null);
	},

	watchClearSession : function() {
		if (this.form && this.form.getElement('.clearSession')) {
			this.form.getElement('.clearSession').addEvent('click', function(e) {
				new Event(e).stop();
				this.form.getElement('input[name=task]').value = 'removeSession';
				this.clearForm();
				this.form.submit();
			}.bind(this));
		}
	},

	createPages : function() {
		if (this.options.pages.keys().length > 1) {
			// wrap each page in its own div
			this.options.pages.each(function(page, i) {
				var p = new Element('div', {
					'class' : 'page',
					'id' : 'page_' + i
				});
				p.injectBefore($('group' + page[0]));
				page.each(function(group) {
					p.adopt($('group' + group));
				});
			});
			if ($('fabrikSubmit' + this.id) && this.options.rowid == '') {
				$('fabrikSubmit' + this.id).disabled = "disabled";
				$('fabrikSubmit' + this.id).setStyle('opacity', 0.5);
			}
			this.form.getElement('.fabrikPagePrevious').disabled = "disabled";
			this.form.getElement('.fabrikPageNext').addEvent('click', this._doPageNav.bindAsEventListener(this, [ 1 ]));
			this.form.getElement('.fabrikPagePrevious').addEvent('click', this._doPageNav.bindAsEventListener(this, [ -1 ]));
			this.setPageButtons();
			this.hideOtherPages();
		}
	},

	_doPageNav : function(e, dir) {
		if (this.options.editable) {
			this.form.getElement('.fabrikMainError').addClass('fabrikHide');
			// if tip shown at bottom of long page and next page shorter we need to move
			// the tip to
			// the top of the page to avoid large space appearing at the bottom of the
			// page.
			if ($type(document.getElement('.tool-tip')) !== false) {
				document.getElement('.tool-tip').setStyle('top', 0);
			}
			var url = this.options.liveSite + 'index.php?option=com_fabrik&controller=form&format=raw&task=ajax_te&form_id=' + this.id;
			url += '&lang='+this.options.lang;
			oPackage.startLoading(this.getBlock(), 'validating');
	
			// only validate the current groups elements, otherwise validations on
			// other pages cause the form to show an error.
	
			var groupId = this.options.pages.get(this.currentPage.toInt());
	
			var d = $H(this.getFormData());
			d.set('controller', 'form');
			d.set('task', 'ajax_validate');
			d.set('fabrik_postMethod', 'ajax');
			d.set('format', 'raw');
			
			d = this._prepareRepeatsForAjax(d);
	
			var myAjax = new Ajax(url, {
				method : this.options.ajaxmethod,
				data : d,
				onComplete : function(r) {
					oPackage.stopLoading(this.getBlock());
					r = Json.evaluate(r.stripScripts());
					if (this._showGroupError(r, d) == false) {
						this.changePage(dir);
						this.saveGroupsToDb();
					}
					new Fx.Scroll(window).toElement(this.form);
				}.bind(this)
			}).request();
		}
		else {
			this.changePage(dir);
		}
		var event = new Event(e).stop();
	},

	saveGroupsToDb : function() {
		if (this.options.multipage_save !== true) {
			return;
		}
		if (!this.runPlugins('saveGroupsToDb', null)) {
			return;
		}
		var orig = this.form.getElement('input[name=format]').value;
		var origprocess = this.form.getElement('input[name=task]').value;
		this.form.getElement('input[name=format]').value = 'raw';
		this.form.getElement('input[name=task]').value = 'savepage';

		var url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw&page=' + this.currentPage;
		oPackage.startLoading(this.getBlock(), 'saving page');
		var data = this.getFormData();
		new Ajax(url, {
			method : this.options.ajaxmethod,
			data : data,
			onComplete : function(r) {
				if (!this.runPlugins('onCompleteSaveGroupsToDb', null)) {
					return;
				}
				this.form.getElement('input[name=format]').value = orig;
				this.form.getElement('input[name=task]').value = origprocess;
				if (this.options.postMethod == 'ajax') {
					oPackage.sendMessage(this.getBlock(), 'updateRows', 'ok', json);
				}
				oPackage.stopLoading(this.getBlock());
			}.bind(this)
		}).request();
	},

	changePage : function(dir) {
		this.changePageDir = dir;
		if (!this.runPlugins('onChangePage', null)) {
			return;
		}
		this.currentPage = this.currentPage.toInt();
		// hide all error messages ($$$ rob why would we want to do that? -
		// commneting out)
		// this.form.getElements('.fabrikError').addClass('fabrikHide');
		if (this.currentPage + dir >= 0 && this.currentPage + dir < this.options.pages.keys().length) {
			this.currentPage = this.currentPage + dir;
			if (!this.pageGroupsVisible()) {
				this.changePage(dir);
			}
		}

		this.setPageButtons();
		$('page_' + this.currentPage).setStyle('display', '');
		this.hideOtherPages();
		if (!this.runPlugins('onEndChangePage', null)) {
			return;
		}
	},

	pageGroupsVisible : function() {
		var visible = false;
		this.options.pages.get(this.currentPage).each(function(gid) {
			if ($('group' + gid).getStyle('display') != 'none') {
				visible = true;
			}
		});
		return visible;
	},

	/**
	 * hide all groups except those in the active page
	 */
	hideOtherPages : function() {
		this.options.pages.each(function(gids, i) {
			if (i != this.currentPage) {
				$('page_' + i).setStyle('display', 'none');
			}
		}.bind(this));
	},

	setPageButtons : function() {
		if (this.currentPage == this.options.pages.keys().length - 1) {
			if ($('fabrikSubmit' + this.id)) {
				$('fabrikSubmit' + this.id).disabled = "";
				$('fabrikSubmit' + this.id).setStyle('opacity', 1);
			}
			this.form.getElement('.fabrikPageNext').disabled = "disabled";
			this.form.getElement('.fabrikPageNext').setStyle('opacity', 0.5);
		} else {
			if ($('fabrikSubmit' + this.id) && this.options.rowid == '') {
				$('fabrikSubmit' + this.id).disabled = "disabled";
				$('fabrikSubmit' + this.id).setStyle('opacity', 0.5);
			}
			this.form.getElement('.fabrikPageNext').disabled = "";
			this.form.getElement('.fabrikPageNext').setStyle('opacity', 1);
		}
		if (this.currentPage === 0) {
			this.form.getElement('.fabrikPagePrevious').disabled = "disabled";
			this.form.getElement('.fabrikPagePrevious').setStyle('opacity', 0.5);
		} else {
			this.form.getElement('.fabrikPagePrevious').disabled = "";
			this.form.getElement('.fabrikPagePrevious').setStyle('opacity', 1);
		}
	},

	addElements : function(a) {
		a = $H(a);
		a.each(function(elements, gid) {
			elements.each(function(el) {
				if ($type(el) !== false) {
					this.addElement(el, el.options.element, gid);
				}
			}.bind(this));
		}.bind(this));
		// $$$ hugh - moved attachedToForm calls out of addElement to separate loop, to fix forward reference issue,
		// i.e. calc element adding events to other elements which come after itself, which won't be in formElements
		// yet if we do it in the previous loop ('cos the previous loop is where elements get added to formElements)
		a.each(function(elements) {
			elements.each(function(el) {
				if ($type(el) !== false) {
					try {
						el.attachedToForm();
					} catch (err) {
						fconsole(el.options.element + ' attach to form:' + err );
					}
				}
			}.bind(this));
		}.bind(this));
		window.fireEvent('fabrik.form.elements.added');
	},

	addElement : function(oEl, elId, gid) {
		elId = elId.replace('[]', '');
		var ro = elId.substring(elId.length - 3, elId.length) === '_ro';
		oEl.form = this;
		oEl.groupid = gid;
		this.formElements.set(elId, oEl);
		if (ro) {
			elId = elId.substr(0, elId.length - 3);
			this.formElements.set(elId, oEl);
		}
	},

	// we have to buffer the events in a pop up window as
	// the dom inserted when the window loads appears after the ajax evalscripts

	dispatchEvent : function(elementType, elementId, action, js) {
		if (!this.options.delayedEvents) {
			var el = this.formElements.get(elementId);
			if (el && js != '') {
				el.addNewEvent(action, js);
			}
		} else {
			this.bufferEvent(elementType, elementId, action, js);
		}
	},

	bufferEvent : function(elementType, elementId, action, js) {
		this.bufferedEvents.push([ elementType, elementId, action, js ]);
	},

	// call this after the popup window has loaded
	processBufferEvents : function() {
		this.setUp();
		this.options.delayedEvents = false;
		this.bufferedEvents.each(function(r) {
			// refresh the element ref
			var elementId = r[1];
			var el = this.formElements.get(elementId);
			el.element = $(elementId);
			this.dispatchEvent(r[0], elementId, r[2], r[3]);
		}.bind(this));
	},

	action : function(task, element) {
		var oEl = this.formElements.get(el);
		eval('oEl.' + task + '()');
	},

	triggerEvents : function(el) {
		this.formElements.get(el).fireEvents(arguments[1]);
	},

	/**
	 * @param string
	 *          element id to observe
	 * @param string
	 *          error div for element
	 * @param string
	 *          parent element id - eg for datetime's time field this is the date
	 *          fields id
	 */
	watchValidation : function(id, triggerEvent) {
		if (this.options.ajaxValidation == false) {
			return;
		}
		if ($(id).className == 'fabrikSubElementContainer') {
			// check for things like radio buttons & checkboxes

			$(id).getElements('.fabrikinput').each(function(i) {
				i.addEvent(triggerEvent, this.doElementValidation.bindAsEventListener(this, [ true ]));
			}.bind(this));
			return;
		}
		$(id).addEvent(triggerEvent, this.doElementValidation.bindAsEventListener(this, [ false ]));
	},

	// as well as being called from watchValidation can be called from other
	// element js actions, e.g. date picker closing
	doElementValidation : function(event, subEl, replacetxt) {
		if (this.options.ajaxValidation == false) {
			return;
		}
		replacetxt = $type(replacetxt) == false ? '_time' : replacetxt;
		if ($type(event) == 'event' || $type(event) == 'object') { // type object
																																// in
			var e = new Event(event);
			var id = e.target.id;
			// for elements with subelements eg checkboxes radiobuttons
			if (subEl == true) {
				id = $(e.target).findClassUp('fabrikSubElementContainer').id;
			}
		} else {
			// hack for closing date picker where it seems the event object isnt
			// available
			id = event;
		}
		// for elements with subelements eg checkboxes radiobuttons
		/*
		 * if (subEl == true) { id =
		 * $(e.target).findClassUp('fabrikSubElementContainer').id; }
		 */
		if ($type($(id)) === false) {
			return;
		}
		if ($(id).getProperty('readonly') === true || $(id).getProperty('readonly') == 'readonly') {
			// stops date element being validated
			// return;
		}
		var el = this.formElements.get(id);
		if (!el) {
			// silly catch for date elements you cant do the usual method of setting
			// the id in the
			// fabrikSubElementContainer as its required to be on the date element for
			// the calendar to work
			id = id.replace(replacetxt, '');
			el = this.formElements.get(id);
			if (!el) {
				return;
			}
		}
		
		// $$$ hugh - added mainly for CDD elements, which don't need
		// to run AJAX validation when change is fired because of CDD
		// being reset on update of watched element.
		if (el.skipValidation()) {
			return;
		}

		if (!this.runPlugins('onStartElementValidation', event)) {
			return;
		}
		el.setErrorMessage(this.lang.validating, 'fabrikValidating');

		var d = $H(this.getFormData());
		d.set('controller', 'form');
		d.set('task', 'ajax_validate');
		d.set('fabrik_postMethod', 'ajax');
		d.set('format', 'raw');

		d = this._prepareRepeatsForAjax(d);

		var origid = el.origId ? el.origId : id;
		el.options.repeatCounter = el.options.repeatCounter ? el.options.repeatCounter : 0;
		var url = this.options.liveSite + 'index.php?option=com_fabrik&form_id=' + this.id;
		var myAjax = new Ajax(url, {
			method : this.options.ajaxmethod,
			data : d,
			onComplete : this._completeValidaton.bindAsEventListener(this, [ id, origid ])
		}).request();
	},

	_completeValidaton : function(r, id, origid) {
		r = Json.evaluate(r.stripScripts());
		if (!this.runPlugins('onCompleteElementValidation', null)) {
			return;
		}
		var el = this.formElements.get(id);
		if ($defined(r.modified[origid])) {
			el.update(r.modified[origid]);
		}
		if ($type(r.errors[origid]) !== false) {
			this._showElementError(r.errors[origid][el.options.repeatCounter], id);
		} else {
			this._showElementError([], id);
		}
	},

	_prepareRepeatsForAjax : function(d) {
		this.getForm();
		// ensure we are dealing with a simple object
		if ($type(d) === 'hash' || ($type(d.obj) === 'object' && this.options.mooversion == 1.1)) {
			d = (this.options.mooversion == 1.1) ? d.obj : d.getClean();
		}
		// data should be key'd on the data stored in the elements name between []'s
		// which is the group id
		this.form.getElements('input[name^=fabrik_repeat_group]').each(function(e) {
			var c = e.name.match(/\[(.*)\]/)[1];
			d['fabrik_repeat_group[' + c + ']'] = e.get('value'); // good for mootools
																														// 1.1
		});
		return d;
	},

	_showGroupError : function(r, d) {
		var gids = $A(this.options.pages.get(this.currentPage.toInt()));
		var err = false;
		$H(d).each(function(v, k) {
			k = k.replace(/\[(.*)\]/, '');// for dropdown validations
			if (this.formElements.hasKey(k)) {
				var el = this.formElements.get(k);
				if (gids.contains(el.groupid.toInt())) {
					if (r.errors[k]) {
						// prepare error so that it only triggers for real errors and not
						// sucess
						// msgs

						var msg = '';
						if ($type(r.errors[k]) !== false) {
							for ( var i = 0; i < r.errors[k].length; i++) {
								if (r.errors[k][i] != '') {
									msg += r[i] + '<br />';
								}
							}
						}
						if (msg !== '') {
							tmperr = this._showElementError(r.errors[k], k);
							if (err == false) {
								err = tmperr;
							}
						}else{
							el.setErrorMessage('', '');
						}
					}
					if (r.modified[k]) {
						if (el) {
							el.update(r.modified[k]);
						}
					}
				}
			}
		}.bind(this));

		return err;
	},

	_showElementError : function(r, id) {
		// r should be the errors for the specific element, down to its repeat group
		// id.
		var msg = '';
		if ($type(r) !== false) {
			for ( var i = 0; i < r.length; i++) {
				if (r[i] != '') {
					msg += r[i] + '<br />';
				}
			}
		}
		var classname = (msg === '') ? 'fabrikSuccess' : 'fabrikError';
		if (msg === '')
			msg = this.lang.success;
		this.formElements.get(id).setErrorMessage(msg, classname);
		return (classname === 'fabrikSuccess') ? false : true;
	},

	updateMainError : function() {
		var mainEr = this.form.getElement('.fabrikMainError');
		mainEr.setHTML(this.options.error);
		var activeValidations = this.form.getElements('.fabrikError').filter(function(e, index) {
			return !e.hasClass('fabrikMainError');
		});
		if (activeValidations.length > 0 && mainEr.hasClass('fabrikHide')) {
			mainEr.removeClass('fabrikHide');
			var myfx = new Fx.Style(mainEr, 'opacity', {
				duration : 500
			}).start(0, 1);
		}
		if (activeValidations.length === 0) {
			myfx = new Fx.Style(mainEr, 'opacity', {
				duration : 500,
				onComplete : function() {
					mainEr.addClass('fabrikHide');
				}
			}).start(1, 0);
		}
	},

	/*
	 * runPlugins : function(func, event) { var ret = true;
	 * this.options.plugins.each( function(plugin) { if ($type(plugin[func]) !=
	 * false) { if (plugin[func](event) == false) { ret = false; } } }); return
	 * ret; },
	 */

	watchSubmit : function() {
		if (!$('fabrikSubmit' + this.id)) {
			return;
		}
		if (this.form.getElement('input[name=delete]')) {
			this.form.getElement('input[name=delete]').addEvent('click', function(e) {
				if (confirm(this.lang.confirmDelete)) {
					this.form.getElement('input[name=task]').value = 'delete';
				} else {
					return false;
				}
			}.bind(this));
		}
		if (this.options.postMethod == 'ajax') {
			$('fabrikSubmit' + this.id).addEvent('click', this.doSubmit.bindAsEventListener(this));
		}
		this.form.addEvent('submit', this.doSubmit.bindAsEventListener(this));
	},

	doSubmit : function(e) {
		var ret = this.runPlugins('onSubmit', e);
		this.elementsBeforeSubmit(e);
		if (ret == false) {
			new Event(e).stop();
			// update global status error
			this.updateMainError();
		}
		//insert a hidden element so we can reload the last page if validation vails
		if (this.options.pages.keys().length > 1) {
			this.form.adopt(new Element('input', {'name':'currentPage','value':this.currentPage.toInt(), 'type':'hidden'}));
		}
		if (ret) {
			if (this.options.postMethod == 'ajax') {
				// do ajax val only if onSubmit val ok
				if (this.form) {
					oPackage.startLoading(this.getBlock());
					this.elementsBeforeSubmit(e);
					// get all values from the form
					var data = $H(this.getFormData());
					data = this._prepareRepeatsForAjax(data);
					data.fabrik_postMethod = 'ajax';
					data.format = 'raw';
					var myajax = new Ajax(this.form.action, {
						'data' : data,
						'method' : this.options.ajaxmethod,
						onComplete : function(json) {
							json = Json.evaluate(json.stripScripts());
	
							if ($type(json) === false) {
								// stop spinner
								fconsole('error in returned json', json);
								oPackage.stopLoading(this.getBlock());
								return;
							}
							// process errors if there are some
							var errfound = false;
							if ($defined(json.errors)) {
								// for every element of the form update error message
								$H(json.errors).each(function(errors, key) {
									// replace join[id][label] with join___id___label
									key = key.replace(/(\[)|(\]\[)/g, '___').replace(/\]/, '');
									if (this.formElements.hasKey(key) && errors.flatten().length > 0) {
										errfound = true;
										this._showElementError(errors, key);
									}
									;
								}.bind(this));
								window.fireEvent('fabrik.form.ajax_submit.failed');
								// this.runPlugins('onAjaxSubmitComplete'); don't run it I guess
							}
							// update global status error
							this.updateMainError();
	
							if (errfound === false) {
								// $$$ rob clearForm() was commented out but in module with ajax
								// on this gave appearance that
								// form was not submitted
								// process thanks msg (from redirect plugin) if exists
								var saved_msg = this.lang.form_saved;
								if ($defined(json.thanks)) {
									// $$$ hugh - haven't tested this, but I'm pretty sure we now return
									// thanks message as an array
									if (typeof(json.thanks.message) == 'object') {
										saved_msg = json.thanks.message.join('\r\n');
									}
									else {
										saved_msg = json.thanks.message;
									}
								}
								else if ($defined(json.msg)) {
									saved_msg = json.msg;
								}
								alert(saved_msg);
								this.clearForm();
								oPackage.sendMessage(this.getBlock(), 'updateRows', 'ok', json, saved_msg);
								this.runPlugins('onAjaxSubmitComplete', e);
							} else {
								// stop spinner
								oPackage.stopLoading(this.getBlock(), this.lang.validation_error);
							}
						}.bind(this)
					}).request();
				}
				new Event(e).stop();
			}
			else {
				var end_ret = this.runPlugins('onSubmitEnd', e);
				if (end_ret == false) {
					new Event(e).stop();
					// update global status error
					this.updateMainError();
				}
			}
		}
	},

	elementsBeforeSubmit : function(e) {
		e = new Event(e);
		this.formElements.each(function(el, key) {
			if (!el.onsubmit()) {
				e.stop();
			}
		});
	},

	// used to get the querystring data and
	// for any element overwrite with its own data definition
	// required for empty select lists which return undefined as their value if no
	// items
	// available

	getFormData : function() {
		this.getForm();
		var s = this.form.toQueryString();
		var h = {};
		s = s.split('&');
		var arrayCounters = $H({});
		s.each(function(p) {
			p = p.split('=');
			var k = p[0];
			// $$$ rob deal with checkboxes
			if (k.substring(k.length - 2) === '[]') {
				k = k.substring(0, k.length - 2);
				if (!arrayCounters.hasKey(k)) {
					// rob for ajax validation on repeat element this is required to be
					// set to 0
					// arrayCounters.set(k, 1);
					arrayCounters.set(k, 0);
				} else {
					arrayCounters.set(k, arrayCounters.get(k) + 1);
				}
				k = k + '[' + arrayCounters.get(k) + ']';
			}
			h[k] = p[1];
		});

		// toQueryString() doesn't add in empty data - we need to know that for the
		// validation on multipages
		var elKeys = this.formElements.keys();
		this.formElements.each(function(el, key) {
			//fileupload data not included in querystring
			if (el.plugin == 'fabrikfileupload') {
				h[key] = el.getValue();
			}
			if ($type(h[key]) === false) {
				// search for elementname[*] in existing data (search for * as datetime
				// elements aren't keyed numerically)
				var found = false;
				$H(h).each(function(val, dataKey) {
					dataKey = dataKey.replace(/\[(.*)\]/, '');
					if (dataKey == key) {
						found = true;
					}
				}.bind(this));
				if (!found) {
					h[key] = '';
				}
			}
		}.bind(this));
		return h;
	},

	// $$$ hugh - added this, so far only used by cascading dropdown JS
	// to populate 'data' for the AJAX update, so custom cascade 'where' clauses
	// can use {placeholders}. Initially tried to use getFormData for this, but
	// because
	// it adds ALL the query string args from the page, the AJAX call from cascade
	// ended
	// up trying to submit the form. So, this func does what the commented out
	// code in
	// getFormData used to do, and only fecthes actual form element data.

	getFormElementData : function(this_el) {
		var h = {};
		this.wtf_this_el = this_el;
		this.formElements.each(function(el, key) {
			var rawkey = key + '_raw';
			if (this.wtf_this_el && el.options.isGroupJoin && el.options.canRepeat) {
				rawkey = rawkey.replace(/_(\d+)$/, "_raw_$1");
				var joinid = key.match(/^join___(\d+)___/)[1];
				var gcount = key.match(/_(\d+)$/)[1];
				if (this.wtf_this_el.element.id.test(/_(\d+)$/)) {
					var this_el_gcount = this.wtf_this_el.element.id.match(/_(\d+)$/)[1];
					if (joinid == this.wtf_this_el.options.joinid && gcount == this_el_gcount) {
						//var repeat = key.match(/_(\d+)$/)[1];
						var cheat_key = key.replace(/^(join___\d+___)/, "");
						cheat_key = cheat_key.replace(/(_\d+$)/, "");
						h[cheat_key] = el.getValue();
						h[cheat_key + '_raw'] = h[cheat_key];
					}
				}
			}
			if (el.element) {
				h[key] = el.getValue();
				h[rawkey] = h[key];
			}
		}.bind(this));
		return h;
	},

	watchGroupButtons : function() {
		this.unwatchGroupButtons();
		this.form.getElements('.deleteGroup').each(function(g, i) {
			g.addEvent('click', this.clickDeleteGroup);
		}.bind(this));
		this.form.getElements('.addGroup').each(function(g, i) {
			g.addEvent('click', this.clickDuplicateGroup);
		}.bind(this));
		this.form.getElements('.fabrikSubGroup').each(function(subGroup) {
			var r = subGroup.getElement('.fabrikGroupRepeater');
			if (r) {
				subGroup.removeEvents();
				r.addEvent('mouseenter', function(e) {
					r.effect('opacity', {
						wait : false,
						duration : 200
					}).start(0.2, 1);
				});
				r.addEvent('mouseleave', function(e) {
					r.effect('opacity', {
						wait : false,
						duration : 200
					}).start(1, 0.2);
				});
			}
		});
	},

	unwatchGroupButtons : function() {
		this.form.getElements('.deleteGroup').each(function(g, i) {
			g.removeEvent('click', this.clickDeleteGroup);
		}.bind(this));
		this.form.getElements('.addGroup').each(function(g, i) {
			g.removeEvent('click', this.clickDuplicateGroup);
		}.bind(this));
		this.form.getElements('.fabrikSubGroup').each(function(subGroup) {
			subGroup.removeEvents('mouseenter');
			subGroup.removeEvents('mouseleave');
		});
	},

	addGroupJS : function(groupId, e, js) {
		if (e == 'delete') {
			this.delGroupJS.set(groupId, js);
		} else {
			this.duplicateGroupJS.set(groupId, js);
		}
	},

	deleteGroup: function(event) {
		if (!this.runPlugins('onDeleteGroup', event)) {
			return;
		}
		var e = new Event(event).stop();
		var group = $(e.target).findClassUp('fabrikGroup');
		// find which repeat group was deleted
		var delIndex = 0;
		group.getElements('.deleteGroup').each(function(b, x) {
			if (b.getElement('img') === $(e.target)) {
				delIndex = x;
			}
		}.bind(this));
		var i = group.id.replace('group', '');
		this.duplicatedGroups.remove(i);
		if ($('fabrik_repeat_group_' + i + '_counter').value == '0') {
			return;
		}
		var subgroups = group.getElements('.fabrikSubGroup');

		var subGroup = $(e.target).findClassUp('fabrikSubGroup');
		this.subGroups.set(i, subGroup.clone());
		if (subgroups.length <= 1) {
			this.hideLastGroup(i, subGroup);

		} else {
			var toel = subGroup.getPrevious();
			var js = this.delGroupJS.get(i);
			//stop double clicking when in fade fx - http://fabrikar.com/forums/showthread.php?t=20646
			this.unwatchGroupButtons();
			var myFx = new Fx.Style(subGroup, 'opacity', {
				duration : 300,
				onComplete : function() {
					if (subgroups.length > 1) {
						subGroup.remove();
					}

					this.formElements.each(function(e, k) {
						if ($type(e.element) !== false) {
							if ($type($(e.element.id)) == false) {
								e.decloned(i);
								this.formElements.remove(k);
							}
						}
					}.bind(this));

					subgroups = group.getElements('.fabrikSubGroup');// minus the removed
																														// group
					var nameMap = {};
					this.formElements.each(function(e, k) {
						if (e.groupid == i) {
							nameMap[k] = e.decreaseName(delIndex);
						}
					}.bind(this));
					// ensure that formElements' keys are the same as their object's ids
					// otherwise delete first group, add 2 groups - ids/names in last
					// added group are not updated
					$H(nameMap).each(function(newKey, oldKey) {
						if (oldKey !== newKey) {
							this.formElements[newKey] = this.formElements[oldKey];
							delete this.formElements[oldKey];
						}
					}.bind(this));
					this.watchGroupButtons();
					eval(js);
					window.fireEvent('fabrik.group.delete', [event, i]);
				}.bind(this)
			}).start(1, 0);
			if (toel) {
				// !! added
				// Only scroll the window if the previous element is not visible
				var win_scroll = $(window).getScroll().y;
				var obj = toel.getCoordinates();
				// If the top of the previous repeat goes above the top of the visible
				// window,
				// scroll down just enough to show it.
				if (obj.top < win_scroll) {
					var new_win_scroll = obj.top;

					this.winScroller.scrollTo(0, new_win_scroll);
				}
				// !! removed
				// this.winScroller.toElement(toel);
			}
		}
		// update the hidden field containing number of repeat groups
		$('fabrik_repeat_group_' + i + '_counter').value = $('fabrik_repeat_group_' + i + '_counter').get('value').toInt() - 1;
		// $$$ hugh - noooooooo!  See comment around line 70.  Never decrement this number!
		//this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) - 1);
	},

	hideLastGroup : function(groupid, subGroup) {
		var sge = subGroup.getElement('.fabrikSubGroupElements');
		sge.setStyle('display', 'none');
		new Element('div', {
			'class' : 'fabrikNotice'
		}).appendText(this.lang.nodata).injectAfter(sge);
	},

	isFirstRepeatSubGroup : function(group) {
		var subgroups = group.getElements('.fabrikSubGroup');
		return subgroups.length == 1 && subgroups[0].getElement('.fabrikNotice');
	},

	getSubGroupToClone : function(groupid) {
		var group = $('group' + groupid);
		var subgroup = group.getElement('.fabrikSubGroup');
		if (!subgroup) {
			subgroup = this.subGroups.get(groupid);
		}

		var clone = null;
		var found = false;
		if (this.duplicatedGroups.hasKey(groupid)) {
			found = true;
		}
		if (!found) {
			clone = subgroup.cloneNode(true);
			this.duplicatedGroups.set(groupid, clone);
		} else {
			if (!subgroup) {
				clone = this.duplicatedGroups.get(groupid);
			} else {
				clone = subgroup.cloneNode(true);
			}
		}
		return clone;
	},

	repeatGetChecked : function(group) {
		// /stupid fix for radio buttons loosing their checked value
		var tocheck = [];
		group.getElements('.fabrikinput').each(function(i) {
			if (i.type == 'radio' && i.getProperty('checked')) {
				tocheck.push(i);
			}
		});
		return tocheck;
	},

	/* duplicates the groups sub group and places it at the end of the group */

	duplicateGroup : function(event) {
		if (!this.runPlugins('onDuplicateGroup', event)) {
			return;
		}
		if (this.options.mooversion == '1.1' && event) {
			var event = new Event(event);
		}
		if (event)
			event.stop();
		var i = $(event.target).findClassUp('fabrikGroup').id.replace('group', '');
		var js = this.duplicateGroupJS.get(i);
		var group = $('group' + i);
		var c = this.repeatGroupMarkers.get(i);
		if (c >= this.options.maxRepeat[i] && this.options.maxRepeat[i] !== 0) {
			return;
		}
		
		$('fabrik_repeat_group_' + i + '_counter').value = $('fabrik_repeat_group_' + i + '_counter').get('value').toInt() + 1;

		if (this.isFirstRepeatSubGroup(group)) {
			var subgroups = group.getElements('.fabrikSubGroup');
			// user has removed all repeat groups and now wants to add it back in
			// remove the 'no groups' notice
			subgroups[0].getElement('.fabrikNotice').remove();
			subgroups[0].getElement('.fabrikSubGroupElements').setStyle('display', '');
			this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) + 1);
			return;
		}
		var clone = this.getSubGroupToClone(i);
		this.addedGroups.push(clone);//testing for rest form
		var tocheck = this.repeatGetChecked(group);

		group.appendChild(clone);
		tocheck.each(function(i) {
			i.setProperty('checked', true);
		});
		// remove values and increment ids
		var newElementControllers = [];
		this.subelementCounter = 0;
		var hasSubElements = false;
		var inputs = clone.getElements('.fabrikinput');
		var lastinput = null;
		this.formElements.each(function(el) {
			var formElementFound = false;
			subElementContainer = null;
			var subElementCounter = -1;
			inputs.each(function(input) {

				hasSubElements = el.hasSubElements();

				// for all instances of the call to findClassUp use el.element rather
				// than input (HMM SEE LINE 912 - PERHAPS WE CAN REVERT TO USING INPUT
				// NOW?)
				// var testid = (hasSubElements) ?
				// input.findClassUp('fabrikSubElementContainer').id : input.id
				// var testid = (hasSubElements) ?
				// el.element.findClassUp('fabrikSubElementContainer').id : input.id;
				var testid = (hasSubElements) ? input.findClassUp('fabrikSubElementContainer').id : input.id;
				if (el.options.element == testid) {
					lastinput = input;
					formElementFound = true;

					if (hasSubElements) {
						subElementCounter++;
						// the line below meant that we updated the orginal groups id @ line
						// 942 - which in turn meant when we cleared the values we were
						// clearing the orignal elements values
						// not sure how this fits in with comments above which state we
						// should use el.element.findClassUp('fabrikSubElementContainer');
						// REAL ISSUE WAS THAT inputs CONTAINED ADD OPTIONS
						// (elementmodel->getAddOptionFields) WHICH HAD ELEMENTS WITH THE
						// CLASS fabrikinput THIS CLASS IS RESERVERED FOR ACTUAL DATA
						// ELEMENTS
						// subElementContainer =
						// el.element.findClassUp('fabrikSubElementContainer');

						subElementContainer = input.findClassUp('fabrikSubElementContainer');
						// clone the first inputs event to all subelements
						input.cloneEvents($(testid).getElement(input.getTag()));

						// id set out side this each() function
					} else {
						// $$$ rob note that clonevents wont work for select's onchange
						// event - use onblur instead
						input.cloneEvents(el.element);

						// update the element id use el.element.id rather than input.id as
						// that may contain _1 at end of id
						var bits = $A(el.element.id.split('_'));
						bits.splice(bits.length - 1, 1, c);
						input.id = bits.join('_');
						// input.id = el.element.id + '_' + c;

						// update labels for non sub elements
						var l = input.findClassUp('fabrikElementContainer').getElement('label');
						if (l) {
							l.setProperty('for', input.id);
						}
					}

					input.name = input.name.replace('[0]', '[' + (c) + ']');
				}
			}.bind(this));

			if (formElementFound) {
				if (hasSubElements && $type(subElementContainer) != false) {
					// if we are checking subelements set the container id after they have
					// all
					// been processed
					// otherwise if check only works for first subelement and no further
					// events are cloned
					
					// $$$ rob fix for date element
					var bits = $A(el.options.element.split('_'));
					bits.splice(bits.length - 1, 1, c);
					subElementContainer.id = bits.join('_');
					
				}
				var origelid = el.options.element;
				// clone js element controller, set form to be passed by reference and
				// not cloned
				var ignore = el.unclonableProperties();
				var newEl = new CloneObject(el, true, ignore);

				newEl.container = null;
				newEl.options.repeatCounter = c;
				newEl.origId = origelid;

				if (hasSubElements && $type(subElementContainer) != false) {
					newEl.element = $(subElementContainer);
					newEl.options.element = subElementContainer.id;
					newEl._getSubElements();
				} else {
					newEl.element = $(lastinput.id);
					newEl.options.element = lastinput.id;
				}
				//newEl.reset();
				newElementControllers.push(newEl);
			}
		}.bind(this));

		// add new element controllers to form

		// $$$ hugh - had to move the cloned() loop to before addElements() is
		// called, to fix
		// issue with CDD element, where the cloned() method sets an option to tell
		// attachedToForm()
		// (which is called from addElement()) it needs to update the cascade.
		newElementControllers.each(function(newEl) {
			newEl.cloned(c);
			// $$$ hugh - moved reset() from end of main loop above because otherwise things like map element will run their
			// update() method BEFORE they have done their cloned() method, and end up resetting the wrong map back to defaults.
			newEl.reset();
			newEl.applyAjaxValidations();
		});
		var o = {};
		o[i] = newElementControllers;
		this.addElements(o);

		// !! added
		// Only scroll the window if the new element is not visible
		// var win_size = window.getSize().y;
		var win_size = window.getHeight();
		var win_scroll = $(window).getScroll().y;
		var obj = clone.getCoordinates();
		// If the bottom of the new repeat goes below the bottom of the visible
		// window,
		// scroll up just enough to show it.
		if (obj.bottom > (win_scroll + win_size)) {
			var new_win_scroll = obj.bottom - win_size;

			this.winScroller.scrollTo(0, new_win_scroll);
		}
		// !! removed
		// this.winScroller.toElement(clone);

		var myFx = new Fx.Style(clone, 'opacity', {
			duration : 500
		}).set(0);
		/*
		 * // $$$ hugh - moved this a few lines up there ^^
		 * newElementControllers.each( function(newEl) { newEl.cloned(c); });
		 */
		//c = c + 1;
		myFx.start(1);
		eval(js);
		// $$$ hugh - added groupid (i) and repeatCounter (c) as args
		// note I commented out the increment of c a few lines above
		this.runPlugins('onDuplicateGroupEnd', event, i, c);
		window.fireEvent('fabrik.group.duplicate', [event, i, c]);
		this.repeatGroupMarkers.set(i, this.repeatGroupMarkers.get(i) + 1);
		this.unwatchGroupButtons();
		this.watchGroupButtons();
	},

	update : function(o) {
		if (!this.runPlugins('onUpdate', null)) {
			return;
		}
		var leaveEmpties = arguments[1] || false;
		var data = o.data;
		this.getForm();
		if (this.form) { // test for detailed view in module???
			var rowidel = this.form.getElement('input[name=rowid]');
			if (rowidel && data.rowid) {
				rowidel.value = data.rowid;
			}
		}
		this.formElements.each(function(el, key) {
			// if updating from a detailed view with prev/next then data's key is in
			// _ro format
			if ($type(data[key]) === false) {
				if (key.substring(key.length - 3, key.length) == '_ro') {
					key = key.substring(0, key.length - 3);
				}
			}
			// this if stopped the form updating empty fields. Element update()
			// methods
			// should test for null
			// variables and convert to their correct values
			// if (data[key]) {
			if ($type(data[key]) === false) {
				// only update blanks if the form is updating itself
				// leaveEmpties set to true when this form is called from updateRows
				if (o.id == this.id && !leaveEmpties) {
					el.update('');
				}
			} else {
				el.update(data[key]);
			}
		}.bind(this));
	},

	reset : function() {
		this.addedGroups.each(function(subgroup){
			var group = $(subgroup).findClassUp('fabrikGroup');
			var i = group.id.replace('group', '');
			$('fabrik_repeat_group_' + i + '_counter').value = $('fabrik_repeat_group_' + i + '_counter').get('value').toInt() - 1;
			subgroup.remove();
		});
		this.addedGroups = [];
		if (!this.runPlugins('onReset', null)) {
			return;
		}
		this.formElements.each(function(el, key) {
			el.reset();
		}.bind(this));
	},

	showErrors : function(data) {
		var d = null;
		if (data.id == this.id) {
			// show errors
			var errors = new Hash(data.errors);
			if (errors.keys().length > 0) {
				if ($type(this.form.getElement('.fabrikMainError')) !== false) {
					this.form.getElement('.fabrikMainError').setHTML(this.options.error);
					this.form.getElement('.fabrikMainError').removeClass('fabrikHide');
				}
				errors.each(function(a, key) {
					if ($(key + '_error')) {
						var e = $(key + '_error');
						var msg = new Element('span');
						for ( var x = 0; x < a.length; x++) {
							for ( var y = 0; y < a[x].length; y++) {
								d = new Element('div').appendText(a[x][y]).injectInside(e);
							}
						}
					} else {
						fconsole(key + '_error' + ' not found (form show errors)');
					}
				});
			}
		}
	},

	/** add additional data to an element - e.g database join elements */
	appendInfo : function(data) {
		this.formElements.each(function(el, key) {
			if (el.appendInfo) {
				el.appendInfo(data, key);
			}
		}.bind(this));
	},

	addListenTo : function(blockId) {
		this.listenTo.push(blockId);
	},

	clearForm : function() {
		this.getForm();
		if (!this.form) {
			return;
		}
		this.formElements.each(function(el, key) {
			if (key == this.options.primaryKey) {
				this.form.getElement('input[name=rowid]').value = '';
			}
			el.update('');
		}.bind(this));
		// reset errors
		this.form.getElements('.fabrikError').empty();
		this.form.getElements('.fabrikError').addClass('fabrikHide');
	},

	receiveMessage : function(senderBlock, task, taskStatus, data) {
		if (this.listenTo.indexOf(senderBlock) != -1) {
			if (task == 'processForm') {

			}
			// a row from the table has been loaded
			if (task == 'update') {
				this.update(data);
			}
			if (task == 'clearForm') {
				this.clearForm();
			}
		}
		// a form has been submitted which contains data that should be updated in
		// this form.
		// Currently for updating database join drop downs, data is used just as a
		// test to see if the dd needs
		// updating. If found a new ajax call is made from within the dd to update
		// itself
		// $$$ hugh - moved showErrors() so it only runs if data.errors has content
		if (task == 'updateRows' && $type(data) !== false) {
			if ($H(data.errors).keys().length === 0) {
				if ($type(data.data) !== false) {
					this.appendInfo(data);
					this.update(data, true);
				}
			} else {
				this.showErrors(data);
			}
		}
	}/*
		 * ,
		 * 
		 * addPlugin : function(plugin) { this.options.plugins.push(plugin); }
		 */
});

fabrikForm.implement(new Plugins);
