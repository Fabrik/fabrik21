var fbPassword = FbElement.extend({
	initialize: function(element, options, lang) {
		this.parent(element, options);
		this.setOptions(element, options);
		this.lang = lang;
		this.element = $(element);
		if(this.element && this.getConfirmationField()) {
			this.element.addEvent('keyup', this.passwordChanged.bindAsEventListener(this))

                    if(this.options.ajax_validation == true) {
                            this.getConfirmationField().addEvent('blur', this.callvalidation.bindAsEventListener(this));
                    }

                    if(this.getConfirmationField().get('value') == '') {
                            this.getConfirmationField().value = this.element.value;
                    }
                }
	},

	callvalidation: function(e)
	{
		this.form.doElementValidation(e, false, '_check');
	},

	passwordChanged: function() {
		var strength = this.element.getParent().getElement('.strength');
		if($type(strength) == false) {
			return;
		}
		var strongRegex = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
		var mediumRegex = new RegExp("^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$", "g");
		var enoughRegex = new RegExp("(?=.{6,}).*", "g");
		var pwd = this.element;
		if (pwd.value.length==0) {
		strength.innerHTML = this.lang.type_password;
		} else if (false == enoughRegex.test(pwd.value)) {
		strength.innerHTML = this.lang.more_characters;
		} else if (strongRegex.test(pwd.value)) {
		strength.innerHTML = '<span style="color:green">'+this.lang.strong+'</span>';
		} else if (mediumRegex.test(pwd.value)) {
		strength.innerHTML = '<span style="color:orange">'+this.lang.medium+'</span>';
		} else {
		strength.innerHTML = '<span style="color:red">'+this.lang.weak+'</span>';
		}
	},

	getConfirmationField: function()
	{
		var name = this.element.name +  '_check';
		return this.element.findClassUp('fabrikElement').getElement('input[name='+name+']');
	}
});