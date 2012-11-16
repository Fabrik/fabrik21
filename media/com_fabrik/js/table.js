/**
 * @author Robert
 */
 
var FbTablePlugin = new Class({
 	setOptions: function(tableform, options) {
		window.addEvent('domready', function(){
			this.tableform = $(tableform);
		}.bind(this));
		this.options = {};
		this.requireChecked = true;
		$extend(this.options, options);
	},
	
	clearFilter:$empty,
	
	watchButton: function() {
		var buttons = this.tableform.getElements('input[name='+this.options.name+']');
		if(!buttons || buttons.length == 0) {
			return;
		}
		buttons.addEvent('click', function(event) {
			var e = new Event(event);
			e.stop();
			var ok = false;
			if (row = $(e.target).findClassUp('fabrik_row')) {
				if (td = row.getElement('.fabrik_row___fabrik_delete input')) {
					td.checked = true;
				}
			} else {
				this.tableform.getElements('input[name^=ids]').each( function(c) {
					if(c.checked) {
						ok = true;
					}
				});
				if(!ok && this.requireChecked) {
					alert(this.lang.selectrow);
					return;
				}
			}
			var n = buttons[0].name.split('-');
			this.tableform.getElement('input[name=fabrik_tableplugin_name]').value = n[0];
			this.tableform.getElement('input[name=fabrik_tableplugin_renderOrder]').value = n.getLast();
			this.buttonAction();
		}.bind(this));
	},
	
	buttonAction:function(){
		oPackage.submitfabrikTable(this.tableid, 'doPlugin');
	}
});

var TableFilter = new Class({
 	
 	initialize:function(options){
 		this.filters = $H({});
		this.options = {
			'container': '',
			'type':'table',
			'id':''
		};
		$extend(this.options, options);
		this.container = $(this.options.container);
		if($type(this.container) === false){
			return;
		}
		var c = this.container.getElement('.clearFilters');
		if($type(c) !== false){
			c.removeEvents();
			c.addEvent('click', function(e){
				new Event(e).stop();
				this.container.getElements('.fabrik_filter').each(function(f){
					if(f.getTag() == 'select'){
						f.selectedIndex = 0;
					}else{
						f.value = '';
					}
				});
				var plugins = oPackage.blocks.get(this.options.type+'_'+this.options.id);
				if($type(plugins.plugins) !== false){ //viz filters may not have plugins
				plugins.plugins.each(function(p){
					try{
						p.clearFilter();
					}catch(err){
						fconsole(err);
					}
				});
				}
				new Element('input', {'name':'resetfilters', 'value':1, 'type':'hidden', 'type':'hidden'}).injectInside(this.container);
				if (this.options.type == 'table') {
					oPackage.submitfabrikTable(this.options.id, 'filter');
				}else{
					this.container.getElement('form[name=filter]').submit();
				}
			}.bind(this));
		}
 	},
 	
 	addFilter:function(plugin, f){
 		if(this.filters.hasKey(plugin) === false){
 			this.filters.set(plugin, []);
 		}
 		this.filters.get(plugin).push(f);
 	},
 	
 	// $$$ hugh - added this primarily for CDD element, so it can get an array to emulate submitted form data
 	// for use with placeholders in filter queries.  Mostly of use if you have daisy chained CDD's.
	getFilterData : function() {
		var h = {};
		this.container.getElements('.fabrik_filter').each(function(f){
			if (f.id.test(/value$/)) {
				var key = f.id.match(/(\S+)value$/)[1];
				// $$$ rob added check that something is select - possbly causes js error in ie
				if (f.getTag() == 'select' && f.selectedIndex !== -1) {
					h[key] = $(f.options[f.selectedIndex]).get('text');
				}
				else {
					h[key] = f.get('value');	
				}
				h[key + '_raw'] = f.get('value');
			}
		}.bind(this));
		return h;
	},
 	
 	update: function(){
 		this.filters.each(function(fs, plugin){
 			fs.each(function(f){
 				f.update();
 			}.bind(this));
 		}.bind(this));
 	}
});

TableFilter.implement(new Events);
TableFilter.implement(new Options);
 
