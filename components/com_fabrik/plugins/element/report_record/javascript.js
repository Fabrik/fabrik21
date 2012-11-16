FbReportRecord = FbElement.extend( {
	
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'report_record';
		this.setOptions(element, options);
		this.spinner = new Asset.image(this.options.livesite+'media/com_fabrik/images/ajax-loader.gif', {	'alt':'loading','class':'ajax-loader'});
		this.watchButtons();
	},
	
	report: function(e) {
		new Event(e).stop();
		var tableId = this.form.getForm().getElement('input[name=tableid]').get('value');
		var rowid = this.form.getForm().getElement('input[name=rowid]').get('value');
		var url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw&controller=plugin&g=element&task=pluginAjax&plugin=report_record&method=report&tableid=' + tableId + '&fabrik=' + this.form.id+'&rowid='+rowid+'&elid='+this.options.elid;
		
		this.spinner.injectAfter(this.element);
		new Ajax(
	       url, 
	        {
	      	 evalScripts:true,
					onComplete: function(json) {
						this.update(json);
					}.bind(this)
		   }).request();
	},
	
	update:function(json) {
		alert(json);
		this.spinner.remove();
	
	},
	
	watchButtons: function() {
		this.element.addEvent('click', this.report.bindAsEventListener(this));
	}
});