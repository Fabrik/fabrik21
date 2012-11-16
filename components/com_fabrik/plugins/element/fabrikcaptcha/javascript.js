var fbCaptcha = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikcaptcha';
		this.setOptions(element, options);
		if (this.options.method == 'recaptcha') {
			if (this.options.editable) {
				Recaptcha.create(
					this.options.recaptcha_pubkey,
					this.options.recaptcha_element_id,
					{
						lang: this.options.recaptcha_lang,
						theme: this.options.recaptcha_theme
					}
				);
				window.addEvent('fabrik.form.ajax_submit.failed', this.renewRecaptcha.bindAsEventListener(this));
			}
		}
	},

	renewRecaptcha: function() {
		Recaptcha.reload();
	},
	
	update: function( val ) {
		this.renewRecaptcha();
	}
});