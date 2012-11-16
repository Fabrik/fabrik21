/**
 * @author Robert
 */

var TableRowSelect = new Class({
	
	initialize:function(triggerEl, winid, formid) {
		this.triggerEl = triggerEl;
		this.winid = winid;
		this.formid = formid;
			window.addEvent('domready', function(e) {
				(function() {this.setUp();}.bind(this)).delay(1000);
			}.bind(this));	
	},

	setUp : function() {
		document.getElements('.fabrikTable').each(function(tbl) {
			if (!tbl.hasClass('filtertable')) {
				this.tableid = tbl.id.replace('table_', '');
				tbl.getElements('.fabrik_row').each(function(r) {
					r.setStyle('cursor', 'pointer');
					$(r).addEvent('mouseover', function(e) {
						if (r.hasClass('oddRow0') || r.hasClass('oddRow1')) {
							r.addClass('fabrikHover');
						}
					}, r);

					$(r).addEvent('mouseout', function(e) {
						r.removeClass('fabrikHover');
					}, r);
				});
			
				tbl.getElements('.fabrik_row').each(function(r) {
					$(r).addEvent('click', function(e) {
						var d = $A(r.id.split('_'));
						var data = {};
						data[this.triggerEl] = d.getLast();
						var json = {
							'errors' : {},
							'data' : data,
							'rowid':d.getLast(),
							formid:this.formid
						};
						oPackage.sendMessage('table_' + this.tableid, 'updateRows', 'ok', json);
					}.bind(this));
				}.bind(this));
			}
		}.bind(this));
	},

	receiveMessage : function(senderBlock, task, taskStatus, json) {
		// table status has updated lets reobserve the rows
		this.setUp();
	}
});