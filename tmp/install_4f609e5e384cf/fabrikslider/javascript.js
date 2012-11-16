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
			var output = this.element.getElement('.fabrikinput');
			var output2 = this.element.getElement('.slider_output');
			this.mySlide = new Slider(this.element.getElement('.fabrikslider-line'), this.element.getElement('.knob'), {
				onChange: function(pos) {
					output.value = pos;
					output2.setText(pos);
				},
				onComplete: function (pos) {
					//fire for validations
					output.fireEvent('blur', new Event.Mock(output, 'change'));
				},
				steps:this.options.steps
			}).set(0);
			
			this.mySlide.set(this.options.value);
			output.value = this.options.value;
			output2.setText(this.options.value);
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
	}
});