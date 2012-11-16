/**
 * @package Joomla!
 * @subpackage JavaScript
 * @since 1.5
 */
var FbDiggTable = new Class({

	getOptions : function() {
		return {
			'livesite' : '',
			'alreadyvoted' : 'alreadyvoted',
			'imageover' : '',
			'imageout' : '',
			'voteType' : 'record',
			'tableid' : 0,
			'formid' : 0,
			'view' : 'form',
			userid : 0
		};
	},

	initialize : function(id, options) {
		// preload image
		this.id = id;
		this.setOptions(this.getOptions(), options);
		this.spinner = new Asset.image(this.options.livesite + 'media/com_fabrik/images/ajax-loader.gif', {
			'alt' : 'loading',
			'class' : 'ajax-loader'
		});
		window.addEvent('fabrik.table.updaterows', this.makeEvents.bindAsEventListener(this));
		this.makeEvents();
	},

	removeEvents : function() {
		this.getLinks().each(function(l) {
			l.removeEvents();
		});
	},

	makeEvents : function() {
		this.values = {};
		this.links = this.getLinks();
		this.links.each(function(l) {
			var img = l.getElement('img');
			if ($type(img) !== false) {
				var fabrikrow = l.findClassUp('fabrik_row');
				var row_id = fabrikrow.id.split("_").pop();
				this.values[row_id] = (img.src == this.options.imageover) ? 1 : 0;

				l.addEvent('mouseenter', function() {

					this.previousimg = img.src;
					if (this.values[row_id] == 1) {
						img.src = this.options.imageout;
					} else {
						img.src = this.options.imageover;
					}
				}.bind(this));

				l.addEvent('mouseout', function() {
					img.src = this.previousimg;
				}.bind(this));

				l.addEvent('click', function(e) {
					var e = new Event(e).stop();
					var comment_id = this.options.view == 'form' ? row_id : 0;
					var data = {
						'row_id' : row_id,
						'elementname' : this.id,
						'voteType' : this.options.voteType,
						'commentId' : comment_id,
						'tableid' : this.options.tableid,
						'formid' : this.options.formid
					};

					if (this.values[row_id] == 1) {
						img.src = this.options.imageout;
						data.vote = 0;
						this.values[row_id] = 0;
						this.previousimg = this.options.imageout;
					} else {
						data.vote = 1;
						this.values[row_id] = 1;
						img.src = this.options.imageover;
						this.previousimg = this.options.imageover;
					}
					this.spinner.injectAfter(img);

					var url = this.options.livesite
							+ 'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikdigg&method=ajax_vote&element_id='
							+ this.options.elid;
					new Ajax(url, {
						'data' : data,
						onComplete : function(r) {
							this.spinner.remove();
							fabrikrow.getElement('.digg-votenum').setText(r);
						}.bind(this)
					}).request();
				}.bind(this));
			}
		}.bind(this));
	},
	
	getLinks:function()
	{
		var c = $('tableform_'+this.options.tableid);
		if ($type(c) === false){
			fconsole('fabrik digg - tableform_'+this.options.tableid + 'not found');
			return [];
		}else{
			return c.getElements('.fabrik_row___' + this.id).getElements('a').flatten();
		}
	}

});

FbDiggTable.implement(new Events);
FbDiggTable.implement(new Options);