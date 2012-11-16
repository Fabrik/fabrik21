/**
 * @author Robert
 */
var Autofill =  new Class({
	
	getOptions:function() {
		return {
			'observe':'',
			'trigger':'',
			cnn:0,
			table:0,
			map:'',
			editOrig:false,
			confirm: true
		};
	},
	
	initialize: function(options, lang) {
		this.setOptions(this.getOptions(), options);
		this.lang = Object.extend({
			'doupdate':'Do you want to search for an existing record that matches the data you have just entered?',
			'searching':'performing search',
			'norecordsfound':'no records found'
		}, arguments[1] || {});
	
		(function() {this.setUp();}.bind(this)).delay(1000);
	},
	
	setUp: function()
	{
		try{
  		this.form = eval('form_' + this.options.formid);
  	}catch(err) {
			//form_x not found (detailed view perhaps)
  		return;
  	}
		var evnt = this.lookUp.bind(this);
		this.element = this.form.formElements.get(this.options.observe);
		//if its a joined element
		if (!this.element) {
			var regex = new RegExp (this.options.observe);
			var k = this.form.formElements.getKeys();
			
			var ii = k.each(function(i){
			if(i.test(regex)){
				this.element = this.form.formElements.get(i);
			}
			}.bind(this));
		}
		if (this.options.trigger == '') {
			var elEvnt = $(this.options.observe).getTag() == 'select' ? 'change' : 'blur';
			this.form.dispatchEvent('', this.options.observe, elEvnt, evnt);
		}else{
			this.form.dispatchEvent('', this.options.trigger, 'click', evnt);
		}
	},
	
	// perform ajax lookup when the observer element is blurred
	
	lookUp: function() {
		
		if(this.options.confirm && !confirm(this.lang.doupdate)) {
			return;
		}
		oPackage.startLoading('form_' + this.options.formid, this.lang.searching);
		
		var v = this.element.getValue();
		var formid = this.options.formid;
		var observe = this.options.observe;
		var url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax';
		
		var myAjax = new Ajax(url, { method:'post', 
		'evalscripts':true,
		'data':{
			'plugin':'fabrikautofill',
			'method':'ajax_getAutoFill',
			'g':'form',
			'v':v, 
			'formid':formid,
			'observe':observe,
			'cnn':this.options.cnn,
			'table':this.options.table,
			'map':this.options.map
			},
		onComplete: function(json) {
			// $$$ rob 22/02/2011 need to specify the block to stop loading
			oPackage.stopLoading('form_' + this.options.formid);
			this.updateForm(json.stripScripts());
		}.bind(this)}).request();
	},
	
	//update the form from the ajax request returned data
	updateForm:function(json) {
		json = $H(Json.evaluate(json));
		if(json.length == 0) {
			alert(this.lang.norecordsfound);
		}
		json.each(function(val, key) {
			var k2 = key.substring(key.length - 4, key.length);
			if(k2 == '_raw') {
				key = key.replace('_raw', '');
				var el = this.form.formElements.get(key);
				if ($type(el) !== false) {
					el.update(val);
				}
			}
		}.bind(this));
		if (this.options.editOrig === true) {
			this.form.getForm().getElement('input[name=rowid]').value = json.__pk_val;
		}
	}
	
});

Autofill.implement(new Events);
Autofill.implement(new Options);