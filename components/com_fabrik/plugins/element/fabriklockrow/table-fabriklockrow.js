/**
 * @package Joomla!
 * @subpackage JavaScript
 * @since 1.5
 */
var FbLockrowTable = new Class({

	getOptions : function() {
		return {
			'livesite' : '',
			'locked_img' : '',
			'unlocked_img' : '',
			'userid' : ''
		};
	},

	initialize : function(id, options) {
		this.setOptions(this.getOptions(), options);
			this.id = id;
		// preload image
		this.spinner = new Asset.image(this.options.livesite + 'media/com_fabrik/images/ajax-loader.gif', {
			'alt' : 'loading',
			'class' : 'ajax-loader'
		});
		window.addEvent('fabrik.table.updaterows', this.makeEvents.bindAsEventListener(this));
		this.makeEvents();
	},
	
	makeEvents : function() {
		this.col = $$('.fabrik_row___' + this.id);
		this.col.each(function(tr) {
			var row = tr.findClassUp('fabrik_row');
			if (row !== false) {
				var rowid = row.id.replace('table_' + this.options.tableid + '_row_', '');
				var all_locked = tr.getElements('.fabrikElement_lockrow_locked');
				var all_unlocked = tr.getElements('.fabrikElement_lockrow_unlocked');
				all_locked.each(function(locked) {
					if (this.options.can_unlocks[rowid]) {
						locked.addEvent('mouseover', function(e) {
							locked.src = this.options.imagepath + "key.png";
						}.bind(this));
						locked.addEvent('mouseout', function(e) {
							locked.src = this.options.imagepath + "locked.png";
						}.bind(this));
						locked.addEvent('click', function(e) {
							this.doAjaxUnlock(locked);
						}.bind(this));
					}
				}.bind(this));

				all_unlocked.each(function(unlocked) {
					if (this.options.can_locks[rowid]) {
						unlocked.addEvent('mouseover', function(e) {
							unlocked.src = this.options.imagepath + "key.png";
						}.bind(this));
						unlocked.addEvent('mouseout', function(e) {
							unlocked.src = this.options.imagepath + "unlocked.png";
						}.bind(this));
						unlocked.addEvent('click', function(e) {
							this.doAjaxLock(locked);
						}.bind(this));
					}
				}.bind(this));
			}
		}.bind(this));
	},

	doAjaxUnlock : function(e) {
		var row = e.findClassUp('fabrik_row');
		var rowid = row.id.replace('table_' + this.options.tableid + '_row_', '');
		
		var data = {
			'row_id' : rowid,
			'element_id' : this.options.elid,
			'userid' : this.options.userid
		};
		var url = this.options.livesite
				+ 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabriklockrow&method=ajax_unlock';
		new Ajax(url, {
			'data' : data,
			onComplete : function(r) {
				r = Json.evaluate(r.stripScripts());
				if (r.status == 'unlocked') {
					this.options.row_locks[rowid] = false;
					e.removeEvents('mouseover');
					e.removeEvents('mouseout');
					e.removeEvents('click');
					e.src = this.options.imagepath + "unlocked.png";
					if (this.options.can_locks[rowid]) {
						e.addEvent('mouseover', function(el) {
							el.src = this.options.imagepath + "key.png";
						}.bind(this));
						e.addEvent('mouseout', function(el) {
							el.src = this.options.imagepath + "unlocked.png";
						}.bind(this));
						e.addEvent('click', function(el) {
							this.doAjaxLock(locked);
						}.bind(this));
					}
				}
			}.bind(this)
		}).request();
	},
	
	doAjaxLock : function(e) {
		
	}
	

});

FbLockrowTable.implement(new Events);
FbLockrowTable.implement(new Options);