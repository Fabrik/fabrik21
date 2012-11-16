var MochaSearch = new Class({
	
	getOptions: function(){
		return {};
	},
			
  initialize: function(opts){
		this.setOptions(this.getOptions(), opts);
	},
	
	ini:function(){
    this.trs = $A([]);
    if ($('advanced-search-add')) {
      $('advanced-search-add').addEvent("click", this.addRow.bindAsEventListener(this));
      $('advancedFilterTable-clearall').addEvent("click", this.resetForm.bindAsEventListener(this));
      this.trs.each(function(tr){
        tr.injectAfter($('advanced-search-table').getElements('tr').getLast());
      }.bind(this));
    }
    this.watchDelete();
		this.watchElementList();
  },
  
  watchDelete: function(){
    $$('.advanced-search-remove-row').removeEvents();
    $$('.advanced-search-remove-row').addEvent('click', this.removeRow.bindAsEventListener(this));
  },
	
	watchElementList:function(){
		$$('select.key').removeEvents();
		$$('select.key').addEvent('change', this.updateValueInput.bindAsEventListener(this));
	},
	
	/**
	 * called when you choose an element from the filter dropdown list
	 * should run ajax query that updates value field to correspond with selected
	 * element
	 * @param {Object} e event
	 */
	updateValueInput:function(e){
		e = new Event(e).stop();
		var v = $(e.target).get('value');
		
		var update = $(e.target).getParent().getParent().getElements('td')[3];
		if (v == ''){
			update.setHTML('');
			return;
		}
		var url = this.options.liveSite + "/index.php?option=com_fabrik&view=table&task=elementFilter&format=raw";
		var eldata = this.options.elementMap[v];
		new Ajax(url, {'update':update, 'evalScripts':true, 'data':{'element':v, 'id':this.options.tableid, 'elid':eldata.id, 'plugin':eldata.plugin, 'counter':this.options.counter}}).request();
	},
  
  addRow: function(e){
		this.options.counter ++;
  	new Event(e).stop();
    var tr = $('advanced-search-table').getElement('tbody').getElements('tr').getLast();
    var clone = tr.clone();
    clone.injectAfter(tr);
		clone.getElement('td').empty().setHTML(this.options.conditionList);
		
		var tds = clone.getElements('td');
		tds[1].empty().setHTML(this.options.elementList);
		tds[1].adopt([
			new Element('input', {'type':'hidden','name':'fabrik___filter[table_'+this.options.tableid+'][search_type][]','value':'advanced'}),
			new Element('input', {'type':'hidden','name':'fabrik___filter[table_'+this.options.tableid+'][grouped_to_previous][]','value':'0'})
		]);
		tds[2].empty().setHTML(this.options.statementList);
		tds[3].empty();
		tds[4].empty().adopt(
			new Element('a', {'class':'advanced-search-remove-row','href':'#'}).adopt(
				new Element('img', {'src':this.options.liveSite + '/media/com_fabrik/images/del.png', 'alt':'[-]'})
			)
		);
    this.watchDelete();
		this.watchElementList();
  },
  
  removeRow: function(event){
    var e = new Event(event);
    e.stop();
    if ($$('.advanced-search-remove-row').length > 1) {
			this.options.counter --;
      var tr = e.target.findUp('tr');
      var fx = new Fx.Styles(tr, {
        duration: 800,
        transition: Fx.Transitions.Quart.easeOut,
        onComplete: function(){
          tr.remove();
        }
      });
      fx.start({
        'height': 0,
        'opacity': 0
      });
    }
  },
  
  resetForm: function(){
    var table = $('advanced-search-table');
    if(!table){
    	return;
    }
    $('advanced-search-table').getElements('tbody tr').each(function(tr, i){
	    if(i > 1){
	    	tr.remove();
	    }
	    if(i == 0){
	    	tr.getElements('.inputbox').each(function(dd){dd.selectedIndex = 0;});
				tr.getElements('input').each(function(i){i.value = '';});
	    }
    });
    this.watchDelete();
		this.watchElementList();
  },

  deleteFilterOption: function(e){
    var event = new Event(e);
    var element = event.target;
    $(element.id).removeEvent("click", this.deleteFilterOption.bindAsEventListener(this));
    var tr = element.parentNode.parentNode;
    var table = tr.parentNode;
    table.removeChild(tr);
    event.stop();
  }
 
});

MochaSearch.implement(new Events);
MochaSearch.implement(new Options);


