var fbDigg =  FbElement.extend({
	initialize: function(element, options) {
		this.parent(element, options);
		this.plugin = 'fabrikdigg';
		this.setOptions(element, options);
		window.addEvent('fabrik.form.refresh', this.setup.bindAsEventListener(this));
		this.setup(this.options.row_id);
	},
	
	setup : function(rowid) {
		this.options.row_id = rowid;
		var fe = $(this.options.element).findClassUp('fabrikElement');
		if (fe == false) {
			return;
		}
		this.image = fe.getElement('img');
		this.votenum = fe.getElement('.digg-votenum');
		this.previousimg = this.image.src;
		this.spinner = new Asset.image(this.options.livesite+'media/com_fabrik/images/ajax-loader.gif', {	'alt':'loading','class':'ajax-loader'});
	
		this.image.addEvent('mouseenter', function() {
			this.previousimg = this.image.src;
			if(this.image.src == this.options.imageover) {
				this.image.src = this.options.imageout;
			}else{
				this.image.src = this.options.imageover;				
			}
			
		}.bind(this));
		
		this.image.addEvent('mouseout', function() {
			this.image.src  = this.previousimg;
		}.bind(this));
		
		this.image.addEvent('click', function(e) {
			new Event(e).stop();
			var data = {'row_id':this.options.row_id,
					'elementname':this.options.elementname,
					'tableid':this.options.tableid,
					'formid':this.options.formid};
			if(this.image.src != this.options.imageover) {
				this.element.value = 0;
				this.options.value = 0;
				data.vote  = 0;
				this.image.src = this.options.imageout;
				this.image.alt = this.optionsdigthis;
				this.image.title = this.options.digthis;
				this.previousimg = this.options.imageout;
			}else{
				this.element.value = 1;
				data.vote  = 1;
				this.options.value = 1;
				this.image.src = this.options.imageover;
				this.image.alt = this.options.undigthis;
				this.image.title = this.options.undigthis;
				this.previousimg = this.options.imageover;
			}
			if (this.options.editable == false) {
				this.spinner.injectAfter(this.image);
				var url = this.options.livesite+'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikdigg&method=ajax_vote&element_id='+this.options.elid;
				new Ajax(url, {
					'data':data,
					onComplete:function(r) {
					this.votenum.setText(r);
						this.spinner.remove();
					}.bind(this)
				}).request();
			}
		}.bind(this));
	}
});