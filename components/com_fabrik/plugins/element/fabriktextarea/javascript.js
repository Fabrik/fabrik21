var fbTextarea = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabriktextarea';
		this.elementtype_id = 'fabrikDisplayText';
		this.setOptions(element, options);
		(function() {
			this.getTextContainer();
			this.watchTextContainer();
		}.bind(this)).delay(500);
		
	},
	
	watchTextContainer: function()
	{
		if($type(this.element) === false) {
			this.element = $(this.options.element);
		}
		if($type(this.element) === false) {
			//can occur when element is part of hidden first group
			return;
		}
		if(this.options.editable == true) {
			if (this.element.findClassUp('fabrikElementContainer') === false || $type(this.element.findClassUp('fabrikElementContainer'))== false) {
				fconsole('no fabrikElementContainer class found for textarea');
				return;
			}
			var element = this.element.findClassUp('fabrikElementContainer').getElement('.fabrik_characters_left');
			
			if($type(element) !== false) {
				this.warningFX = element.effects({duration: 1000, transition: Fx.Transitions.Quart.easeOut});
				this.origCol = element.getStyle('color');
				if (this.options.wysiwyg) {
					var eventHandler = this.informKeyPress.bindAsEventListener(this);
					if (this.options.tinymce != 3) {
			  		tinyMCE.addEvent(this.container, "keyup", eventHandler);
				  } else {
				  	tinymce.dom.Event.add(this.container, 'keyup', eventHandler);
				  }
				}else{
					this.container.addEvent('keyup', function(e) {
						this.informKeyPress();
					}.bind(this));
				}
			}
		}
	},
	
	cloned: function(c) {
		//c is the repeat group count
		this.renewChangeEvents();
		this.getTextContainer();
		this.watchTextContainer();
	},
	
	getTextContainer: function()
	{
		var instance;
		if (this.options.wysiwyg && typeof(tinyMCE) !== 'undefined') {
			if (this.options.tinymce != 3) {
	  		instance = tinyMCE.getInstanceById(this.options.element);
		  } else {
		 	 instance = tinyMCE.get(this.options.element);
		  }
			if(instance) {
				this.container = instance.getDoc();
			} else {
				fconsole('didnt find wysiwyg edtor ...' + this.options.element);
			}
		} else {
			this.container = this.element; 
		}
	},
	
	getValue:function()
	{
		return this.getContent();
	},
	
	getContent:function()
	{
		if (this.options.wysiwyg) {
			if (this.options.tinymce != 3) {
		  	return tinyMCE.getContent().replace(/<\/?[^>]+(>|$)/g, "");
		  } else {
			  var editor = tinyMCE.getInstanceById(this.element.id);
			  if ($type(editor) !== false) {
				  return editor.getContent().replace(/<\/?[^>]+(>|$)/g, "");
			  }
		  }
		}else{
			this.getTextContainer();
			return this.container.value;
		}
	},
	
	setContent: function(c)
	{
		if (this.options.wysiwyg) {
			try{
				return tinyMCE.setContent(c);
			}catch(er){
				var e = tinyMCE.getInstanceById(this.element.id);
				return e.setContent(c);
			}
			
		}else{
			this.getTextContainer();
			if($type(this.container) !== false) {
				this.container.value = c;
			}
		}
		return null;
	},
	
	informKeyPress: function()
	{
		var charsleftEl = this.element.findClassUp('fabrikElementContainer').getElement('.fabrik_characters_left');
		var content = this.getContent();
		var charsLeft =  this.options.max - (content.length + 1);
		if(charsLeft < 0) {
			if (this.options.deleteOverflow) {
				this.setContent( content.substring(0,this.options.max));
				charsLeft = 0;
			}
			
			this.warningFX.start({'opacity':0, 'color':'#FF0000'}).chain(function() {
				this.start({'opacity':1, 'color':'#FF0000'}).chain(function() {
				this.start( {'opacity':0,'color':this.origCol}).chain(function() {
					this.start({'opacity':1});
				});
			});
		});
		}else{
			charsleftEl.setStyle('color',this.origCol);
		}
		charsleftEl.getElement('span').setHTML(charsLeft);
	},
	
	reset: function()
	{
		this.update(this.options.defaultVal);
	},
	
	update: function(val) {
		this.getTextContainer();
		if (!this.options.editable) {
			this.element.innerHTML = val;
			return;
		}
		this.setContent(val);
	}
});