var fabrikTable = new Class({

  initialize: function(id, options, lang){
    this.id = id;
    this.listenTo = $A([]);
    this.options = {
      'admin': false,
      'filterMethod':'onchange',
      'postMethod': 'post',
      'form': 'tableform_' + this.id,
      'hightLight': '#ccffff',
      'emptyMsg': 'No records found',
      'primaryKey': '',
      'headings': [],
      'labels':{},
      'Itemid': 0,
      'formid': 0,
      'canEdit': true,
      'canView': true,
      'page': 'index.php',
      'formels':[], //elements that only appear in the form
      'plugins':[],
      'data': [], //[{col:val, col:val},...] (depreciated)
      'rowtemplate':''
    };
    $extend(this.options, options);
    this.translate = {
	 	'select_rows':'Select some rows for deletion',
		'confirm_drop':"Do you really want to delete all records and reset this tables key to 0?",
		'yes':'Yes',
		'no':'No',
		'select_colums_to_export':'Select the columns to export',
		'include_filters':'Include filters:',
		'include_data':'Inclde data:',
		'inlcude_raw_data':'Include raw data:',
		'include_calculations':'Include calculations:',
		'export':'Export',
		'loading':'loading',
		'savingto':'Saving to',
		'confirmDelete':'Are you sure you want to delete these records?',
		'csv_downloading':'CSV file now downloading',
		'download_here':'download here',
		'csv_complete':'csv created',
		'filetype':'File type'
	};
		
   	$extend(this.translate, lang);
		window.addEvent('domready', function(e){
			this.getForm();
	    this.table = $('table_' + id);
	    if (this.table) {
	      this.tbody = this.table.getElement('tbody');
	    }
			this.watchAll();
			window.fireEvent('fabrik.table.ini');
		}.bind(this));
  },
  
  setRowTemplate:function(){
  	// $$$ rob mootools 1.2 has bug where we cant setHTML on table
		//means that there is an issue if table contains no data
		if ($type(this.options.rowtemplate) === 'string'){
	  	var r = this.table.getElement('.fabrik_row');
			if (window.ie && $type(r) !== false) {
				this.options.rowtemplate = r;
			}
		}
  },
	
	watchAll: function()
	{
		this.watchNav();
		this.watchRows();
		this.watchFilters();
		this.watchOrder();
		this.watchEmpty();
		this.watchButtons();
	},
	
	watchButtons: function()
	{
		this.exportWindowOpts = {
			id: 'exportcsv',
			title: 'Export CSV',
			loadMethod:'html',
			minimizable:false,
			width: 360,
			height: 120,
			content:''		
		};
	
		if(this.form.getElements('.csvExportButton')){
			this.form.getElements('.csvExportButton').each(function(b){
				if(b.hasClass('custom') === false){
					b.addEvent('click', function(e){
						e = new Event(e).stop();
						var thisc = this.makeCSVExportForm();
						this.form.getElements('.fabrik_filter').each(function(f){
							var fc = new Element('input', {'type':'hidden','name':f.name,'id':f.id,'value':f.get('value')});
							fc.injectInside(thisc);
						}.bind(this));
						this.exportWindowOpts.content = thisc;
						
						this.exportWindowOpts.onContentLoaded = function(){
							(function(){oPackage.resizeMocha('exportcsv');}).delay(700);
						};
						if(this.options.mooversion > 1.1){
							var win = new MochaUI.Window(this.exportWindowOpts);
						}else{
							document.mochaDesktop.newWindow(this.exportWindowOpts);
						}
					}.bind(this));
				}
			}.bind(this));
		}
	},
	
	makeCSVExportForm:function(){
		// cant build via dom as ie7 doesn't accept checked status
		var rad = "<input type='radio' value='1' name='incfilters' checked='checked' />" + this.translate.yes;
		var rad2 = "<input type='radio' value='1' name='incraw' checked='checked' />" + this.translate.yes;
		var rad3 = "<input type='radio' value='1' name='inccalcs' checked='checked' />" + this.translate.yes;
		var rad4 = "<input type='radio' value='1' name='inctabledata' checked='checked' />" + this.translate.yes;
		var rad5 = "<input type='radio' value='1' name='excel' checked='checked' />Excel CSV";
		var url = 'index.php?option=com_fabrik&view=table&tableid='+this.id+'&format=csv';

		var divopts = {'styles':{'width':'200px','float':'left'}};
		var c = new Element('form', {'action':url, 'method':'post', 'id':'csvexportform'}).adopt([
			
		new Element('div', divopts).appendText(this.translate.filetype),
		new Element('label').setHTML(rad5),
		new Element('label').adopt([
			new Element('input', {'type':'radio','name':'excel','value':'0'}), 
			new Element('span').appendText('CSV')
		]),
		new Element('br'),
		new Element('br'),
		new Element('div', divopts).appendText(this.translate.include_filters),
		new Element('label').setHTML(rad),
		new Element('label').adopt([
			new Element('input', {'type':'radio','name':'incfilters','value':'0'}), 
			new Element('span').appendText(this.translate.no)
		]),
		new Element('br'),
		new Element('div', divopts).appendText(this.translate.include_data),
		new Element('label').setHTML(rad4),
		new Element('label').adopt([
			new Element('input', {'type':'radio','name':'inctabledata','value':'0'}), 
			new Element('span').appendText(this.translate.no)
		]),
		new Element('br'),
		new Element('div', divopts).appendText(this.translate.inlcude_raw_data),
		new Element('label').setHTML(rad2),
		new Element('label').adopt([
			new Element('input', {'type':'radio','name':'incraw','value':'0'}), 
			new Element('span').appendText(this.translate.no)
		]),
		new Element('br'),
		new Element('div', divopts).appendText(this.translate.include_calculations),
		new Element('label').setHTML(rad3),
		new Element('label').adopt([
			new Element('input', {'type':'radio','name':'inccalcs','value':'0'}), 
			new Element('span').appendText(this.translate.no)
		])
		]);
		new Element('h4').appendText(this.translate.select_colums_to_export).injectInside(c);
		var g = '';
		var i = 0;
		$H(this.options.labels).each(function(label, k){
			if (k.substr(0, 7) != 'fabrik_') {
	  		var newg = k.split('___')[0];
				if(newg !== g){
					g = newg;
					new Element('h5').setText(g).injectInside(c);
				}
				var rad = "<input type='radio' value='1' name='fields["+k+"]' checked='checked' />" + this.translate.yes;
			  label =  label.replace(/<\/?[^>]+(>|$)/g, "");
				var r = new Element('div', divopts).appendText(label);
				r.injectInside(c);
				new Element('label').setHTML(rad).injectInside(c);
				new Element('label').adopt([
				new Element('input', {'type':'radio','name':'fields['+k+']','value':'0'}), 
				new Element('span').appendText(this.translate.no)
				]).injectInside(c);
				new Element('br').injectInside(c);
	  	}
			i++;
		}.bind(this)); 
		
		// elements not shown in table
		if(this.options.formels.length > 0){ 
			new Element('h5').setText('Form fields').injectInside(c);
			this.options.formels.each(function(el){
				var rad = "<input type='radio' value='1' name='fields["+el.name+"]' checked='checked' />" + this.translate.yes;
				var r = new Element('div', divopts).appendText(el.label);
				r.injectInside(c);
				new Element('label').setHTML(rad).injectInside(c);
					new Element('label').adopt([
					new Element('input', {'type':'radio','name':'fields['+el.name+']','value':'0'}), 
					new Element('span').appendText(this.translate.no)
					]).injectInside(c);
					new Element('br').injectInside(c);	
			}.bind(this));
		}
		
		new Element('div', {'styles':{'text-align':'right'}}).adopt(
			new Element('input', {'type':'button','name':'submit','value':this.translate['export'], 'class':'button', events:{
				'click':function(e){
					e = new Event(e);
					e.stop();
					e.target.disabled = true;
					$('csvexportform').hide();
					new Element('div', {'id': 'csvmsg'}).setHTML(this.translate['loading']+' <br /><span id="csvcount">0</span> / <span id="csvtotal"></span> '+this.translate['records'] + '.<br/> '+this.translate['savingto']+' <span id="csvfile"></span>').injectAfter($('csvexportform'));
					
					this.triggerCSVImport(0);
					oPackage.resizeMocha('exportcsv');
				}.bind(this)
				
			}})
		).injectInside(c);
		new Element('input', {'type':'hidden','name':'view','value':'table'}).injectInside(c);
		new Element('input', {'type':'hidden','name':'option','value':'com_fabrik'}).injectInside(c);
		new Element('input', {'type':'hidden','name':'tableid','value':this.id}).injectInside(c);
		new Element('input', {'type':'hidden','name':'format','value':'csv'}).injectInside(c);
		new Element('input', {'type':'hidden','name':'c','value':'table'}).injectInside(c);
		return c;
	},
	
	triggerCSVImport:function(start, opts, fields){
		var url = 'index.php?option=com_fabrik&c=table&view=table&format=csv&tableid='+this.id+'&task=viewTable';
		if (start !== 0) {
			opts = this.csvopts;
			fields = this.csvfields;
		}else{
			if (!opts) {
				var opts = {};
				if ($type($('exportcsv')) !== false) {
					$A(['incfilters', 'inctabledata', 'incraw', 'inccalcs', 'excel']).each(function(v){
						var inputs = $('exportcsv').getElements('input[name=' + v + ']');
						if (inputs.length > 0) {
							opts[v] = inputs.filter(function(i){
								return i.checked;
							})[0].value;
						}
					});
				}
			}
			//selected fields
			if (!fields) {
				var fields = {};
				if ($type($('exportcsv')) !== false) {
				$('exportcsv').getElements('input[name^=field]').each(function(i){
					if(i.checked){
						var k = i.name.replace('fields[', '').replace(']', '');
						fields[k] = i.get('value');
					}
				});
				}
			}
			opts['fields'] = fields;
			this.csvopts = opts;
			this.csvfields = fields;
		}
		var thisurl = url +'&start='+start;
		var myAjax = new Ajax(thisurl, {
            method: 'post',
						data:opts,
            onComplete: function(res){
							res = Json.evaluate(res);
							if ($type($('csvcount')) !== false) $('csvcount').setText(res.count);
							if ($type($('csvtotal')) !== false) $('csvtotal').setText(res.total);
							if ($type($('csvfile')) !== false) $('csvfile').setText(res.file);
              if (res.count < res.total) {
								this.triggerCSVImport(res.count);
							}else{
								var finalurl = oPackage.options.liveSite+'index.php?option=com_fabrik&view=table&format=csv&tableid='+this.id+'&start='+res.count;
								if (!window.ie) {
									window.location=finalurl;
									var msg = this.translate.csv_downloading;
								}else{
									var msg = this.translate.csv_complete;
								}
								msg += ' <a href="'+finalurl+'">'+this.translate.download_here+'</a>';
								if ($type($('csvmsg')) !== false) $('csvmsg').setHTML(msg);
							}
            }.bind(this)
          });
		myAjax.request();
	},
	
	addPlugins:function(a){
		// $$$ rob shouldnt have been this.plugins but leaving here in case ppl use it
		// should be this.options.plugins to get things to play nicely with the implemented plugins class
		this.plugins = a;
		this.options.plugins = a;
		window.addEvent('domready', function(e){
			// delay for use in mocha win
			var delay = window.ie ? 1000 : 0;
			(function(){
			this.runPlugins('onAttach', e);
			}.bind(this)).delay(2000);
		}.bind(this));
		
	},
	
	watchEmpty: function(e){
		var b = $E('input[name=doempty]', this.options.form);
		if (b) {
			b.addEvent('click', function(e){
				var event = new Event(e).stop();
				if( confirm(this.translate.confirm_drop)){
					oPackage.submitfabrikTable(this.id,'doempty');
				}
			}.bind(this));
		}
	},
	
	watchOrder: function() {
		var elementId = false;
		var hs = $(this.options.form).getElementsBySelector('.fabrikorder, .fabrikorder-asc, .fabrikorder-desc');
		hs.removeEvents('click');
		hs.each(function(h){
			h.addEvent('click', function(event) {
				var e = new Event(event);
				var orderdir = '';
				var newOrderClass = '';
				// $$$ rob in pageadaycalendar.com h was null so reset to e.target
				var h = $(e.target);
				var td = h.findClassUp('fabrik_ordercell');
				
				if (h.getTag() !== 'a') {
					var h = td.getElement('a');
				}
				switch(h.className){
					case 'fabrikorder-asc':
						newOrderClass = 'fabrikorder-desc';
						orderdir = 'desc';
						break;
					case 'fabrikorder-desc':
						newOrderClass = 'fabrikorder';
						orderdir = "-";
						break;
					case 'fabrikorder':
						newOrderClass = 'fabrikorder-asc';
						orderdir = 'asc';
						break;
				}
				td.className.split(' ').each(function (c) {
					if (c.contains('_order')) {
						elementId = c.replace('_order', '').replace(/^\s+/g,'').replace(/\s+$/g,'');
					}
				});
				if (!elementId) {
					fconsole('woops didnt find the element id, cant order');
					return;
				}
				h.className = newOrderClass;
				this.fabrikNavOrder(elementId, orderdir);
				e.stop();
			}.bind(this));
		}.bind(this));
	},
	
	watchFilters: function(){
		var e = '';
		if (this.options.filterMethod != 'submitform') {
			$(this.options.form).getElements('.fabrik_filter').each(function(f){
				e = f.getTag() == 'select' ? 'change' : 'blur';
				f.removeEvents();
				f.addEvent(e, function(e){
					new Event(e).stop();
					if(!this.runPlugins('onFilterSubmit', e)){
						return false;
					}
					oPackage.submitfabrikTable(this.id, 'filter');
				}.bind(this));
			}.bind(this));
		}else{
			var f = $(this.options.form).getElement('.fabrik_filter_submit');
			if (f) {
				f.removeEvents();
				f.addEvent('click', function(e){
					if(!this.runPlugins('onFilterSubmit', e)){
						return false;
					}
					oPackage.submitfabrikTable(this.id, 'filter');
				}.bind(this));
			}
		}
		$(this.options.form).getElements('.fabrik_filter').removeEvents('keydown');
		$(this.options.form).getElements('.fabrik_filter').addEvent('keydown', function(e){
			e = new Event(e);
			if (e.code == 13) {
				e.stop();
				this.runPlugins('onFilterSubmit', e);
  			oPackage.submitfabrikTable(this.id, 'filter');
			}
		}.bind(this));
	},
  
  // highlight active row, deselect others
  setActive: function(activeTr){
    this.table.getElements('.fabrik_row').each(function(tr){
      tr.removeClass('activeRow');
    });
    activeTr.addClass('activeRow');
  },
  
  watchRows: function(){
    if(!this.table){
			return;
		}
    this.rows = this.table.getElements('.fabrik_row');
		this.links = this.table.getElements('.fabrik___rowlink');
    if (this.options.ajaxEditViewLink) {
      var view = '';
      if (this.options.canEdit == 1) {
        view = 'form';
      }
      else {
        if (this.options.canView == 1) {
          view = 'details';
        }
      }
      //TODO this isnt working when in CB plugin mode
      var editopts = {
        option: 'com_fabrik',
        'Itemid': this.options.Itemid,
        'view': view,
        'tableid': this.id,
        'fabrik': this.options.formid,
        'rowid': 0,
        'format': 'raw',
        '_senderBlock': 'table_' + this.id
      };
      this.links.each(function(link){
        link.addEvent('click', function(e){
          var tr = link.findUp('tr');
					this.setActive(tr);
          oPackage.startLoading();
          editopts.rowid = tr.id.replace('table_' + this.id + '_row_', '');
          var url = "index.php?" + Object.toQueryString(editopts);
          var myAjax = new Ajax(url, {
            method: 'get',
            onComplete: function(res){
              oPackage.sendMessage('table_' + this.id, 'update', 'ok', res);
            }.bind(this)
          });
          myAjax.request();
          e = new Event(e);
          e.stop();
        }.bind(this));
      }.bind(this));
    }
    
    //view details 
    
    this.links = this.table.getElements('.fabrik___viewrowlink');
    if (this.options.postMethod != 'post') {
      view =  'details';
      opts = {
        option: 'com_fabrik',
        'Itemid': this.options.Itemid,
        'view': view,
        'tableid': this.id,
        'fabrik': this.options.formid,
        'rowid': 0,
        'format': 'raw',
        '_senderBlock': 'table_' + this.id
      };
      this.links.each(function(link){
        link.addEvent('click', function(e){
          var tr = link.findUp('tr');
					this.setActive(tr);
          oPackage.startLoading();
          opts.rowid = tr.id.replace('table_' + this.id + '_row_', '');
          var url = "index.php?" + Object.toQueryString(opts);
          var myAjax = new Ajax(url, {
            method: 'get',
            onComplete: function(res){
              oPackage.sendMessage('table_' + this.id, 'update', 'ok', res);
            }.bind(this)
          });
          myAjax.request();
          e = new Event(e);
          e.stop();
        }.bind(this));
      }.bind(this));
    }
  },
  
  getForm: function(){
		if (!this.form) {
			this.form = $(this.options.form);
		}
  },
  
  submitfabrikTable: function (task) {
  	if (!this.runPlugins('onSubmitTable', null, task)){
  		return false;
  	}
    this.getForm();
		if (task == 'delete') {
			var ok = false;
			this.form.getElements('input[name^=ids]').each(function(c){
				if(c.checked){
					ok = true;
				}
			});
			if(!ok){
				alert(this.translate.select_rows);
				oPackage.stopLoading('table_' + this.id);
				return false;
			}
			if(!confirm(this.translate.confirmDelete)){
				oPackage.stopLoading('table_' + this.id);
				return;
			}
		}
    if (task == 'filter') {
    	if (this.filtering == true){
    		return false;
    	}
    	this.filtering = true;
			this.form.task.value = task;
      if (this.form['limitstart' + this.id]) {
        this.form.getElement('#limitstart' + this.id).value = 0;
      }
    }
    else {
      if (task !== '') {
        this.form.task.value = task;
      }
    }
    if (this.options.postMethod == 'ajax') {

	    //for module & mambot
			// $$$ rob with modules only set view/option if ajax on
			$('tableform_'+this.id).getElement('input[name=option]').value = 'com_fabrik';
			$('tableform_'+this.id).getElement('input[name=view]').value = 'table';

      $('table_' + this.id + '_format').value = 'raw';
       
      //oPackage.startLoading();
      if(this.options.mooversion > 1.1){
      	if(this.options.mooversion >= 1.24){
      		var formData = this.form.toQueryString().toObject();
      		var myFormRequest = new Ajax('index.php', {
      			data: formData,
      			onSuccess: function(json){
      				json = Json.evaluate(json.stripScripts());
      				oPackage.sendMessage('table_' + this.id, task, 'ok', json);
      			}.bind(this),
      			onFailure: function(e){
      				// $$$ rob issue with (perhaps sef404) where 404 header sent but body response text correct
      				json = Json.evaluate(e.responseText.stripScripts());
      				if($type(json) == 'object') {
      					oPackage.sendMessage('table_' + this.id, task, 'ok', json);
      				}else{
      					oPackage.stopLoading('table_' + this.id);
      				}
      			}.bind(this)
	        }).request(); 
      		window.fireEvent('fabrik.table.submit', [task, formData]);
      	}else{
	      	this.form.set('send', {onComplete: function(json){
	      		oPackage.sendMessage('table_' + this.id, task, 'ok', json);
	        }.bind(this)});
	      	this.form.send();
      	}
      	
      }else{
	      this.form.send({
	        onComplete: function(json){
	          oPackage.sendMessage('table_' + this.id, task, 'ok', json);
	        }.bind(this)
	      });
      }
    }
    else {
      this.form.submit();
    }
    //return (this.options.postMethod == 'ajax') ? false : true;
    return true;
  },
  
  fabrikNav: function(limitStart){
    this.form.getElement('#limitstart' + this.id).value = limitStart;
    // cant do filter as that resets limitstart to 0
    if(!this.runPlugins('onNavigate', null)){
    	return false;
    }
    oPackage.submitfabrikTable(this.id, 'navigate');
    return false;
  },
  
  fabrikNavOrder: function(orderby, orderdir){
  	this.form.orderby.value = orderby;
    this.form.orderdir.value = orderdir;
    if(!this.runPlugins('onOrder', null)){
    	return false;
    }
    window.fireEvent('fabrik.table.order', [orderby, orderdir]);
    oPackage.submitfabrikTable(this.id, 'order');
  },
  
  removeRows: function(rowids){
    //TODO: try to do this with FX.Elements
    for (i = 0; i < rowids.length; i++) {
      var row = $('table_' + this.id + '_row_' + rowids[i]);
      var highlight = new Fx.Styles(row, {
        duration: 1000
      });
      highlight.start({
        'backgroundColor': this.options.hightLight
      }).chain(function(){
        this.start({
          'opacity': 0
        });
      }).chain(function(){
        row.remove();
        this.checkEmpty();
      }.bind(this));
    }
  },
  
  editRow: function(){
  
  },
  
  clearRows: function(){
  	window.fireEvent('fabrik.table.clearrows');
  	this.table.getElements('.fabrik_row').each(function(tr){
      tr.remove();
    });
  },
  
  updateRows: function(data){
		if (data.id == this.id && data.model == 'table') {
			var header = this.table.getElements('.fabrik___heading, .fabrik___heading2').getLast();
			var headings = new Hash(data.headings);
			headings.each(function(data, key){
				key = "." + key + '_heading';
					try{
						if ($type(header.getElement(key)) !== false) {
						header.getElement(key).setHTML(data);
						}
					}catch(err){
						fconsole(err);
					}
			});
			
			this.clearRows();
			var counter = 0;
			var rowcounter = 0;
			trs = [];
			this.options.data = data.data;
			if(data.calculations){
				this.updateCals(data.calculations);
			}
			if ($type(this.form.getElement('.fabrikNav')) !== false) {
				this.form.getElement('.fabrikNav').setHTML(data.htmlnav);
			}
			this.setRowTemplate();
			// $$$ rob was $H(data.data) but that wasnt working ????
			//testing with $H back in again for grouped by data? Yeah works for grouped data!!
			var gdata = this.options.isGrouped ? $H(data.data) : data.data;
			gdata.each(function(groupData, groupKey){
				for(i=0;i<groupData.length;i++){
					if ($type(this.options.rowtemplate) == 'string') {
						var container =(!this.options.rowtemplate.match(/\<tr/)) ? 'div' : 'table';
						var thisrowtemplate = new Element(container);
		  			thisrowtemplate.setHTML(this.options.rowtemplate);
				  }else{
						container = this.options.rowtemplate.getTag() == 'tr' ? 'table' : 'div'; 
						var thisrowtemplate = new Element(container);
						// ie tmp fix for mt 1.2 setHTML on table issue
						thisrowtemplate.adopt(this.options.rowtemplate.clone());
					}
					var row = groupData[i];
					$H(row.data).each(function(val, key){
						var rowk = '.fabrik_row___' + key;
						var cell = thisrowtemplate.getElement(rowk);
						if ($type(cell) !== false) {
							cell.setHTML(val);
						}
						rowcounter ++;
          }.bind(this));
					//thisrowtemplate.getElement('.fabrik_row').id = 'table_' + this.id + '_row_' + row.get('__pk_val');
					thisrowtemplate.getElement('.fabrik_row').id = row.id;
					if ($type(this.options.rowtemplate) === 'string') {
						var c = thisrowtemplate.getElement('.fabrik_row').clone();
						c.id = row.id;
				  	c.injectInside(this.tbody);
				  }else{
						var r = thisrowtemplate.getElement('.fabrik_row');
						r.injectInside(this.tbody);
						thisrowtemplate.empty();
					}
					counter ++;
				}
      }.bind(this));
			
			var fabrikDataContainer = this.table.findClassUp('fabrikDataContainer');
			var emptyDataMessage = this.table.findClassUp('fabrikForm').getElement('.emptyDataMessage');
			if (rowcounter == 0) {
				if($type(fabrikDataContainer)!== false)
					fabrikDataContainer.setStyle('display', 'none');
				if($type(emptyDataMessage)!== false)
					emptyDataMessage.setStyle('display', '');	
			}else{
				if($type(fabrikDataContainer)!== false)
					fabrikDataContainer.setStyle('display', '');
				if($type(emptyDataMessage)!== false)
					emptyDataMessage.setStyle('display', 'none');	
			}
			
      this.watchAll();
      window.fireEvent('fabrik.table.updaterows');
      try{
				Slimbox.scanPage();
			}catch(err){
				fconsole('slimbox scan:'+err);
			}
			try{
				Mediabox.scanPage();
			}catch(err){
				fconsole('mediabox scan:'+err);
			}
			
			this.stripe();
			oPackage.stopLoading('table_' + this.id);
		}
  },
  
  addRow: function(obj){
    var r = new Element('tr', {
      'class': 'oddRow1'
    });
    var x = {
      test: 'hi'
    };
    for (var i in obj) {
      if (this.options.headings.indexOf(i) != -1) {
        var td = new Element('td', {}).appendText(obj[i]);
        r.appendChild(td);
      }
    }
    r.injectInside(this.tbody);
  },
  
  addRows: function(aData){
    for (i = 0; i < aData.length; i++) {
      for (j = 0; j < aData[i].length; j++) {
        this.addRow(aData[i][j]);
      }
    }
    this.stripe();
  },
  
  stripe: function(){
  	var trs = this.table.getElements('.fabrik_row');
    for (i = 0; i < trs.length; i++) {
      if (i !== 0) { // ignore heading
        var row = 'oddRow' + (i % 2);
        trs[i].addClass(row);
      }
    }
  },
  
  checkEmpty: function(){
  	var trs = this.table.getElements('tr');
    if (trs.length == 2) {
      this.addRow({
        'label': this.options.emptyMsg
      });
    }
  },
  
  watchCheckAll: function(e){
  	var checkAll = this.form.getElement('input[name=checkAll]');
    if ($type(checkAll) !== false) {
    	//IE wont fire an event on change until the checkbxo is blurred!
      checkAll.addEvent('click', function(e){
      	var event = new Event(e);
   	  	var c = $(event.target); 
        var chkBoxes = c.findClassUp('fabrikTable').getElements('input[name^=ids]');
				var c = !c.checked ? '' : 'checked';
        for (var i = 0; i < chkBoxes.length; i++) {
          chkBoxes[i].checked = c;
					this.toggleJoinKeysChx(chkBoxes[i]);
        }
        //event.stop(); dont event stop as this stops the checkbox being selected
      }.bind(this));
    }
		this.form.getElements('input[name^=ids]').each(function(i){
			i.addEvent('change', function(e){
				this.toggleJoinKeysChx(i);
			}.bind(this));
		}.bind(this));
  },
	
	toggleJoinKeysChx:function(i)
	{
		i.getParent().getElements('input[class=fabrik_joinedkey]').each(function(c){
			c.checked = i.checked;
		});
	},
  
  watchNav: function(e){
  	var limitBox = this.form.getElement('#limit'+ this.id);
    if (limitBox) {
    	limitBox.removeEvents();
      limitBox.addEvent('change', function(e){
      	if(!this.runPlugins('onLimit', e)){
      		return false;
      	}
        oPackage.submitfabrikTable(this.id, 'filter');
      }.bind(this));
    }
    var addRecord = $('table_' + this.id + '_addRecord');
    
    if ($(addRecord) && (this.options.ajaxEditViewLink != '0')) {
    	addRecord.removeEvents();
      addRecord.addEvent('click', function(e){
        e = new Event(e);
        oPackage.startLoading();
        oPackage.sendMessage('table_' + this.id, 'clearForm', 'ok', '');
        e.stop();
      }.bind(this));
    }
		
		if($('fabrik__swaptable')){
			$('fabrik__swaptable').addEvent('change', function(event){
				var e = new Event(event);
				var v = e.target;
				window.location = 'index.php?option=com_fabrik&c=table&task=viewTable&cid=' + v.get('value');
			}.bind(this));
		}
		if(this.options.postMethod != 'post'){
			if($type(this.form.getElement('.pagination')) !== false){
				this.form.getElement('.pagination').getElements('.pagenav').each(function(a){
					a.addEvent('click', function(e){
						new Event(e).stop();
						if(a.getTag() == 'a'){
							var o = a.href.toObject();
							this.fabrikNav(o['limitstart' + this.id]);
						}
					}.bind(this));
				}.bind(this));
			}
		}
    this.watchCheckAll();
		
		//clear filter list
	/*	var c = this.form.getElement('.clearFilters');
		if(c){
			c.removeEvents();
			c.addEvent('click', function(e){
				new Event(e).stop();
				this.form.getElements('.fabrik_filter').each(function(f){
					if(f.getTag() == 'select'){
						f.selectedIndex = 0;
					}else{
						f.value = '';
					}
				});
				oPackage.submitfabrikTable(this.id, 'filter');
			}.bind(this));
		}*/
  },
  
  //todo: refractor addlistento into block class 
  addListenTo: function(blockId){
    this.listenTo.push(blockId);
  },
  
  receiveMessage: function(senderBlock, task, taskStatus, data){
    if (this.listenTo.indexOf(senderBlock) != -1) {
      switch (task) {
        case 'delete':
          //this.removeRows(data);
          //this.stripe();
        	this.updateRows(data);
          break;
        case 'processForm':
          this.addRows(data);
          break;
        case 'navigate':
        case 'filter':
        case 'updateRows':
        case 'order':
        case 'doPlugin':
        	this.filtering = false;
        	//only update rows if no errors returned
        	if ($H(data.errors).keys().length === 0){
          	this.updateRows(data);
          }
          break;
      }
    }
  },
  /** currently only called from element raw view when using inline edit plugin
   *  might need to use for ajax nav as well?
   */
  updateCals : function(json){
  	var types = ['sums', 'avgs', 'count', 'medians'];
  	this.table.getElements('.fabrik_calculations').each(function(c){
  		types.each(function(type){
  			$H(json[type]).each(function(val, key){
	  			var target = c.getElement('.fabrik_row___'+key);
	  			if ($type(target) !== false) {
	  				target.setHTML(val);
	  			}
	  		});
  		});
  	});
  }
});

fabrikTable.implement(new Plugins);