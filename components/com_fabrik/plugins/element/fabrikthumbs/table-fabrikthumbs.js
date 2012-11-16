/**
 * @package Joomla!
 * @subpackage JavaScript
 * @since 1.5
 */
var FbThumbsTable = new Class({

	getOptions : function() {
		return {
			'livesite' : '',
			'imageover' : '',
			'imageout' : '',
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
		this.origThumbUp = {};
		this.origThumbDown = {};
		this.col.each(function(tr) {
			var row = tr.findClassUp('fabrik_row');
			if (row !== false) {
				var rowid = row.id.replace('table_' + this.options.tableid + '_row_', '');
	
				var thumbup = tr.getElements('.thumbup');
				var thumbdown = tr.getElements('.thumbdown');
				thumbup.each(function(thumbup) {
					thumbup.addEvent('mouseover', function(e) {
						thumbup.src = this.options.imagepath + "thumb_up_in.gif";
					}.bind(this));
					thumbup.addEvent('mouseout', function(e) {
						if (this.options.myThumbs[rowid] == 'up') {
							thumbup.src = this.options.imagepath + "thumb_up_in.gif";
						} else {
							thumbup.src = this.options.imagepath + "thumb_up_out.gif";
						}
					}.bind(this));
					thumbup.addEvent('click', function(e) {
						this.doAjax(thumbup, 'up');
					}.bind(this));
				}.bind(this));

				thumbdown.each(function(thumbdown) {
					thumbdown.addEvent('mouseover', function(e) {
						thumbdown.src = this.options.imagepath + "thumb_down_in.gif";
					}.bind(this));
	
					thumbdown.addEvent('mouseout', function(e) {
						if (this.options.myThumbs[rowid] == 'down') {
							thumbdown.src = this.options.imagepath + "thumb_down_in.gif";
						} else {
							thumbdown.src = this.options.imagepath + "thumb_down_out.gif";
						}
					}.bind(this));
					thumbdown.addEvent('click', function(e) {
						this.doAjax(thumbdown, 'down');
					}.bind(this));
				}.bind(this));
			}
		}.bind(this));
	},

	doAjax : function(e, thumb) {
		var row = e.findClassUp('fabrik_row');
		var rowid = row.id.replace('table_' + this.options.tableid + '_row_', '');
		var cell = e.findClassUp('fabrik_element');
		var count_thumbup = cell.getElement('.counter_up');
		var count_thumbdown = cell.getElement('.counter_down');
		this.spinner.injectInside(cell);

		this.thumb = thumb;

		var data = {
			'row_id' : rowid,
			'elementname' : this.options.elid,
			'userid' : this.options.userid,
			'thumb' : this.thumb
		};
		var url = this.options.livesite
				+ 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikthumbs&method=ajax_rate&element_id='
				+ this.options.elid + '&thumb=' + this.thumb + '&row_id=' + rowid;
		new Ajax(url, {
			'data' : data,
			onComplete : function(r) {
				this.spinner.destroy();
				var thumbup = row.getElements('.thumbup');
				var thumbdown = row.getElements('.thumbdown');
				r = r.split(this.options.splitter2);
				count_thumbup.innerHTML = r[0];
				count_thumbdown.innerHTML = r[1];
				thumbup.each(function(thumbup) {
					if (thumb == 'up') {
						thumbup.src = this.options.imagepath + "thumb_up_in.gif";
						thumbup.addEvent('mouseout', function(e) {
							thumbup.src = this.options.imagepath + "thumb_up_in.gif";
						}.bind(this));
					} else {
						thumbup.src = this.options.imagepath + "thumb_up_out.gif";
						thumbup.addEvent('mouseout', function(e) {
							thumbup.src = this.options.imagepath + "thumb_up_out.gif";
						}.bind(this));
					}
				}.bind(this));

				thumbdown.each(function(thumbdown) {
					if (thumb == 'up') {
						thumbdown.src = this.options.imagepath + "thumb_down_out.gif";
						thumbdown.addEvent('mouseout', function(e) {
							thumbdown.src = this.options.imagepath + "thumb_down_out.gif";
						}.bind(this));
					} else {
						thumbdown.src = this.options.imagepath + "thumb_down_in.gif";
						thumbdown.addEvent('mouseout', function(e) {
							thumbdown.src = this.options.imagepath + "thumb_down_in.gif";
						}.bind(this));
					}
				}.bind(this));
			}.bind(this)
		}).request();
	}

});

FbThumbsTable.implement(new Events);
FbThumbsTable.implement(new Options);