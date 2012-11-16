 var fbCalc = FbElement.extend({
	initialize: function(element, options) {
	 	this.parent(element, options);
		this.plugin = 'calc';
		this.setOptions(element, options);
	},
	
	attachedToForm : function() {
		if (this.options.ajax) {
			// @TODO - might want to think about firing ajaxCalc here as well, if we've just been added to the form
			// as part of duplicating a group.  Don't want to do it in cloned(), as that would be before elements
			// we observe have finished setting themselves up.  So just need to work out if this is on page load
			// or on group clone.
			this.ajaxCalc = this.calc.bindAsEventListener(this);
			var form = this.form;
			this.options.observe.each(function(o){

				if (this.form.formElements[o]) {
					this.form.formElements[o].addNewEventAux('change', this.ajaxCalc);
				}
				else {
					// $$$ hugh - check to see if an observed element is actually part of a repeat group,
					// and if so, modify the placeholder name they used to match this instance of it
					// @TODO - add and test code for non-joined repeats!
					if (this.options.canRepeat) {
						if (this.options.isGroupJoin) {
							var o2 = 'join___' + this.options.joinid + '___' + o + '_' + this.options.repeatCounter;
							if (this.form.formElements[o2]) {
								this.form.formElements[o2].addNewEventAux('change', this.ajaxCalc);
							}
						}
						else {
							var o2 = o + '_' + this.options.repeatCounter;
							if (this.form.formElements[o2]) {
								this.form.formElements[o2].addNewEventAux('change', this.ajaxCalc);
							}							
						}
					}
					else {
						this.form.repeatGroupMarkers.each(function(v, k) {
							var o2 = '';
							for (v2 = 0; v2 < v; v2++) {
								o2 = 'join___' + this.form.options.group_join_ids[k] + '___' + o + '_' + v2;
								if (this.form.formElements[o2]) {
										// $$$ hugh - think we can add this one as sticky ...
										this.form.formElements[o2].addNewEvent('change', this.ajaxCalc);
								}
							}
						}.bind(this));
					}
				}
			}.bind(this));
		}
	},
	
	calc: function(){
		this.element.getParent().getElement('.loader').setStyle('display', '');
		var url = this.options.liveSite + 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&plugin=fabrikcalc&method=ajax_calc&element_id=' + this.options.id;
		var formdata = this.form.getFormElementData(this);
		var testdata = $H(this.form.getFormData());
		testdata.each(function(v, k) {
			if (k.test(/^join\[\d+\]/)) {
				formdata[k] = v;
			}
		});
		var data = $extend(formdata, {'formid':this.form.id});
		var myAjax = new Ajax(url, {method:'post', 'data': data,
		onComplete: function(r){
			this.element.getParent().getElement('.loader').setStyle('display', 'none');
			this.update(r);
			this.element.fireEvent('change', new Event.Mock(this.element, 'change'));
		}.bind(this)}).request();
		
	}
});
