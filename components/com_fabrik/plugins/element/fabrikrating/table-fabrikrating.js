/**
 * @package Joomla!
 * @subpackage JavaScript
 * @since 1.5
 */
var FbRatingTable = new Class({

	getOptions : function() {
		return {
			'livesite' : '',
			userid : 0,
			'mode' : ''
		};
	},

	initialize: function(id, options) {
		this.setOptions(this.getOptions(), options);
		if (this.options.mode == 'creator-rating') {
			return;
		}
		// preload image
		this.spinner = new Asset.image(this.options.livesite + 'media/com_fabrik/images/ajax-loader.gif', {
			'alt' : 'loading',
			'class' : 'ajax-loader'
		});
		this.col = $$('.fabrik_row___' + id);
		this.origRating = {};
		this.col.each(function(tr) {
			var stars = tr.getElements('.starRating');

			stars.each(function(star) {
				star.addEvent('mouseover', function(e) {
					this.origRating[tr.id] = star.findClassUp('fabrik_element').getElement('.ratingMessage').innerHTML.toInt();
					stars.each(function(ii) {
						if (this._getRating(star) >= this._getRating(ii)) {
							ii.src = this.options.imagepath + "star_in" + this.options.ext;
						} else {
							ii.src = this.options.imagepath + "star_out" + this.options.ext;
						}
					}.bind(this));
					star.findClassUp('fabrik_element').getElement('.ratingMessage').innerHTML = star.alt;
				}.bind(this));

				star.addEvent('mouseout', function(e) {
					stars.each(function(ii) {
						if (this.origRating[tr.id] >= this._getRating(ii)) {
							ii.src = this.options.imagepath + "star_in" + this.options.ext;
						} else {
							ii.src = this.options.imagepath + "star_out" + this.options.ext;
						}
					}.bind(this));
					star.findClassUp('fabrik_element').getElement('.ratingMessage').innerHTML = this.origRating[tr.id];
				}.bind(this));
			}.bind(this));

			stars.each(function(star) {
				star.addEvent('click', this.doAjax.bindAsEventListener(this, [ star ]));
			}.bind(this));

		}.bind(this));

	},

	_getRating : function(i) {
		r = i.className.replace("rate_", "").replace("starRating ", "");
		return r.toInt();
	},

	doAjax : function(e, star) {
		new Event(e).stop();
		this.rating = this._getRating(star);
		var ratingmsg = star.findClassUp('fabrik_element').getElement('.ratingMessage');
		this.spinner.injectInside(ratingmsg);
		var row = $(star).findClassUp('fabrik_row');
		var rowid = row.id.replace('table_' + this.options.tableid + '_row_', '');
		var data = {
			'row_id' : rowid,
			'elementname' : this.options.elid,
			'userid' : this.options.userid,
			'rating' : this.rating,
			'mode' : this.options.mode
		};
		var url = this.options.livesite
				+ 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikrating&method=ajax_rate&element_id='
				+ this.options.elid;
		new Ajax(url, {
			'data' : data,
			onComplete : function(r) {
				r = r.toInt();
				this.rating = r;
				ratingmsg.setHTML(this.rating);
				this.spinner.remove();
				star.findClassUp('fabrik_element').getElements('img').each(function(i, x) {

					if (x < r) {
						i.src = this.options.imagepath + "star_in" + this.options.ext;
					} else {
						i.src = this.options.imagepath + "star_out" + this.options.ext;
					}
				}.bind(this));
			}.bind(this)
		}).request();
	}

});

FbRatingTable.implement(new Events);
FbRatingTable.implement(new Options);