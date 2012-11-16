var fbDuration = FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikduration';
		this.setOptions(element, options);
		this.watchMenus();
	},
	
	watchMenus: function() {
		this.hourElement = this.element.findClassUp('fabrikElementContainer').getElement('.fabrikHourMenu');
		this.hourElement.addEvent("change", this.updateFromMenu.bindAsEventListener(this));
		this.minuteElement = this.element.findClassUp('fabrikElementContainer').getElement('.fabrikMinuteMenu');
		this.minuteElement.addEvent("change", this.updateFromMenu.bindAsEventListener(this));
		if (this.options.show_seconds) {
			this.secondElement = this.element.findClassUp('fabrikElementContainer').getElement('.fabrikSecondMenu');
			this.secondElement.addEvent("change", this.updateFromMenu.bindAsEventListener(this));
		}
	},
	
	updateFromMenu: function() {
		this.element.value = (parseInt(this.hourElement.get('value')) * 3600) + (parseInt(this.minuteElement.get('value')) * 60);
		if (this.options.show_seconds) {
			this.element.value = parseInt(this.element.value) + parseInt(this.secondElement.get('value'));
		}
	},
	
	update : function(val) {
		this.fireEvents([ 'change' ]);
		if ($type(val) === false || val === false) {
			return;
		}
		if (!this.options.editable) {
			if ($type(this.element) !== false) {
				this.element.innerHTML = val;
			}
			return;
		}
		this.hourElement.selectedIndex = parseInt(parseInt(val) / 3600);
		this.minuteElement.selectedIndex = parseInt((parseInt(val) / 60) % 60);
		this.secondElement.selectedIndex = parseInt(parseInt(val) % 60);
		this.element.value = val;
	}


});