var FbTableOrder = FbTablePlugin.extend({
	initialize: function(tableform, options, lang) {
		this.setOptions(tableform, options);
		
		window.addEvent('domready', function() {
			//for iE?
			document.ondragstart = function() {
				return false;
			};
			var container = $(options.container);
			container.setStyle('position', 'relative'); 
			if ($type(container.getElement('tbody')) !== false) {
				container = container.getElement('tbody');
			}
			
			if (this.options.handle !== false && container.getElements(this.options.handle).length === 0) {
				fconsole('order: handle selected ('+this.options.handle+') but not found in container');
				return;
			}
			/*
			 * duration: 500,
			 transition: 'elastic:out',
			 constrain: false,
			 revert: true,
			 */
			this.sortable = new Sortables(container, {

				clone: true,
				constrain: false,
			 revert: true,
			 opacity:0.7,
			 transition: 'elastic:out',
			 
				'handle' : this.options.handle,
				onComplete : function(element, clone) {
					clone ? clone.removeClass('fabrikDragSelected') : element.removeClass('fabrikDragSelected');
					//element.removeClass('fabrikDragSelected');
					this.neworder = this.getOrder();
					
					oPackage.startLoading('table_'+this.options.tableid, 'sorting', true);
					var url = this.options.liveSite + 'index.php?option=com_fabrik&controller=plugin&format=raw';
					new Ajax(url, {
						'data': {
							'task':'pluginAjax',
							'plugin':'order',
							'g':'table',
							'method':'ajaxReorder',
							'order':this.neworder,
							'origorder':this.origorder,
							'dragged' : this.getRowId(element),
							'tableid':this.options.tableid,
							'orderelid':this.options.orderElementId,
							'direction':this.options.direction
						},
						'onComplete': function(r) {
							oPackage.stopLoading('table_'+this.options.tableid, null, true);
							this.origorder = this.neworder;
						}.bind(this)
					}).request();

				}.bind(this),
				onStart: function(element, clone) {
					this.origorder = this.getOrder();
					clone ? clone.addClass('fabrikDragSelected') : element.addClass('fabrikDragSelected');
				}.bind(this)
			});

			if (options.enabled === false) {
				fconsole('drag n drop reordering not enabled - need to order by ordering element');
				this.sortable.detach();
			} else {
				if (this.options.handle) {
					container.getElements(this.options.handle).setStyle('cursor', 'move');
				} else {
					container.getChildren().setStyle('cursor', 'move');
				}
			}
		}.bind(this));
	},
	
	// get the id from the fabrik row's html id
	
	getRowId : function(element) {
		return $type(element.getProperty('id')) === false ? null : element.getProperty('id').replace('table_'+this.options.tableid+'_row_', '');
	},
	
	//get the order of the sortable
	
	getOrder : function() {
		return (this.sortable.serialize(0, function(element){
			return this.getRowId(element);
		}.bind(this))).clean();
	}
});