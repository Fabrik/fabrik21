var fbRating = FbElement.extend({
	initialize : function(element, opts, rating, lang) {
		this.parent(element, opts);
		this.field = $(element);
		this.imagepath = opts.liveSite + '/components/com_fabrik/plugins/element/fabrikrating/images/';
		this.translate = {
			yourrating : 'your rating',
			norating : 'no rating'
		};
		$extend(this.translate, lang);
		this.setOptions(element, opts);
		if (this.options.canRate == false) {
			return;
		}
		if (this.options.mode === 'creator-rating' && this.options.view === 'details') {
			// deactivate if in detail view and only the record creator can rate
			return;
		}
		this.rating = rating;
		window.addEvent('fabrik.form.refresh', this.setup.bindAsEventListener(this));
		this.setup(this.options.row_id);
		this.setStars();
	},
	
	setup : function (rowid) {
		this.options.row_id = rowid;
		var element = $(this.options.element + '_div');
		this.spinner = new Asset.image(this.options.liveSite + 'media/com_fabrik/images/ajax-loader.gif', {
			'alt' : 'loading',
			'class' : 'ajax-loader'
		});
		this.stars = element.getElements('.starRating');
		this.ratingMessage = element.getElement('.ratingMessage');
		this.stars.each(function(i) {
			i.addEvent('mouseover', function (e) {
				this.stars.each(function (ii) {
					if (this._getRating(i) >= this._getRating(ii)) {
						ii.src = this.imagepath + "star_in" + this.options.ext;
					}
				}.bind(this));
				this.ratingMessage.innerHTML = i.alt;
			}.bind(this));
		}.bind(this));

		this.stars.each(function(i) {
			i.addEvent('mouseout', function(e) {
				this.stars.each(function(ii) {
					ii.src = this.imagepath + "star_out" + this.options.ext;
				}.bind(this));
			}.bind(this));
		}.bind(this));

		this.stars.each(function (i) {
			i.addEvent('click', function (e) {
				this.rating = this._getRating(i);
				this.field.value = this.rating;
				this.doAjax();
				this.setStars();
			}.bind(this));
		}.bind(this));
		var clearButton = this.element.getElement('.rate_-1');
		element.addEvent('mouseout', function (e) {
			this.setStars();
		}.bind(this));

		element.addEvent('mouseover', function(e) {
			if ($type(clearButton) !== false) {
				clearButton.setStyles({
					visibility : 'visible'
				});
			}
		}.bind(this));

		if ($type(clearButton) !== false) {
			clearButton.addEvent('mouseover', function (e) {
				e = new Event(e);
				e.target.src = this.imagepath + "clear_rating_in" + this.options.ext;
				this.ratingMessage.innerHTML = this.translate.norating;
			}.bind(this));

			clearButton.addEvent('mouseout', function (e) {
				e = new Event(e);
				if (this.rating != -1) {
					e.target.src = this.imagepath + "clear_rating_out" + this.options.ext;
				}
			}.bind(this));

			clearButton.addEvent('click', function (e) {
				this.rating = -1;
				this.field.value = '';
				this.stars.each(function(ii) {
					ii.src = this.imagepath + "star_out" + this.options.ext;
				}.bind(this));
				e = new Event(e);
				this.element.getElement('.rate_-1').src = this.imagepath + "clear_rating_in" + this.options.ext;
				this.doAjax();
			}.bind(this));
		}
	},

	doAjax : function() {
		if (this.options.canRate == false) {
			return;
		}
		if (this.options.editable == false) {
			this.spinner.injectInside(this.ratingMessage);
			var data = {
				'row_id': this.options.row_id,
				'elementname': this.options.elid,
				'userid': this.options.userid,
				'rating': this.rating
			};
			var url = this.options.liveSite
					+ 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikrating&method=ajax_rate&element_id='
					+ this.options.elid;
			new Ajax(url, {
				'data' : data,
				onComplete : function(r) {
					if(this.options.mode != 'creator-rating') {
						this.update(r);
						window.fireEvent('fabrik.element.rating.updated', r);
						this.ratingMessage.setText('');
					}
					this.spinner.remove();
				}.bind(this)
			}).request();
		}
	},

	_getRating : function (i) {
		r = i.className.replace("rate_", "").replace("starRating ", "");
		return r.toInt();
	},

	setStars : function () {
		this.stars.each(function (ii) {
			var starScore = this._getRating(ii);
			if (starScore <= this.rating) {
				ii.src = this.imagepath + "star_in" + this.options.ext;
			} else {
				ii.src = this.imagepath + "star_out" + this.options.ext;
			}
		}.bind(this));
		if ($type(this.element.getElement('.rate_-1')) != false){
			if (this.rating != -1) {
				this.element.getElement('.rate_-1').src = this.imagepath + "clear_rating_out" + this.options.ext;
			} else {
				this.element.getElement('.rate_-1').src = this.imagepath + "clear_rating_in" + this.options.ext;
			}
		}
	},

	update : function (val) {
		this.rating = val.toInt().round();
		this.field.value = this.rating;
		this.element.getElement('.ratingScore').setText(val);
		this.setStars();
	}
});