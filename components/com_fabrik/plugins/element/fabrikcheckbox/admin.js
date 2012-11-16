var fabrikAdminCheckbox = new Class({
	initialize: function(options) {
		this.options = {};
		$extend(this.options, options);
		this.counter = 0;
		this.clickAddOption = this.addOption.bindAsEventListener(this);
		this.clickRemoveSubElement = this.removeSubElement.bindAsEventListener(this);
		$('addCheckbox').addEvent('click', this.clickAddOption);	
	},
	
	addOption: function(e) {
		this.addSubElement();
		var event = new Event(e);
		event.stop();
	},
	
	removeSubElement: function(e) {
		var event = new Event(e);
		var id = event.target.id.replace('chk_delete_', '');
		$('chk_content_' + id).remove();
		event.stop();
	},
	addSubElements: function(ar) {
		ar.each(function(a) {
			this.addSubElement(a[0], a[1], a[2]);
		}.bind(this))
	},
	
	addSubElement: function(sValue, sText, sChecked) {
	  sValue = sValue ? sValue : '';
		sText = sText ? sText : '';
	 	sCurChecked = sChecked ? "checked='" + sChecked + "'" : '';
		var chx = "<input class='inputbox chk_intial_selection' type='checkbox'  value='"+sValue+"' name='chk_intial_selection' id='chk_checked_"+this.counter+"' " + sCurChecked + " />";
		var li = new Element('li', {id: 'chk_content_'+ this.counter}).adopt([
	 		new Element('table',  {width:'100%'}).adopt([
	 			new Element('tbody').adopt([
	 				new Element('tr').adopt([
	 				  new Element('td', {'rowspan':2,'class':'handle chxhandle'}),
	 					
		       	new Element('td', {width:'30%'}).adopt(
	   					new Element('input', {'class':'inputbox chk_values', type:'text', name:'chk_values', id:'chk_value_'+this.counter, size:20, value:sValue})
	   				),
	   			
	    				new Element('td', {width:'30%'}).adopt(
	   					new Element('input', {'class':'inputbox chk_text', type:'text', name:'chk_text', id:'chk_text_'+this.counter, size:20, value:sText})
	   				),
	   				new Element('td', {width:'10%'}).setHTML(
	  	      		chx
	  	      		),
	   				new Element('td', {width:'20%'}).adopt(
	  	      		new Element('a', {'class':'removeButton',href:'#', id:'chk_delete_'+this.counter}).setText('Delete')
	  	      	)
	 				])
	 			])
	 		])
	 	]);
		if($('chk_subElementBody').getElement('li').innerHTML == '') {
	 		$('chk_subElementBody').getElement('li').replaceWith(li);
	 	}else{
	 		li.injectInside($('chk_subElementBody'));
	 	}
		$('chk_delete_'+this.counter).addEvent('click', this.clickRemoveSubElement);
		
		//@TODO: clone:true - the offset is wrong on dragged clone
		if(this.options.mooversion == 1.2) {
			if(!this.sortable) {
				this.sortable = new Sortables('chk_subElementBody', {'handle':'.chxhandle'});
			}else{
				this.sortable.addItems(li);
			}
		}else{
			this.sortable = new Sortables('chk_subElementBody', {'handles':$$('.chxhandle')});
		}
		this.counter++;
	},
	
	onSave:function() {
		var values = ''; 
		var text = ''; 
		var ret = true;
		var intial_selection = '';
		$$('.chk_values').each(function(dd) {
			if(dd.value == '') {
				alert("please ensure all sub element values are filled in");
				ret = false;
			}
			values += dd.value.replace('|', '') + '|';
		});
		$$('.chk_text').each(function(dd) {
			text += dd.value.replace('|', '') + '|';
		});
		var avals = values.split('|');
		$$('.chk_intial_selection').each(function(dd, c) {
			if(dd.checked) {
				intial_selection += avals[c] + '|';
			}else{
				intial_selection += '|';
			}
		});
		$('sub_values').value = values.substr(0, values.length-1);
		$('sub_labels').value = text.substr(0, text.length-1); 
		$('sub_intial_selection').value = intial_selection.substr(0, intial_selection.length-1);
		return ret;
	}
});