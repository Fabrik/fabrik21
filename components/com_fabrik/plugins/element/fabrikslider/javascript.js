var fbSlider = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikslider';
		this.element = $(element);
		this.setOptions(element, options);
		if($type(this.options.value) === false) {
			this.options.value = 0;
		}
		this.options.value = this.options.value.toInt();
		if(this.options.editable === true) {
			window.addEvent('domready', function() {
				if ($type(this.element) === false) {
					return;
				}
				var init_value = this.options.value;
				var output = this.element.getElement('.fabrikinput');
				var output2 = this.element.getElement('.slider_output');
				this.mySlide = new Slider(this.element.getElement('.fabrikslider-line'), this.element.getElement('.knob'), {
					onChange: function(pos) {
						output.value = pos;
						output2.setText(pos);
						this.options.value = pos;
					}.bind(this),
					onComplete: function (pos) {
						//fire for validations
						this.options.value = pos;
						output.fireEvent('blur', new Event.Mock(output, 'blur'));
						this.element.fireEvent('change', new Event.Mock(this.element, 'change'));
					}.bind(this),
					steps:this.options.steps
				}).set(0);
				
				this.mySlide.set(init_value);
				output.value = init_value;
				output2.setText(init_value);
				var clear = this.element.getElement('.clearslider');
				if ($type(clear)!==false) {
					clear.addEvent('click', function(e) {
						this.mySlide.set(0);
						output.value = '';
						output.fireEvent('blur', new Event.Mock(output, 'change'));
						output2.setText('');
						new Event(e).stop();
					});
				}
			}.bind(this));
		}
	},
	
	getValue: function () {
		return this.options.value;
	}
	
});