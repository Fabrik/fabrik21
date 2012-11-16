var adminCCK = new Class({

	initialize : function(options) {

		this.options = {};
		$extend(this.options, options);

		this.form = oPackage.blocks.get('form_' + this.options.formid);

		new Element('fieldset').adopt([ new Element('legend').setText('CCK'), new Element('table', {
			'class' : 'paramlist admintable'
		}).adopt(new Element('tbody').adopt([ new Element('tr').adopt([ new Element('td', {
			'class' : 'paramlist_key'
		}).adopt(new Element('label', {
			'for' : 'template'
		}).setHTML('template: ')), new Element('td', {
			'class' : 'paramlist_value'
		}).setHTML(this.options.tmplList) ]), new Element('tr').adopt([ new Element('td', {
			'class' : 'paramlist_key'
		}).setText('view: '), new Element('td', {
			'class' : 'paramlist_value'
		}).setHTML(this.options.viewList) ]) ])) ]).injectBefore($('form_' + this.options.formid));

		oPackage.addBlock('cck', this);
		this.form.options.postMethod = 'ajax';
		// get the form to emulate being in a module
		document.getElement('input[name=_packageId]').value = '-1';
	},

	insertTag : function(json) {
		var tmpl = $('fabrik_cck_template').get('value');
		var view = document.getElements('input[name=fabrik_cck_view]').filter(function(v) {
			return v.checked;
		});
		view = view.length === 0 ? 'form' : view[0].get('value');
		var tag = "{fabrik view=" + view + " id=" + this.options.formid + " rowid=" + json.rowid + " layout=" + tmpl + "}";
		window.parent.jInsertEditorText(tag, this.options.ename);
		window.parent.document.getElementById('sbox-window').close();
	},

	receiveMessage : function(senderBlock, task, taskStatus, json) {
		this.insertTag(json);
	}
});
