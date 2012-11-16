var FbTableInlineEdit = FbTablePlugin.extend({
	initialize: function(tableform, options, lang) {
		this.setOptions(tableform, options);
		this.tableform = tableform;
		this.defaults = {};
		this.editors = {};
		this.inedit = false;
		this.spinner = new Asset.image(this.options.liveSite+'media/com_fabrik/images/ajax-loader.gif', {
			'alt':'loading',
			'class':'ajax-loader'
		});
		this.addbutton = new Asset.image(this.options.liveSite+'media/com_fabrik/images/action_check.png', {
			'alt':'save',
			'class':''
		});
		this.cancelbutton = new Asset.image(this.options.liveSite+'media/com_fabrik/images/action_delete.png', {
			'alt':'delete',
			'class':''
		});
		this.lang = Object.extend({
			'selectrow':'Please select a row!'
		}, lang || {});
		window.addEvent('domready', function(){
			this.table = $('table_' + this.options.tableid);
			if ($type(this.table) == false) {
				return false;
			}
			this.tableid = this.tableform.getElement('input[name=tableid]').value;
			this.setUp();
		}.bind(this));
		
		window.addEvent('fabrik.table.clearrows', function(){
			this.cancel();			
		}.bind(this));
		
		window.addEvent('fabrik.table.updaterows', function(){
			this.watchCells();
		}.bind(this))
		
		window.addEvent('fabrik.table.ini', function(){
			var table = oPackage.blocks['table_'+this.options.tableid];
			var formData = table.form.toQueryString().toObject();
			formData.format = 'raw';
      		var myFormRequest = new Ajax('index.php', {
      			data: formData,
      			onSuccess: function(json){
      				json = Json.evaluate(json.stripScripts());
      				table.options.data = json.data;
      			}.bind(this)
	       }).request(); 
		}.bind(this))
	},
	
	setUp:function(){
		if($type(this.table) == false) {
			return;
		}
		this.scrollFx = new Fx.Scroll(window, {
			'wait':false
		});
		this.watchCells();
		document.addEvent('keydown', this.checkKey.bindAsEventListener(this));
	},
	
	watchCells:function(){
		var firstLoaded = false;
		this.table.getElements('.fabrik_element').each(function(td, x){
			if (!firstLoaded && this.options.loadFirst) {
				firstLoaded = this.edit(null, td);
				if (firstLoaded) {
					this.select(null, td);
				}
			}
			this.setCursor(td);
			td.removeEvents();
			td.addEvent(this.options.editEvent, this.edit.bindAsEventListener(this, [td]));
			td.addEvent('click', this.select.bindAsEventListener(this, [td]));
			if(this.canEdit(td)){
				td.addEvent('mouseenter', function(e){
					if(!td.hasClass('fabrik_uneditable')) {
						td.setStyle('cursor', 'pointer')}
					}
					);
				td.addEvent('mouseleave', function(e){td.setStyle('cursor', '')});
			}
		}.bind(this));
	},
	
	checkKey: function(e){
		e = new Event(e);
		if ($type(this.td) !== 'element') {
			return;
		}
		switch(e.code){

			case 39:
				//right
				if(this.inedit) {
					return;
				}
				if ($type(this.td.getNext()) === 'element') {
					e.stop();
					this.select(e, this.td.getNext());
				}
				break;
			case 9:
				//tab
				if(this.inedit) {
					if(this.options.tabSave) {
						if ($type(this.editing) === 'element') {
							this.save(e, this.editing);
						} else {
							this.edit(e, this.td);
						}
					}
					//var next = e.shift ? this.td.getPrevious() : this.td.getNext();
					var next = e.shift ? this.getPreviousEditable(this.td) : this.getNextEditable(this.td);
					if ($type(next) === 'element') {
						e.stop();
						this.select(e, next);
						this.edit(e, this.td);
					}
					return;
				}
				e.stop();
				if(e.shift){
					if ($type(this.td.getPrevious()) === 'element') {
						this.select(e, this.td.getPrevious());
					}
				}else{
					if ($type(this.td.getNext()) === 'element') {
						this.select(e, this.td.getNext());
					}
				}
				break;
			case 37: //left
				if(this.inedit) {
					return;
				}
				if ($type(this.td.getPrevious()) === 'element') {
					e.stop();
					this.select(e, this.td.getPrevious());
				}
				break;
			case 40: //down
				if(this.inedit) {
					return;
				}
				var row = this.td.getParent();
				if($type(row) == false){
					return;
				}
				var index = row.getElements('td').indexOf(this.td);
				if ($type(row.getNext()) === 'element') {
					e.stop();
					var nexttds = row.getNext().getElements('td');
					this.select(e, nexttds[index]);
				}
				break;
			case 38:
				//up
				if(this.inedit) {
					return;
				}
				var row = this.td.getParent();
				if($type(row) == false){
					return;
				}
				var index = row.getElements('td').indexOf(this.td);
				if ($type(row.getPrevious()) === 'element') {
					e.stop();
					var nexttds = row.getPrevious().getElements('td');
					this.select(e, nexttds[index]);
				}
				break;
			case 27:
				//escape
				e.stop();
				this.select(e, this.editing);
				this.cancel(e);
				break;
			case 13:
				//enter
				e.stop();
				if ($type(this.editing) === 'element') {
					this.save(e, this.editing);
				} else {
					this.edit(e, this.td);
				}
				break;
		}
	},
	
	select: function(e, td) {
		if (td.hasClass('fabrik_uneditable')) {
			return;
		}
		var element = this.getElementName(td);
		var opts = this.options.elements[element];
		if($type(opts) === false) {
			return;
		}
		if($type(this.td) === 'element'){
			this.td.removeClass(this.options.focusClass);
		}
		this.td = td;
		if ($type(this.td) === 'element') {
			this.td.addClass(this.options.focusClass);
		}
		if ($type(this.td) == false) {
			return;
		}
		var p = this.td.getPosition();

		var x = p.x - (window.getSize().size.x/2) - (this.td.getSize().size.x / 2);
		var y = p.y - (window.getSize().size.y/2) + (this.td.getSize().size.y / 2);
		this.scrollFx.scrollTo(x, y);
	},
	
	getElementName: function(td){
		var c = td.className.split(' ').filter(function(item, index) {
			return item !== 'fabrik_element' && item !== 'fabrik_row';
		});
		var element = c[0].replace('fabrik_row___', '');
		return element;
	},
	
	setCursor: function(td){
		var element = this.getElementName(td);
		var opts = this.options.elements[element];
		if($type(opts) === false){
			return;
		}
		td.addEvent('mouseover', function(e){
			if (!$(e.target).hasClass('fabrik_uneditable')) {
				e.target.setStyle('cursor', 'pointer');
			}
		});
		td.addEvent('mouseleave', function(e){
			e.target.setStyle('cursor', '');
		});
	},
	
	getPreviousEditable:function(active){
		var found = false;
		var tds = this.table.getElements('.fabrik_element');
		for(var i=tds.length; i>=0; i--){
			if (found) {
				if(this.canEdit(tds[i])){
					return tds[i];
				}
			}
			if(tds[i] === active){
				found = true;
			}
		}
		return false;
	},
	
	getNextEditable:function(active){
		var found = false;
		var next = this.table.getElements('.fabrik_element').filter(function(td, i){
			if (found) {
				if (this.canEdit(td)) {
					found = false;
					return true;
				} 
			}
			if (td === active) {
				found = true;
			}
			return false;
		}.bind(this));
		return next.getLast();
	},
	
	canEdit:function(td){
		if (td.hasClass('fabrik_uneditable')) {
			return false;
		}
		var element = this.getElementName(td);
		var opts = this.options.elements[element];
		if($type(opts) === false){
			return false;
		}
		return true;
	},
	
	edit: function(e, td) {
		//only one field can be edited at a time
		if (this.inedit) {
			return false;
		}
		if (!this.canEdit(td)){
			return false;
		}
		if($type(e) !== false){
			e = new Event(e).stop();
		}
		var element = this.getElementName(td);
		var rowid = td.findClassUp('fabrik_row').id.replace(this.table.id + '_row_', '');
		
		var opts = this.options.elements[element];
		if($type(opts) === false){
			return;
		}
		this.inedit = true;
		this.editing = td;
		this.defaults[rowid+'.'+opts.elid] = td.innerHTML;
		
		var data = this.getDataFromTable(td);
		if ($type(this.editors[opts.elid]) === false) {
			td.empty().adopt(this.spinner);
			var url = this.options.liveSite + 'index.php?option=com_fabrik&controller=element&format=raw';
			new Ajax(url, {
				'data':{
					'element':element,
					'elid':opts.elid,
					'plugin':opts.plugin,
					'rowid':rowid,
					'tableid':this.options.tableid,
					'inlinesave':this.options.showSave,
					'inlinecancel':this.options.showCancel
				},
				'evalScripts':false,
				'onComplete':function(r){
					//don't use evalScripts as we reuse the js when tabbing to the next element. Previously js was wrapped in delay
					//but now we want to use it with and without the delay
					var javascript;
					var html = r.stripScripts(function(script){
						javascript = script;
					}.bind(this));
					//delay the script to allow time for the dom to be updated
					(function(){
						$exec(javascript);
					}).delay(1000);
					td.empty().setHTML(html);
					this.editors[opts.elid] = r;
					this.watchControls(td);
					this.setFocus(td);
					
				}.bind(this)
			}).request();
		} else {
			//testing trying to re-use old form
			var javascript;
			var html = this.editors[opts.elid].stripScripts(function(script){
				javascript = script;
			}.bind(this));
		
			td.empty().setHTML(html);
			//make a new instance of the element js class which will use the new html
			$exec(javascript);
			//tell the element obj to update its value
			window['inlineedit_'+opts.elid].update(data);
			window['inlineedit_'+opts.elid].select();
			this.watchControls(td);
			this.setFocus(td);
		}
		return true;
	},
	
	getDataFromTable: function(td)
	{
		var groupedData = oPackage.blocks['table_'+this.tableid].options.data;
		var element = this.getElementName(td);
		var ref = td.findClassUp('fabrik_row').id;
		var v = false;
		this.vv = [];
		// $$$rob $H needed when group by applied
		$H(groupedData).each(function(data){
			
			if ($type(data) == 'array') {//groued by data in forecasting slotenweb app. Where groupby table plugin applied to data.
				for(var i  =0; i < data.length; i++) {
					if (data[i].id === ref) {
						this.vv.push(data[i]);
					}
				};
			} else {
				var vv = data.filter(function(row){
					return row.id === ref;
				});
			}
		}.bind(this));
		if (this.vv.length > 0) {
			v = this.vv[0].data[element];
		}
		return v;
	},
	
	setTableData:function(row, element, val){
		ref = row.id;
		var groupedData = oPackage.blocks['table_'+this.tableid].options.data;
		// $$$rob $H needed when group by applied
		$H(groupedData).each(function(data){
			data.each(function(row){
				if(row.id === ref){
					row.data[element] = val;
				}
			});
		});
	},
	
	setFocus : function(td){
		if($type(td.getElement('.fabrikinput')) !== false) {
			td.getElement('.fabrikinput').focus();
		}
	},
	
	watchControls : function(td) {
		if($type(td.getElement('a.inline-save')) !== false) {
			td.getElement('a.inline-save').addEvent('click',  this.save.bindAsEventListener(this, [td]));
		}
		if($type(td.getElement('a.inline-cancel')) !== false) {
			td.getElement('a.inline-cancel').addEvent('click',  this.cancel.bindAsEventListener(this, [td]));
		}
	},
	
	save: function(e, td) {
		window.fireEvent('fabrik.table.updaterows');
		this.inedit = false;
		e = new Event(e).stop();
		var element = this.getElementName(td);
		var url = this.options.liveSite + 'index.php?option=com_fabrik&controller=element&task=save&format=raw';
		var opts = this.options.elements[element];
		var row = this.editing.findClassUp('fabrik_row');
		var rowid = row.id.replace(this.table.id + '_row_', '');
		td.removeClass(this.options.focusClass);
		//var eObj = eval('inlineedit_' + opts.elid);
		var eObj = window['inlineedit_'+opts.elid];
		if ($type(eObj) === false) {
			fconsole('issue saving from inline edit: eObj not defined');
			this.cancel(e);
			return false;
		}
		delete eObj.element;
		eObj.getElement();
		var value = eObj.getValue();
		var k = 'value';

		this.setTableData(row, element, value);
		var data = {
			'element':element,
			'elid':opts.elid,
			'plugin':opts.plugin,
			'rowid':rowid,
			'tableid':this.options.tableid
		};
		data[k] = value;
		td.empty().adopt(this.spinner);
		new Ajax(url, {
			'data':data,
			'evalScripts':true,
			'onComplete':function(r){
				td.empty().setHTML(r);
				window.fireEvent('fabrik.table.updaterows');
			}.bind(this)
		}).request();

		this.editing = null;
		return true;
	},
	
	cancel: function(e) {
		if(e) {
			e = new Event(e).stop();
		}
		if($type(this.editing) !== 'element') {
			return;
		}
		var row = this.editing.findClassUp('fabrik_row');
		if (row !== false) {
			var rowid = row.id.replace(this.table.id + '_row_', '');
		}
		var td = this.editing;
		if (td !== false) {
			td.removeClass(this.options.focusClass);
			var element = this.getElementName(td);
			var opts = this.options.elements[element];
			var c = this.defaults[rowid+'.'+opts.elid];
			td.setHTML(c);
		}
		this.editing = null;
		this.inedit = false;
	}
});