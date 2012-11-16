/**
 * @package		Joomla!
 * @subpackage	JavaScript
 * @since		1.5
 */
var FbReportRecordTable = new Class({

    getOptions: function() {
        return {
        	'livesite':'',
					'tableid':0,
					'formid':0,
					'view':'form',
					userid:0
    };
	},
	
	initialize: function(id, options) {
	//preload image
		this.id = id;
		this.setOptions(this.getOptions(), options);
		this.spinner = new Asset.image(this.options.livesite+'media/com_fabrik/images/ajax-loader.gif', {	'alt':'loading','class':'ajax-loader'});
		this.observeCells();
	},
	
	removeEvents:function()
	{
		this.getLinks().each(function(l) {
			l.removeEvents();
		});
	},

	observeCells:function() {
		this.values = {};
		this.links = this.getLinks();
		this.links.each(function(l) {
				
			//	this.values[row_id] = (img.src == this.options.imageover) ? 1 : 0;
				
				l.addEvent('click', function(e) {
					
					var fabrikrow = $(e.target).findClassUp('fabrik_row');
					var rowid = fabrikrow.id.split("_").pop();
					
					var e = new Event(e).stop();
					var data = {'rowid':rowid,
						'elementname':this.id,
						'tableid':this.options.tableid,
						'formid':this.options.formid
						};
				
					this.spinner.injectAfter(l);
					var url = this.options.livesite+'index.php?option=com_fabrik&format=raw&controller=plugin&g=element&task=pluginAjax&plugin=report_record&method=report&elid='+this.options.elid;
					
					new Ajax(url, {
						'data':data,
						onComplete:function(r) {
							this.spinner.remove();
							fabrikrow.getElement('.report_record_button').replaceWith(new Element('span').setText(r));
						}.bind(this)
					}).request();
				}.bind(this));
		}.bind(this));
	},
	
	getLinks:function()
	{
		return $('tableform_'+this.options.tableid).getElements('.fabrik_row___' + this.id).getElements('.report_record_button').flatten();
	}
	
});

FbReportRecordTable.implement(new Events);
FbReportRecordTable.implement(new Options);