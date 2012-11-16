var fabrikAdminPicklist = new Class({
	initialize: function(opts) {
		this.counter = 0;
		this.options = opts;
		this.clickAddOption = this.addOption.bindAsEventListener(this);
		this.clickRemoveSubElement = this.removeSubElement.bindAsEventListener(this);
		$('addPickList').addEvent('click', this.clickAddOption);
	},
	
	addOption: function(e) {
		this.addSubElement();
		var event = new Event(e);
		event.stop();
	},
	
	removeSubElement: function(e) {
		var event = new Event(e);
		var id = event.target.id.replace('deletePicklistSubElements_', '');
		$('picklist_content_' + id).remove();
		event.stop();
	},
	
	addSubElements: function(ar) {
		ar.each(function(a) {
			this.addSubElement(a[0], a[1], a[2]);
		}.bind(this));
	},
	
	addSubElement: function(sValue, sText, sChecked) {
    sValue = sValue ? sValue : '';
    sText = sText ? sText : '';
		rExp = /\"/gi;
    var selVal = $$('.picklist_intial_selection').length + 1;     	
   	var sCurChecked = sChecked ? "checked='" + sChecked + "'" : '';
		var chx = "<input class='inputbox picklist_intial_selection' type='checkbox'  value='"+selVal+"' name='picklist_intial_selection' id='picklist_checked_"+this.counter+"' " + sCurChecked + " />";
		
   	var t = new Element('table', {name:'contentArea', id: 'picklist_content_'+ this.counter }).adopt(
    new Element('tbody').adopt(
     	[
     	new Element('tr').adopt(
     		new Element('td', {'class':'picklist_orderhandle', 'colspan':5, 'styles':
     		{'background-color':'#E4E4E4','cursor':'move','height':'15px'}})
     	),
     	new Element('tr').adopt(
        [
          new Element('td', {width:'25%'}).adopt(
         		 new Element('label', {'for':'picklist_value_'+this.counter}).appendText('Value')
         	),
         	new Element('td', {width:'25%'}).adopt(
       			new Element('input', {'class':'inputbox picklist_values', type:'text', name:'picklist_values', id:'picklist_value_'+this.counter, size:20, value:sValue})
       		),
       		 new Element('td', {width:'25%'}).adopt(
         		 new Element('label', {'for':'picklist_text_'+this.counter}).appendText('Label')
         	),
         	new Element('td', {width:'25%'}).adopt(
       			new Element('input', {'class':'inputbox picklist_text', type:'text', name:'picklist_text', id:'picklist_text_'+this.counter, size:20, value:sText})
       		)
        ]
      ),
		 
      new Element('tr').adopt(
        [
          new Element('td', {width:'20%'}).adopt(
         		 new Element('label', {'for':'picklist_checked_'+this.counter}).appendText('Selected as default:')
         	),
         	new Element('td', {width:'80%'}).setHTML(
       		chx
       		),
       		new Element('td', {width:'20%', colspan:'2'}).adopt(
      		new Element('a', {'class':'removeButton',href:'#', id:'deletePicklistSubElements_'+this.counter}).appendText('Delete')
      	)
        ]
      )
     	]
   	)
   	)
		$('picklist_subElementBody').appendChild(t);
		$('deletePicklistSubElements_'+this.counter).addEvent('click', this.clickRemoveSubElement);
		this.counter++;
		new Sortables('picklist_subElementBody', {'handles':$$('.picklist_orderhandle')});
	},
	
	onSave:function() {
		var values = []; 
		var text = []; 
		var intial_selection = [];
		$$('.picklist_values').each(function(dd) {
			values.push(dd.value.replace(this.options.splitter, ''));//+ this.options.splitter;
		}.bind(this));
		
		$$('.picklist_text').each(function(dd) {
			text.push(dd.value.replace(this.options.splitter, '')); //+ this.options.splitter;
		}.bind(this));

		$$('.picklist_intial_selection').each(function(dd, c) {
			if(dd.checked) {
				intial_selection.push(values[c]); //+ this.options.splitter;
			}else{
				intial_selection.push('');// this.options.splitter;
			}
		}.bind(this));
		
		$('sub_values').value = values.join(this.options.splitter)
		$('sub_labels').value = text.join(this.options.splitter)
		$('sub_intial_selection').value = intial_selection.join(this.options.splitter);
		return true;
	}
});