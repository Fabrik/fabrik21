var SliderField = new Class({
	initialize : function(field, slider) {
		this.field = $(field);
		this.slider = slider;
		this.eventChange = this.update.bindAsEventListener(this);
		this.field.addEvent("change", this.eventChange);
	},

	destroy : function() {
		this.field.removeEvent("change", this.eventChange);
	},

	update : function() {
		if (!this.options.editable) {
			this.element.innerHTML = val;
			return;
		}
		this.slider.set(this.field.value.toInt());
	}
});

var ColourPicker = FbElement.extend( {

	initialize : function(element, options) {
		this.plugin = 'fabrikcolourpicker';
		this.setOptions(element, options);
		this.options = $extend( {
			liveSite : '',
			closeImage : 'components/com_fabrik/plugins/element/fabrikcolourpicker/images/close.gif',
			handleImage : 'components/com_fabrik/plugins/element/fabrikcolourpicker/images/handle.gif',
			trackImage : 'components/com_fabrik/plugins/element/fabrikcolourpicker/images/track.gif',
			red : 0,
			green : 0,
			blue : 0
		}, this.options)

		this.options.closeImage = this.options.liveSite + this.options.closeImage;
		this.options.handleImage = this.options.liveSite + this.options.handleImage;
		this.options.trackImage = this.options.liveSite + this.options.trackImage;
		this.element = $(element);
		this.widget = this.element.findClassUp('fabrikSubElementContainer').getElement('.colourpicker-widget');
		this.setOutputs();
		this.redField = null;
		this.showSwatch = true;
		this.showCloseButton = true;
		this.showCloseIcon = true;
		// create the table to hold the scroller
		this.table = new Element('table', {
			'styles' : {
				'float' : 'right','margin-right':'2px'
			}
		});
		this.tbody = new Element('tbody');
		var aColours = ['red', 'green', 'blue'];
		
		if (this.showCloseIcon) {
			var closeIcon = this.createCloseIcon(element);
			this.widget.appendChild(closeIcon);
		}else{
			new Element('div', {'class':'handle','styles':{'width':'375px','text-align':'right','clear':'both'}}).injectInside(this.widget);
		}
		if (this.showSwatch) {
			this.createColourSwatch(element);
		}

		this.createColourSlideHTML(element, 'red', 'Red:', this.options.red);
		this.createColourSlideHTML(element, 'green', 'Green:', this.options.green);
		this.createColourSlideHTML(element, 'blue', 'Blue:', this.options.blue);
		this.table.appendChild(this.tbody);
		this.widget.appendChild(this.table);
		this.sliderRefs = [];
		for (var i = 0; i < aColours.length; i++) {
			var col = aColours[i];
			var opts = {
				steps : 255,
				'color' : col,
				max : 255,
				offset : 1,
				onChange : function(pos) {
					window.fireEvent('fabrik.colourpicker.slider', [this, this.options.color, pos])
				}
			};
			this.sliderRefs.push(element + col + 'track');
			this[col + "Slider"] = new Slider($(element + col + 'track'), $(element + col + 'handle'), opts);
		}
		window.addEvent('fabrik.colourpicker.slider', function(o, col, pos){
			if(this.sliderRefs.contains(o.element.id)) {
				this.options.colour[col] = pos;
				this.update(this.options.colour.red+','+this.options.colour.green+','+this.options.colour.blue);
			}
			
		}.bind(this));
		this.widget.hide();
		// this makes the class update when someone enters a value into
		this.redField.addEvent("change", this.updateFromField.bindAsEventListener(this, ['red']));
		this.greenField.addEvent("change", this.updateFromField.bindAsEventListener(this, ['green']));
		this.blueField.addEvent("change", this.updateFromField.bindAsEventListener(this, ['blue']));

		if (this.showCloseButton) {
			var closeButton = this.createCloseButton(element, 'Close');
			this.widget.appendChild(closeButton);
		}
		var d = new Drag.Move(this.widget, {'handle':this.widget.getElement('.handle')});
		this.update(this.options.value);
	},

	createColourSwatch : function(element) {
		var j;
		this.fUpdateFromSwatch = this.updateFromSwatch.bindAsEventListener(this);
		var swatchDiv = new Element('div', {
			'styles' : {
				'float' : 'left',
				'margin-left':'5px',
				'class' : 'swatchBackground'
			}
		});

		for (var i = 0; i < this.options.swatch.length; i++) {
			var swatchLine = new Element('div', {
				'styles' : {
					'width' : '160px'
				}
			});
			var line = this.options.swatch[i];
			j = 0;
			$H(line).each(function(colname, colour){
				var swatchId = element + 'swatch-' + i + '-' + j;
				swatchLine.adopt(new Element('div', {
					'id' : swatchId,
					'styles' : {
						'float' : 'left',
						'width' : '10px',
						'cursor' : 'crosshair',
						'height' : '10px',
						'background-color' : 'rgb(' + colour + ')'
					},
					'class':colname,
					'events':{
						'click':this.fUpdateFromSwatch,
						'mouseenter':this.showColourName.bindAsEventListener(this),
						'mouseleave':this.clearColourName.bindAsEventListener(this)
					}
				}))
				j++;
			}.bind(this));
			
			swatchDiv.adopt(swatchLine);
		}
		this.widget.adopt(swatchDiv);
	},

	updateFromSwatch : function(event) {
		var e = new Event(event).stop();
		var sColour = e.target.style.background;
		var c = new Color(e.target.getStyle('background-color'));
		this.options.colour.red = c[0];
		this.options.colour.green = c[1];
		this.options.colour.blue = c[2];
		this.showColourName(event);
		this.updateAll(this.options.colour.red, this.options.colour.green, this.options.colour.blue);
	},
	
	showColourName:function(e){
		var e = new Event(e);
		this.colourName = e.target.className;
		e.target.findClassUp('colourpicker-widget').getElement('.colourName').setText(this.colourName);
	},
	
	clearColourName:function(e){
		e.target.findClassUp('colourpicker-widget').getElement('.colourName').setText('');
	},

	updateOutputs : function() {
		var c = new Color([this.options.colour.red, this.options.colour.green, this.options.colour.blue]);
		this.outputs['backgrounds'].each( function(output) {
			output.setStyle('background-color', c);
		});
		this.outputs['foregrounds'].each( function(output) {
			output.setStyle('background-color', c);
		});
		this.element.value = c.join(',');
	},

	update : function(val) {
		if (this.options.editable == false) {
			this.element.innerHTML = val;
			return;
		}
		if ($type(val) === false) {
			val = [0, 0, 0];
		} else {
			val = val.split(",");
		}
		this.updateAll(val[0], val[1], val[2]);
	},

	updateAll : function(red, green, blue) {
		red = red ? red.toInt() : 0;
		green = green ? green.toInt() : 0;
		blue = blue ? blue.toInt() : 0;
		this.redSlider.set(red);
		this.redField.value = red;
		this.options.colour.red = red;
		this.greenSlider.set(green);
		this.greenField.value = green;
		this.options.green = green;
		this.blueSlider.set(blue);
		this.blueField.value = blue;
		this.options.blue = blue;
		this.updateOutputs();
	},

	setOutputs : function(output) {
		this.outputs = {};
		this.outputs.backgrounds = (this.element.findClassUp('fabrikElementContainer').getElements('.colourpicker_bgoutput'));
		this.outputs.foregrounds = (this.element.findClassUp('fabrikElementContainer').getElements('.colourpicker_output'));
		this.outputs.backgrounds.each(function(i){
			i.addEvent('click', this.toggleWidget.bindAsEventListener(this));
		}.bind(this));
		this.outputs.foregrounds.each(function(i){
			i.addEvent('click', this.toggleWidget.bindAsEventListener(this));
		}.bind(this));
	},
	
	toggleWidget: function(e){
		new Event(e).stop();
		this.widget.toggle();
	},
	
	updateFromField:function(evt, col){
		var val = $(evt.target).value.toInt();
		if (isNaN(val)) {
			val = 0;
		} else {
			this.options.colour[col] = val;
			this.update(this.options.colour.red+','+this.options.colour.green+','+this.options.colour.blue);
		}
	},

	createCloseButton : function(picker, sClose) {
		var div = new Element('div', {
			'styles' : {
				'width' : '375px',
				'text-align' : 'right',
				'clear' : 'right'
			}
		});
		div.adopt(new Element('span', {'class':'colourName', 'styles':{'padding-right':'20px'}}));
		picker = $(picker);
		var input = new Element('input', {
			'class' : 'button',
			'value' : sClose,
			'type' : 'button',
			'events' : {
				'click' : function() {
					this.widget.toggle();
				}.bind(this)
			}
		});
		div.appendChild(input);
		return div;
	},

	createCloseIcon : function(picker) {
		var div = new Element('div', {'class':'handle',
			'styles' : {
				'margin' : '0 0 5px 0',
				'padding':'5px',
				'background-color':'#333333',
				'cursor':'move',
				'text-align' : 'right',
				'clear' : 'both'
			}
		}).adopt(new Element('img', {
			'src' : this.options.closeImage,
			'styles' : {
				'cursor' : 'pointer'
			},
			'events' : {
				'click' : function(e) {
					e = new Event(e)
					$(e.target).getParent().parentNode.setStyle('display', 'none');
					e.stop();
				}
			}
		}));
		return div;
	},

	createColourSlideHTML : function(element, colour, label, value) {

		var Track = new Element('div', {
			'id' : element + colour + 'track',
			'styles' : {
				'height' : '5px',
				'width' : '123px'
			}
		}).adopt(new Element('img', {
			'src' : this.options.trackImage,
			'width' : '123px',
			'height' : '4px'
		}));

		var sliderDiv = new Element('div', {
			'id' : element + colour + 'handle',
			'styles' : {
				'width' : '11px',
				'height' : '21px',
				'top' : '-15px'
			}
		}).adopt(new Element('img', {
			'src' : this.options.handleImage,
			'width' : '11px',
			'height' : '21px'
		}));

		var sliderField = new Element('input', {
			'type' : 'text',
			'id' : element + colour + 'redField',
			'size' : '3',
			'class' : 'input ' + colour+'SliderField',
			'value' : value
		});

		var tr1 = new Element('tr').adopt([new Element('td').appendText(label), new Element('td').adopt([Track.adopt(sliderDiv)]),
				new Element('td').adopt(sliderField) ]);

		this.tbody.appendChild(tr1);
		this[colour + "Field"] = sliderField;
	}
});