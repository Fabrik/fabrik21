var fbPicklist = FbElement.extend({
	initialize: function(element, options, lang) {
	
		this.plugin = 'fabrikpicklist';
		this.lang = {
			'dropstuff': 'Drop item here',
			please_enter_value:'Please enter a value and/or label'
		};
		$extend(this.lang,lang);
    this.setOptions(element, options);
    if(this.options.allowadd === true) {
    	this.watchAdd();
    }
    if(this.options.mooversion < 1.2) {
	    alert('picklist only works with mootools 1.2 +, please update Fabriks parameters to use mootools 1.2');
	    return;
    }
    if (this.options.editable === false) {
    	return;
    }
    //hovercolor: this.options.bghovercolour,
    var dropcolour = $(this.options.element + '_fromlist').getStyle('background-color');
    var hovercolour = this.options.bghovercolour;
    var drops = [$(this.element.id+'_fromlist'), $(this.element.id+'_tolist')];
    drops.each(function(drop){
	    drop.getElements('li').addEvents({
				'mouseenter':function(e){
					e.target.store('origbg', e.target.getStyle('background-color'));
					e.target.setAttribute('style', 'background-color:'+this.options.hovercolour+ "! important");

				}.bind(this),
				'mouseleave':function(e){
					e.target.setAttribute('style', 'background-color:'+e.target.retrieve('origbg')+ "! important");
				}.bind(this)    	
	    });	
    }.bind(this));
    
	 this.sortable = new Sortables(drops, 
			{
				clone: true, 
				revert: true, 
				opacity:0.7,
				onComplete: function() 
					{
					drops.each(function(drop){
						drop.tween('background-color', dropcolour);
						drop.removeEvents('mouseenter');
						drop.removeEvents('mouseleave');
					});
					this.setData();
					}.bind(this),
				onStart:function(element, clone)
					{
						clone.setStyles({'border':'1px dotted'});
						drops.each(function(drop){
							drop.addEvent('mouseenter', function(e){
								drop.tween('background-color', hovercolour);
							}.bind(this))
							
							drop.addEvent('mouseleave', function(e){
								drop.tween('background-color', dropcolour);
							}.bind(this));
						
						}.bind(this));
						
						
					}.bind(this)
			});
    },
    
    setData: function()
		{
			var empty_msg = $(this.options.element + '_tolist').getElement('.emptyplicklist');
			if (empty_msg) {
				empty_msg.dispose();
			}
			var v = $(this.options.element + '_tolist').getElements('li').map(function(item, index) {
	 			return item.id.replace(this.options.element + '_value_', '');
			}.bind(this));
			this.element.value = v.join(this.options.splitter);
			this.element.fireEvent('change', new Event.Mock(this.element));
		},
    
    watchAdd: function()
    {
    	var id = this.element.id;
    	if(!$(this.element.id + '_dd_add_entry')) {
    		return;
    	}
    	$(this.element.id + '_dd_add_entry').addEvent( 'click', function(event) {
				var label = $(id + '_ddLabel').value;
				if ($(id + '_ddVal')) {
					var val = $(id + '_ddVal').value;
				}else{
					val = label;
				}
				if (val === '' || label === '') {
					alert(this.lang.please_enter_value);
				}else {
				
				var li = new Element('li', {
						'class': 'picklist',
						'id': this.element.id + '_value_' + val,
						'events':{
							'mouseover':function(e){
								li.store('orgibg', li.getStyle('background-color'));
								li.setStyle('background-color', this.options.hovercolour)
							}.bind(this),
							'mouseleave':function(e){
								li.setStyle('background-color', li.retrieve('origbg'));
							}.bind(this)
						}
					}).appendText(label);
					
					$(this.element.id + '_tolist').adopt(li);
					this.sortable.addItems(li);
					
					var e = new Event(event).stop();
					if ($(id + '_ddVal')) {
						$(id + '_ddVal').value = '';
					}
					$(id + '_ddLabel').value = '';
					//this.showEmptyMsg($(this.options.element + '_tolist'));
					this.setData();
				}
			}.bind(this));
    }
});
