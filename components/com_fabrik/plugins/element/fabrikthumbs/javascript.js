var fbThumbs = FbElement.extend({
	initialize : function(element, opts, thumb, lang) {
		this.parent(element, opts);
		this.field = $(element);
		this.imagepath = opts.liveSite + '/components/com_fabrik/plugins/element/fabrikthumbs/images/';
		this.translate = {
			yourrating : 'your rating',
			norating : 'no rating'
		};
		$extend(this.translate, lang);
		this.setOptions(element, opts);
		this.thumb = thumb;
		this.spinner = new Asset.image(this.options.liveSite + 'media/com_fabrik/images/ajax-loader.gif', {
			'alt' : 'loading',
			'class' : 'ajax-loader'
		});
		window.addEvent('fabrik.form.refresh', this.setup.bindAsEventListener(this));
		this.setup(this.options.row_id);
	},
	
	setup : function(rowid) {
		this.options.row_id = rowid;
		
		this.thumbup = $('thumbup');
		this.thumbdown = $('thumbdown');

		this.thumbup.addEvent('mouseover', function(e) {
			this.thumbup.setStyle('cursor', 'hand');
			this.thumbup.src = this.imagepath + "thumb_up_in.gif";
		}.bind(this));
		this.thumbdown.addEvent('mouseover', function(e) {
			this.thumbdown.setStyle('cursor', 'hand');
			this.thumbdown.src = this.imagepath + "thumb_down_in.gif";
		}.bind(this));

		this.thumbup.addEvent('mouseout', function(e) {
			this.thumbup.setStyle('cursor', '');
			if (this.options.myThumb == 'up') {
				this.thumbup.src = this.imagepath + "thumb_up_in.gif";
			} else {
				this.thumbup.src = this.imagepath + "thumb_up_out.gif";
			}
		}.bind(this));
		this.thumbdown.addEvent('mouseout', function(e) {
			this.thumbdown.setStyle('cursor', '');
			if (this.options.myThumb == 'down') {
				this.thumbdown.src = this.imagepath + "thumb_down_in.gif";
			} else {
				this.thumbdown.src = this.imagepath + "thumb_down_out.gif";
			}
		}.bind(this));

		this.thumbup.addEvent('click', function(e) {
			this.doAjax('up');
		}.bind(this));
		this.thumbdown.addEvent('click', function(e) {
			this.doAjax('down');
		}.bind(this));
	},

	doAjax : function(th) {
		if (this.options.editable == false) {
			var forspin = $('count_thumb' + th);
			this.spinner.injectInside(forspin);
			var data = {
				'row_id' : this.options.row_id,
				'elementname' : this.options.elid,
				'userid' : this.options.userid,
				'thumb' : th
			};
			var url = this.options.liveSite
					+ 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikthumbs&method=ajax_rate&element_id='
					+ this.options.elid + '&thumb=' + th;
			new Ajax(url, {
				'data' : data,
				onComplete : function(r) {
					this.spinner.remove();
					if (r != '') {
						var count_thumbup = $('count_thumbup');
						var count_thumbdown = $('count_thumbdown');
						var thumbup = $('thumbup');
						var thumbdown = $('thumbdown');
						r = r.split(this.options.splitter2);
						count_thumbup.innerHTML = r[0];
						count_thumbdown.innerHTML = r[1];
						// Well since the element can't be rendered in form view I guess
						// this isn't really needed
						$(this.element.id).getElement('.' + this.field.id).value = r[0].toFloat() - r[1].toFloat();
						if (th == 'up') {
							thumbup.src = this.imagepath + "thumb_up_in.gif";
							thumbdown.src = this.imagepath + "thumb_down_out.gif";
							thumbup.addEvent('mouseout', function(e) {
								thumbup.src = this.imagepath + "thumb_up_in.gif";
							}.bind(this));
							thumbdown.addEvent('mouseout', function(e) {
								thumbdown.src = this.imagepath + "thumb_down_out.gif";
							}.bind(this));
						} else {
							thumbup.src = this.imagepath + "thumb_up_out.gif";
							thumbdown.src = this.imagepath + "thumb_down_in.gif";
							thumbup.addEvent('mouseout', function(e) {
								thumbup.src = this.imagepath + "thumb_up_out.gif";
							}.bind(this));
							thumbdown.addEvent('mouseout', function(e) {
								thumbdown.src = this.imagepath + "thumb_down_in.gif";
							}.bind(this));
						}

					}
				}.bind(this)
			}).request();
		}
	}
});