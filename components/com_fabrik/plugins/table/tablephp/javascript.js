var fbTableRunPHP = FbTablePlugin.extend({
	initialize: function(tableform, options, lang) {
		this.setOptions(tableform, options);
		this.lang = Object.extend({'selectrow':'Please select a row!'}, lang || {});
		window.addEvent('domready', function() {
			var t = this.tableform.getElement('input[name=tableid]');
			if($type(t) === false) {
				return;
			}
			this.tableid = t.value;
			this.watchButton();
		}.bind(this));
	},
	
	
	watchButton:function() {
		var button = this.tableform.getElement('input[name='+this.options.name+']');
		if(!button) {
			return;
		}
		button.addEvent('click', function(event) {
			var e = new Event(event);
			e.stop();
			var ok = false;
			var additional_data = this.options.additional_data;
			var hdata = $H({});
			this.tableform.getElements('input[name^=ids]').each(function(c) {
				if(c.checked) {
					ok = true;
					if (additional_data) {
						var row_index = c.name.match(/ids\[(\d+)\]/)[1];
						if (!hdata.has(row_index)) {
							hdata.set(row_index, $H({}));
						}
						hdata[row_index]['rowid'] = c.value;
						additional_data.split(',').each(function(elname){
							var cell_data = c.findClassUp('fabrik_row').getElements('td.fabrik_row___' + elname)[0].innerHTML;
							hdata[row_index][elname] = cell_data;
						});
					}
				}
			});
			if(!ok) {
				alert('Please select a row!');
				return;
			}
			if (additional_data) {
				this.tableform.getElement('input[name=fabrik_tableplugin_options]').value = Json.encode(hdata);
			}
			this.tableform.getElement('input[name=fabrik_tableplugin_name]').value = 'tablephp';
			this.tableform.getElement('input[name=fabrik_tableplugin_renderOrder]').value = button.name.replace('tablephp-', '');
			oPackage.submitfabrikTable(this.tableid, 'doPlugin');
		}.bind(this));
	}
});