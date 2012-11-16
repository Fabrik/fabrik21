var Notify = new Class({
	
	initialize: function(el, options) {
		this.options = options;
		var target = $(el);
		if(target.getStyle('display') == 'none') {
			target = target.getParent();
		}
		target.addEvent('mouseup', function(e) {
			oPackage.startLoading(this.options.senderBlock);
			var myAjax = new Ajax('index.php?option=com_fabrik&controller=plugin&task=pluginAjax&plugin=fabriknotification&method=toggleNotification', {

				data :{
					g:'form',
					format:'raw',
					fabrik_notification:1,
					tableid:this.options.tableid,
					fabrik:this.options.fabrik,
					rowid:this.options.rowid,
					notify: $(el).checked
				},
				onComplete : function(r) {
						oPackage.stopLoading(this.options.senderBlock, r);
				}.bind(this)
			}).request();

		}.bind(this));
	}
});